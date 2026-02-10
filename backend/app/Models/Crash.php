<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Crash extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'crash_id',
        'crash_group_hash',
        'user_id',
        'device_id',
        'session_id',
        'exception_type',
        'exception_message',
        'stack_trace',
        'is_symbolicated',
        'symbolicated_trace',
        'os_version',
        'device_model',
        'app_version',
        'app_build',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'is_symbolicated' => 'boolean',
            'occurred_at' => 'datetime:Y-m-d H:i:s.u',
        ];
    }

    /**
     * Get the project that owns the crash.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Generate a group hash for similar crashes.
     */
    public static function generateGroupHash(string $exceptionType, string $stackTrace): string
    {
        // Extract the first few frames for grouping
        $lines = array_slice(explode("\n", $stackTrace), 0, 5);
        $signature = $exceptionType . '|' . implode('|', $lines);

        return hash('sha256', $signature);
    }

    /**
     * Scope for unsymbolicated crashes.
     */
    public function scopeUnsymbolicated($query)
    {
        return $query->where('is_symbolicated', false);
    }

    /**
     * Scope to get crash groups with counts.
     */
    public function scopeGrouped($query)
    {
        return $query->selectRaw('crash_group_hash, exception_type, exception_message, MAX(occurred_at) as last_occurred, COUNT(*) as crash_count')
            ->groupBy('crash_group_hash', 'exception_type', 'exception_message')
            ->orderByDesc('last_occurred');
    }
}
