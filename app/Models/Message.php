<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'recipient_id',
        'user_id',
        'wa_session_id', // Added for multi-device support
        'phone_e164',
        'body_template',
        'body_rendered',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'error_code',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Recipient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the WhatsApp session/device that sent this message
     */
    public function waSession(): BelongsTo
    {
        return $this->belongsTo(WaSession::class, 'wa_session_id');
    }

    // Scopes
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeQueued($query)
    {
        return $query->where('status', 'queued');
    }

    public function scopeForCampaign($query, $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeForRecipient($query, $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by specific device/session
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('wa_session_id', $sessionId);
    }

    // Helper Methods
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorCode, string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_code' => $errorCode,
            'error_message' => substr($errorMessage, 0, 500),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'delivered_at' => now(),
        ]);
    }

    public function markAsRead(): void
    {
        $this->update([
            'read_at' => now(),
        ]);
    }

    /**
     * Get the device label that sent this message
     */
    public function getDeviceLabel(): ?string
    {
        return $this->waSession?->device_label;
    }

    /**
     * Check if message was sent from primary device
     */
    public function wasFromPrimaryDevice(): bool
    {
        return $this->waSession?->is_primary ?? false;
    }
}
