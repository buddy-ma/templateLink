<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ValidationPipelineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValidationPipeline extends Model
{
    /** @use HasFactory<ValidationPipelineFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ValidationPipelineStep::class, 'pipeline_id')->orderBy('position');
    }

    public static function defaultPipeline(): ?self
    {
        return static::query()
            ->where('is_default', true)
            ->with('steps.user')
            ->first();
    }
}
