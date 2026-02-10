<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionReplay extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'session_id',
        'user_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'screen_count',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime:Y-m-d H:i:s.u',
            'ended_at' => 'datetime:Y-m-d H:i:s.u',
            'duration_seconds' => 'integer',
            'screen_count' => 'integer',
        ];
    }

    /**
     * Get the project that owns the replay.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the frames for the replay.
     */
    public function frames(): HasMany
    {
        return $this->hasMany(ReplayFrame::class)->orderBy('chunk_index');
    }
}
