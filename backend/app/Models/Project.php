<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'bundle_id',
        'platform',
        'timezone',
        'data_retention_days',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'data_retention_days' => 'integer',
        ];
    }

    /**
     * Get the user that owns the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the API keys for the project.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get the events for the project.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the crashes for the project.
     */
    public function crashes(): HasMany
    {
        return $this->hasMany(Crash::class);
    }

    /**
     * Get the session replays for the project.
     */
    public function sessionReplays(): HasMany
    {
        return $this->hasMany(SessionReplay::class);
    }

    /**
     * Get the dSYM files for the project.
     */
    public function dsymFiles(): HasMany
    {
        return $this->hasMany(DsymFile::class);
    }
}
