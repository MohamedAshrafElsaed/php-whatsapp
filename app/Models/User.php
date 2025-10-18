<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'industry',
        'password',
        'country_code',
        'phone',
        'phone_verified',
        'wa_auto_reply_enabled',
        'wa_auto_reply_message',
        'wa_auto_mark_read',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'wa_auto_reply_enabled' => 'boolean',
        'wa_auto_mark_read' => 'boolean',
    ];

    /**
     * @return HasOne
     */
    public function waSession(): HasOne
    {
        return $this->hasOne(WaSession::class)->latestOfMany();
    }

    /**
     * @return HasMany
     */
    public function waSessions(): HasMany
    {
        return $this->hasMany(WaSession::class);
    }

    /**
     * @return HasMany
     */
    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    /**
     * @return HasMany
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(Recipient::class);
    }

    /**
     * @return HasMany
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasMany
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function otps(): HasMany
    {
        return $this->hasMany(Otp::class, 'phone', 'phone')
            ->where('country_code', $this->country_code);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Get the user's full name
     */
    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the full phone number with country code
     */
    public function getFullPhoneAttribute(): string
    {
        return $this->country_code . $this->phone;
    }

    /**
     * Check if user has a connected WhatsApp session
     */
    public function hasConnectedWhatsApp(): bool
    {
        return $this->waSession && $this->waSession->isConnected();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
