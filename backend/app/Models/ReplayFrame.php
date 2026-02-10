<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplayFrame extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'session_replay_id',
        'chunk_index',
        'frame_type',
        'wireframe_data',
        'timestamp',
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'timestamp' => 'datetime:Y-m-d H:i:s.u',
        ];
    }

    /**
     * Get the session replay that owns the frame.
     */
    public function sessionReplay(): BelongsTo
    {
        return $this->belongsTo(SessionReplay::class);
    }

    /**
     * Get the decompressed wireframe data.
     */
    public function getWireframeAttribute(): ?array
    {
        if (!$this->wireframe_data) {
            return null;
        }

        $decompressed = gzdecode($this->wireframe_data);
        return $decompressed ? json_decode($decompressed, true) : null;
    }

    /**
     * Set the wireframe data (compresses automatically).
     */
    public function setWireframeAttribute(array $data): void
    {
        $this->attributes['wireframe_data'] = gzencode(json_encode($data), 9);
    }
}
