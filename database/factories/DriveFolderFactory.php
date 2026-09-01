<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Drive\DriveFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriveFolder>
 */
class DriveFolderFactory extends Factory
{
    protected $model = DriveFolder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'name' => fake()->words(2, true),
            'owner_id' => User::factory(),
            'created_by' => fn (array $attributes) => $attributes['owner_id'],
        ];
    }
}
