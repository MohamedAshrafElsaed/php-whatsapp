<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Import;
use App\Models\Recipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    /**
     * Show imports list
     */
    public function index(Request $request): Response
    {
        $imports = $request->user()
            ->imports()
            ->latest()
            ->paginate(20);

        return Inertia::render('contacts/imports/Index', [
            'imports' => $imports,
        ]);
    }

    /**
     * Download template file
     */
    public function template(Request $request): StreamedResponse
    {
        $format = $request->query('format', 'csv');

        if ($format === 'xlsx') {
            $path = storage_path('app/templates/contacts_template.xlsx');
            return response()->streamDownload(function () use ($path) {
                echo file_get_contents($path);
            }, 'contacts_template.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        // CSV template
        return response()->streamDownload(function () {
            echo "phone,first_name,last_name,email,company,city\n";
            echo "+1234567890,John,Doe,john@example.com,Acme Corp,New York\n";
            echo "+9876543210,Jane,Smith,jane@example.com,Tech Ltd,London\n";
        }, 'contacts_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Upload and process file
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
        ]);

        $path = null;

        try {
            $file = $request->file('file');
            $originalFilename = $file->getClientOriginalName();

            // Sanitize filename - remove spaces and special characters
            $sanitizedFilename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $originalFilename);
            $uniqueFilename = uniqid() . '_' . $sanitizedFilename;

            // Store file
            $path = $file->storeAs('imports', $uniqueFilename);

            if (!$path) {
                throw new \Exception('Failed to store uploaded file');
            }

            // Get absolute path with proper separators
            $absolutePath = Storage::path($path);

            if (!file_exists($absolutePath)) {
                throw new \Exception('File was not saved properly');
            }

            \Log::info('Processing import file', [
                'original' => $originalFilename,
                'stored_path' => $path,
                'absolute_path' => $absolutePath,
            ]);

            // Parse file
            $result = $this->parseFile($absolutePath, $request->user()->id);

            // Create import record
            $import = Import::create([
                'user_id' => $request->user()->id,
                'filename' => $originalFilename,
                'total_rows' => $result['total'],
                'valid_rows' => $result['valid'],
                'invalid_rows' => $result['invalid'],
                'status' => 'ready',
            ]);

            // Save recipients (upsert to handle duplicates)
            $created = 0;
            $updated = 0;

            foreach ($result['recipients'] as $recipientData) {
                if ($recipientData['phone_e164']) {
                    // Check if contact exists for this user with this phone
                    $existing = Recipient::where('user_id', $request->user()->id)
                        ->where('phone_e164', $recipientData['phone_e164'])
                        ->first();

                    if ($existing) {
                        // Update existing contact
                        $existing->update([
                            'phone_raw' => $recipientData['phone_raw'],
                            'first_name' => $recipientData['first_name'] ?? $existing->first_name,
                            'last_name' => $recipientData['last_name'] ?? $existing->last_name,
                            'email' => $recipientData['email'] ?? $existing->email,
                            'extra_json' => array_merge($existing->extra_json ?? [], $recipientData['extra_json']),
                            'is_valid' => $recipientData['is_valid'],
                            'validation_errors_json' => $recipientData['validation_errors_json'],
                            'import_id' => $import->id, // Update to latest import
                        ]);
                        $updated++;
                    } else {
                        // Create new contact
                        Recipient::create(array_merge($recipientData, [
                            'import_id' => $import->id,
                            'user_id' => $request->user()->id,
                        ]));
                        $created++;
                    }
                } else {
                    // Invalid phone, just create record
                    Recipient::create(array_merge($recipientData, [
                        'import_id' => $import->id,
                        'user_id' => $request->user()->id,
                    ]));
                }
            }

            AuditLog::log('imported', 'Import', $import->id, [
                'filename' => $originalFilename,
                'total_rows' => $result['total'],
                'created' => $created,
                'updated' => $updated,
            ]);

            // Clean up file
            if ($path) {
                Storage::delete($path);
            }

            $message = "Successfully imported {$result['valid']} valid contacts ({$created} new, {$updated} updated) out of {$result['total']} total rows.";

            return redirect()->route('imports.show', $import)
                ->with('success', $message);

        } catch (\Exception $e) {
            // Clean up file if it exists
            if ($path) {
                Storage::delete($path);
            }

            \Log::error('Import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
            ]);

            return redirect()->route('imports.index')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse uploaded file
     */
    private function parseFile(string $filePath, int $userId): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File does not exist at path: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \Exception("File is not readable: {$filePath}");
        }

        // Determine file type and create appropriate reader
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        try {
            if (strtolower($extension) === 'xlsx') {
                $reader = new XLSXReader();
            } else {
                $reader = new CSVReader();
            }

            $reader->open($filePath);
        } catch (\Exception $e) {
            throw new \Exception("Failed to open file: " . $e->getMessage());
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $recipients = [];
        $seenPhones = [];
        $totalRows = 0;
        $validRows = 0;
        $invalidRows = 0;
        $headers = [];
        $isFirstRow = true;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowData = $row->toArray();

                    // First row is header
                    if ($isFirstRow) {
                        $headers = array_map('trim', array_map('strtolower', $rowData));

                        // Validate required header
                        if (!in_array('phone', $headers)) {
                            throw new \Exception("Required column 'phone' not found in CSV headers. Found: " . implode(', ', $headers));
                        }

                        $isFirstRow = false;
                        continue;
                    }

                    $totalRows++;

                    // Map row data to headers
                    $data = array_combine($headers, array_pad($rowData, count($headers), ''));

                    // Validate phone (required)
                    if (empty($data['phone'])) {
                        $invalidRows++;
                        continue;
                    }

                    $phoneRaw = trim($data['phone']);
                    $phoneE164 = null;
                    $errors = [];

                    // Normalize phone to E.164
                    try {
                        $phoneNumber = $phoneUtil->parse($phoneRaw, null);
                        if ($phoneUtil->isValidNumber($phoneNumber)) {
                            $phoneE164 = $phoneUtil->format($phoneNumber, \libphonenumber\PhoneNumberFormat::E164);
                        } else {
                            $errors[] = 'Invalid phone number';
                        }
                    } catch (NumberParseException $e) {
                        $errors[] = 'Failed to parse phone number';
                    }

                    // Deduplicate
                    if ($phoneE164 && isset($seenPhones[$phoneE164])) {
                        $invalidRows++;
                        continue;
                    }

                    // Extract known fields
                    $firstName = $data['first_name'] ?? null;
                    $lastName = $data['last_name'] ?? null;
                    $email = $data['email'] ?? null;

                    // Store extra fields
                    $extraFields = array_diff_key($data, array_flip([
                        'phone', 'first_name', 'last_name', 'email'
                    ]));

                    $isValid = empty($errors) && $phoneE164;

                    $recipients[] = [
                        'phone_raw' => $phoneRaw,
                        'phone_e164' => $phoneE164,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'extra_json' => $extraFields,
                        'is_valid' => $isValid,
                        'validation_errors_json' => $errors ?: null,
                    ];

                    if ($phoneE164) {
                        $seenPhones[$phoneE164] = true;
                    }

                    if ($isValid) {
                        $validRows++;
                    } else {
                        $invalidRows++;
                    }
                }
            }
        } catch (\Exception $e) {
            $reader->close();
            throw new \Exception("Error parsing file content: " . $e->getMessage());
        }

        $reader->close();

        if ($totalRows === 0) {
            throw new \Exception("No data rows found in file. Make sure the file has a header row and at least one data row.");
        }

        return [
            'total' => $totalRows,
            'valid' => $validRows,
            'invalid' => $invalidRows,
            'recipients' => $recipients,
        ];
    }

    /**
     * Show import details
     */
    public function show(Request $request, Import $import): Response
    {
        $this->authorize('view', $import);

        $recipients = $import->recipients()
            ->latest()
            ->limit(50)
            ->get();

        return Inertia::render('contacts/imports/Show', [
            'importData' => $import->load('user'),
            'recipients' => $recipients,
        ]);
    }

    /**
     * Delete import
     */
    public function destroy(Request $request, Import $import): RedirectResponse
    {
        $this->authorize('delete', $import);

        $import->update(['status' => 'deleted']);
        $import->delete();

        AuditLog::log('deleted', 'Import', $import->id);

        return redirect()->route('imports.index')
            ->with('success', 'Import deleted successfully.');
    }
}
