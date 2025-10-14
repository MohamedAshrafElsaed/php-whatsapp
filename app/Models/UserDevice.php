<?php
// app/Models/UserDevice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

class UserDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'ip_address',
        'user_agent',
        'browser',
        'platform',
        'is_mobile',
        'last_used_at',
    ];

    protected $casts = [
        'is_mobile' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate device fingerprint from request
     */
    public static function generateFingerprint(): string
    {
        $userAgent = request()->userAgent() ?? '';
        $ip = request()->ip() ?? '';

        // Create a unique fingerprint combining IP, user agent, and accept headers
        $acceptLanguage = request()->header('Accept-Language', '');
        $acceptEncoding = request()->header('Accept-Encoding', '');

        return md5($ip . $userAgent . $acceptLanguage . $acceptEncoding);
    }

    /**
     * Create or update device record for current request
     */
    public static function recordDevice(int $userId): self
    {
        $fingerprint = self::generateFingerprint();
        $agent = new Agent();

        $device = self::firstOrNew(['device_fingerprint' => $fingerprint]);

        $device->fill([
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'is_mobile' => $agent->isMobile(),
            'last_used_at' => now(),
        ]);

        $device->save();

        return $device;
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
