<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    /**
     * Disable Laravel timestamps - we use custom timestamp columns.
     */
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'event_id',
        'event_name',
        'user_id',
        'device_id',
        'session_id',
        'properties',
        'os_version',
        'device_model',
        'app_version',
        'country_code',
        'event_timestamp',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'event_timestamp' => 'datetime:Y-m-d H:i:s.u',
            'received_at' => 'datetime:Y-m-d H:i:s.u',
        ];
    }

    /**
     * Get the project that owns the event.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope for filtering by event name.
     */
    public function scopeOfType($query, string $eventName)
    {
        return $query->where('event_name', $eventName);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('event_timestamp', [$start, $end]);
    }
}
