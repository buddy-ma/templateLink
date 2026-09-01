<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MaterialNature;
use Illuminate\Database\Seeder;

class MaterialNatureSeeder extends Seeder
{
    public function run(): void
    {
        $natures = [
            'Fiche poso',
            'Roll-up',
            'EADV',
            'Brochure',
            'Flyer',
            'Affiche',
            'Kakemono',
            'PLV',
            'Présentoir',
            'Chevalet',
            'Goodies',
            'Vidéo',
            'Digital',
        ];

        foreach ($natures as $name) {
            MaterialNature::query()->firstOrCreate(['name' => $name]);
        }
    }
}
