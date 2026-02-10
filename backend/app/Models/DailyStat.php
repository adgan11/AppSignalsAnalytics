<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'date',
        'event_name',
        'event_count',
        'unique_users',
        'unique_devices',
        'unique_sessions',
        'country_code',
        'device_model',
        'app_version',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'event_count' => 'integer',
            'unique_users' => 'integer',
            'unique_devices' => 'integer',
            'unique_sessions' => 'integer',
        ];
    }

    /**
     * Get the project that owns the stat.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
