<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

class Contact extends Model
{
    protected $fillable = [
        'user_id',
        'wa_session_id',
        'jid',
        'phone_raw',
        'phone_e164',
        'first_name',
        'last_name',
        'full_name',
        'is_valid',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function waSession(): BelongsTo
    {
        return $this->belongsTo(WaSession::class);
    }

    /**
     * Extract and format phone number from JID
     */
    public static function parseJid(string $jid): array
    {
        // Extract phone number from JID (e.g., "201120133111@s.whatsapp.net" -> "201120133111")
        $phoneRaw = explode('@', $jid)[0];

        $phoneUtil = PhoneNumberUtil::getInstance();
        $phoneE164 = null;
        $isValid = false;

        try {
            // Try to parse the phone number
            $phoneNumber = $phoneUtil->parse('+' . $phoneRaw, null);

            if ($phoneUtil->isValidNumber($phoneNumber)) {
                $phoneE164 = $phoneUtil->format($phoneNumber, \libphonenumber\PhoneNumberFormat::E164);
                $isValid = true;
            }
        } catch (NumberParseException $e) {
            // If parsing fails, keep phone_e164 as null and is_valid as false
        }

        return [
            'phone_raw' => $phoneRaw,
            'phone_e164' => $phoneE164,
            'is_valid' => $isValid,
        ];
    }

    /**
     * Parse full name into first and last name
     */
    public static function parseName(?string $fullName): array
    {
        if (empty($fullName)) {
            return [
                'first_name' => null,
                'last_name' => null,
            ];
        }

        $nameParts = explode(' ', trim($fullName), 2);

        return [
            'first_name' => $nameParts[0] ?? null,
            'last_name' => $nameParts[1] ?? null,
        ];
    }

    /**
     * Get display name (full_name or constructed from first/last name)
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->full_name) {
            return $this->full_name;
        }

        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));

        return $name ?: $this->phone_e164 ?? $this->phone_raw ?? 'Unknown';
    }
}
