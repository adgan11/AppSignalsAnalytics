<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DsymFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'uuid',
        'app_version',
        'build_number',
        'file_path',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * Get the project that owns the dSYM file.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
