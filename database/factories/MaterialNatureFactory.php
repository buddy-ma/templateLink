<?php

namespace Database\Factories;

use App\Models\MaterialNature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialNature>
 */
class MaterialNatureFactory extends Factory
{
    protected $model = MaterialNature::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
