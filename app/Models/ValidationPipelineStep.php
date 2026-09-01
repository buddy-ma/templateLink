<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationPipelineStep extends Model
{
    protected $fillable = [
        'pipeline_id',
        'user_id',
        'role_name',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(ValidationPipeline::class, 'pipeline_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isGroupStep(): bool
    {
        return filled($this->role_name);
    }
}
