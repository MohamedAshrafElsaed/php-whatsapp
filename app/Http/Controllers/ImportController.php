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
use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;
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

        return Inertia::render('Contacts/Imports/Index', [
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

        try {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $path = $file->storeAs('imports', uniqid() . '_' . $filename);

            // Parse file
            $result = $this->parseFile(storage_path('app/' . $path), $request->user()->id);

            // Create import record
            $import = Import::create([
                'user_id' => $request->user()->id,
                'filename' => $filename,
                'total_rows' => $result['total'],
                'valid_rows' => $result['valid'],
                'invalid_rows' => $result['invalid'],
                'status' => 'ready',
            ]);

            // Save recipients
            foreach ($result['recipients'] as $recipientData) {
                Recipient::create(array_merge($recipientData, [
                    'import_id' => $import->id,
                    'user_id' => $request->user()->id,
                ]));
            }

            AuditLog::log('imported', 'Import', $import->id, [
                'filename' => $filename,
                'total_rows' => $result['total'],
            ]);

            // Clean up file
            Storage::delete($path);

            return redirect()->route('imports.show', $import)
                ->with('success', "Imported {$result['valid']} valid contacts.");
        } catch (\Exception $e) {
            return redirect()->route('imports.index')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
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

        return Inertia::render('Contacts/Imports/Show', [
            'import' => $import->load('user'),
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

    /**
     * Parse uploaded file
     */
    private function parseFile(string $filePath, int $userId): array
    {
        $reader = ReaderEntityFactory::createReaderFromFile($filePath);
        $reader->open($filePath);

        $phoneUtil = PhoneNumberUtil::getInstance();
        $recipients = [];
        $seenPhones = [];
        $totalRows = 0;
        $validRows = 0;
        $invalidRows = 0;
        $headers = [];
        $isFirstRow = true;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                $rowData = array_map(fn($cell) => $cell->getValue(), $cells);

                // First row is header
                if ($isFirstRow) {
                    $headers = array_map('trim', array_map('strtolower', $rowData));
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

        $reader->close();

        return [
            'total' => $totalRows,
            'valid' => $validRows,
            'invalid' => $invalidRows,
            'recipients' => $recipients,
        ];
    }
}
