<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Drive\DriveFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends Factory<DriveFile>
 */
class DriveFileFactory extends Factory
{
    protected $model = DriveFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word().'.pdf';
        $path = 'drive/root/'.Str::uuid().'.pdf';

        return [
            'folder_id' => null,
            'name' => $name,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $name,
            'mime' => 'application/pdf',
            'size' => 1024,
            'owner_id' => User::factory(),
            'uploaded_by' => fn (array $attributes) => $attributes['owner_id'],
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (DriveFile $file): void {
            Storage::disk($file->disk)->put($file->path, 'pdf-content');
        })->afterCreating(function (DriveFile $file): void {
            if (! Storage::disk($file->disk)->exists($file->path)) {
                Storage::disk($file->disk)->put($file->path, 'pdf-content');
            }
        });
    }
}
