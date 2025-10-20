<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Segment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'total_contacts',
        'valid_contacts',
        'invalid_contacts',
    ];

    protected $casts = [
        'total_contacts' => 'integer',
        'valid_contacts' => 'integer',
        'invalid_contacts' => 'integer',
    ];

    /**
     * Get the user that owns the segment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all recipients in this segment
     */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Recipient::class, 'recipient_segment')
            ->withTimestamps();
    }

    /**
     * Get campaigns using this segment
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Update contact counts
     */
    public function updateCounts(): void
    {
        $this->load('recipients');

        $valid = $this->recipients()->where('is_valid', true)->count();
        $invalid = $this->recipients()->where('is_valid', false)->count();

        $this->update([
            'total_contacts' => $this->recipients()->count(),
            'valid_contacts' => $valid,
            'invalid_contacts' => $invalid,
        ]);
    }

    /**
     * Get statistics for this segment
     */
    public function getStatistics(): array
    {
        $totalCampaigns = $this->campaigns()->count();
        $activeCampaigns = $this->campaigns()->whereIn('status', ['running', 'paused'])->count();
        $completedCampaigns = $this->campaigns()->where('status', 'finished')->count();

        $lastCampaign = $this->campaigns()->latest('created_at')->first();

        $totalMessagesSent = $this->campaigns()->sum('sent_count');
        $totalMessagesFailed = $this->campaigns()->sum('failed_count');

        return [
            'total_contacts' => $this->total_contacts,
            'valid_contacts' => $this->valid_contacts,
            'invalid_contacts' => $this->invalid_contacts,
            'total_campaigns' => $totalCampaigns,
            'active_campaigns' => $activeCampaigns,
            'completed_campaigns' => $completedCampaigns,
            'last_campaign_date' => $lastCampaign?->created_at?->format('M d, Y'),
            'total_messages_sent' => $totalMessagesSent,
            'total_messages_failed' => $totalMessagesFailed,
            'success_rate' => $totalMessagesSent + $totalMessagesFailed > 0
                ? round(($totalMessagesSent / ($totalMessagesSent + $totalMessagesFailed)) * 100, 2)
                : 0,
        ];
    }

}
