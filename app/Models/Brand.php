<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'dosage_form',
        'markers',
        'presentation',
        'ppv',
        'ph',
        'laboratory',
        'source_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ppv' => 'decimal:2',
            'ph' => 'decimal:2',
        ];
    }

    public function demands(): HasMany
    {
        return $this->hasMany(Demand::class);
    }

    public function displayLabel(): string
    {
        $parts = array_filter([
            $this->name,
            $this->dosage_form,
            $this->presentation,
        ]);

        return implode(' — ', $parts);
    }
}
