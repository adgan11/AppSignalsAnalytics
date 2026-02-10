<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'key_hash',
        'key_prefix',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the project that owns the API key.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Generate a new API key.
     * 
     * @return array{key: string, model: ApiKey}
     */
    public static function generate(int $projectId, string $name): array
    {
        // Generate a secure random key: ok_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
        $key = 'ok_live_' . Str::random(32);

        $apiKey = static::create([
            'project_id' => $projectId,
            'name' => $name,
            'key_hash' => hash('sha256', $key),
            'key_prefix' => substr($key, 0, 12),
        ]);

        return [
            'key' => $key,  // Only returned once, never stored in plain text
            'model' => $apiKey,
        ];
    }

    /**
     * Validate an API key and return the associated model.
     */
    public static function validate(string $key): ?static
    {
        $hash = hash('sha256', $key);

        $apiKey = static::where('key_hash', $hash)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($apiKey) {
            $apiKey->update(['last_used_at' => now()]);
        }

        return $apiKey;
    }
}
