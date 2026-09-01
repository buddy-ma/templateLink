<?php

namespace Database\Factories;

use App\Enums\DemandStatus;
use App\Models\Brand;
use App\Models\Demand;
use App\Models\MaterialNature;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Demand>
 */
class DemandFactory extends Factory
{
    protected $model = Demand::class;

    public function definition(): array
    {
        return [
            'reference' => 'DEM-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'created_by' => User::factory(),
            'brand_id' => Brand::factory(),
            'material_nature_id' => MaterialNature::factory(),
            'description' => fake()->paragraph(),
            'status' => DemandStatus::Draft,
            'current_step' => null,
        ];
    }
}
