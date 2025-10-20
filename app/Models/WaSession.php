<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_label',
        'bridge_instance_url',
        'bridge_instance_port',
        'is_primary',
        'status',
        'meta_json',
        'last_seen_at',
        'last_heartbeat_at',
        'expires_at',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'is_primary' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'wa_session_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'wa_session_id');
    }

    // Scopes
    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Get full bridge URL with port path
     */
    public function getBridgeUrl(): string
    {
//        return 'http://localhost:3001';

        if ($this->bridge_instance_url && $this->bridge_instance_port) {
            return rtrim($this->bridge_instance_url, '/') . '/port/' . $this->bridge_instance_port;
        }

        // Fallback to default
        return rtrim(config('services.bridge.url'), '/') . '/port/3001';
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function getPhoneNumber(): ?string
    {
        return $this->meta_json['phone'] ?? null;
    }

    public function getName(): ?string
    {
        return $this->meta_json['name'] ?? null;
    }

    public function updateConnection(array $data): void
    {
        $this->update([
            'status' => 'connected',
            'meta_json' => array_merge($this->meta_json ?? [], $data),
            'last_seen_at' => now(),
            'last_heartbeat_at' => now(),
        ]);
    }

    /**
     * Get all contacts for this session
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function canReconnect(): bool
    {
        return in_array($this->status, ['pending', 'expired', 'disconnected', 'failed']);
    }

    public function prepareForReconnection(): void
    {
        $this->update([
            'status' => 'pending',
            'expires_at' => null,
            'last_seen_at' => null,
            'last_heartbeat_at' => null,
            'meta_json' => array_merge($this->meta_json ?? [], [
                'reconnection_attempt' => ($this->meta_json['reconnection_attempt'] ?? 0) + 1,
                'last_reconnection_at' => now()->toISOString(),
            ]),
        ]);
    }
}
