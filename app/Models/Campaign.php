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
        'import_id',
        'name',
        'message_template',
        'variables_json',
        'status',
        'throttling_cfg_json',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'variables_json' => 'array',
        'throttling_cfg_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canStart(): bool
    {
        return in_array($this->status, ['draft', 'paused']);
    }
}
