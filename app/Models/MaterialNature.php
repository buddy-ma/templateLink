<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MaterialNatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialNature extends Model
{
    /** @use HasFactory<MaterialNatureFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function demands(): HasMany
    {
        return $this->hasMany(Demand::class);
    }
}
