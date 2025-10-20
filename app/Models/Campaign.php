<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wa_session_id',
        'import_id',
        'name',
        'message_template',
        'variables_json',
        'status',
        'throttling_cfg_json',
        'started_at',
        'finished_at',
        'scheduled_at',
        'completed_at',
        'total_recipients',
        'sent_count',
        'failed_count',
        'settings_json',
        'message_type',
        'media_path',
        'media_filename',
        'media_mime_type',
        'caption',
        'link_url',
        'latitude',
        'longitude',
        'contact_name',
        'contact_phone',
        'poll_question',
        'poll_options',
        'poll_max_answer',
    ];

    protected $casts = [
        'variables_json' => 'array',
        'throttling_cfg_json' => 'array',
        'settings_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'total_recipients' => 'integer',
        'poll_options' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the full URL for media file
     */
    public function getMediaUrlAttribute(): ?string
    {
        if (!$this->media_path) {
            return null;
        }

        return Storage::disk('public')->url($this->media_path);
    }

    /**
     * Get the user that owns the campaign
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the import associated with the campaign
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    /**
     * Get the WhatsApp session (device) used for this campaign
     */
    public function waSession(): BelongsTo
    {
        return $this->belongsTo(WaSession::class, 'wa_session_id');
    }

    /**
     * Get the segment associated with the campaign
     */
    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    /**
     * Get all messages for this campaign
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get all recipients for this campaign (through import)
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(Recipient::class, 'import_id', 'import_id');
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'running', 'paused']);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }

    // Helper Methods
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isDraft(): bool
    {
        return in_array($this->status, ['draft', 'pending']);
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function canStart(): bool
    {
        return in_array($this->status, ['draft', 'pending', 'paused'])
            && $this->wa_session_id
            && $this->waSession
            && $this->waSession->isConnected();
    }

    public function canPause(): bool
    {
        return $this->status === 'running';
    }

    public function canResume(): bool
    {
        return $this->status === 'paused'
            && $this->wa_session_id
            && $this->waSession
            && $this->waSession->isConnected();
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['pending', 'running', 'paused']);
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_recipients == 0) {
            return 0;
        }

        $totalProcessed = $this->sent_count + $this->failed_count;
        return round(($totalProcessed / $this->total_recipients) * 100, 2);
    }

    public function getSentPercentage(): float
    {
        if ($this->total_recipients == 0) {
            return 0;
        }

        return round(($this->sent_count / $this->total_recipients) * 100, 2);
    }

    public function getFailedPercentage(): float
    {
        if ($this->total_recipients == 0) {
            return 0;
        }

        return round(($this->failed_count / $this->total_recipients) * 100, 2);
    }

    public function getRemainingCount(): int
    {
        return max(0, $this->total_recipients - $this->sent_count - $this->failed_count);
    }

    /**
     * Get the throttling configuration with defaults
     */
    public function getThrottlingConfig(): array
    {
        return array_merge([
            'messages_per_minute' => 15,
            'delay_between_messages' => 4, // seconds
            'batch_size' => 50,
            'delay_between_batches' => 60, // seconds
        ], $this->throttling_cfg_json ?? []);
    }

    /**
     * Boot method to set default values
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            // Set default throttling if not provided
            if (empty($campaign->throttling_cfg_json)) {
                $campaign->throttling_cfg_json = [
                    'messages_per_minute' => 15,
                    'delay_between_messages' => 4,
                    'batch_size' => 50,
                    'delay_between_batches' => 60,
                ];
            }
        });
    }
}
