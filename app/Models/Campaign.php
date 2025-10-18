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
        'wa_session_id', // NEW: Links to specific WhatsApp device
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
    ];

    protected $casts = [
        'variables_json' => 'array',
        'throttling_cfg_json' => 'array',
        'settings_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function waSession(): BelongsTo
    {
        return $this->belongsTo(WaSession::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
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

    // Helper Methods
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isDraft(): bool
    {
        return in_array($this->status, ['draft', 'pending']);
    }

    public function canStart(): bool
    {
        return in_array($this->status, ['draft', 'pending', 'paused'])
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

        return round(($this->sent_count / $this->total_recipients) * 100, 2);
    }
}
