<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use App\Models\ValidationPipeline;
use App\Models\ValidationPipelineStep;
use App\Services\Demand\DemandWorkflowService;
use Illuminate\Database\Seeder;

class DemandDemoSeeder extends Seeder
{
    public function run(): void
    {
        $rm = User::firstOrCreate(
            ['email' => 'marketing@example.com'],
            [
                'name' => 'Responsable Marketing',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $rm->syncRoles(['responsable_marketing']);

        $pm = User::firstOrCreate(
            ['email' => 'pm@example.com'],
            [
                'name' => 'Chef de projet',
                'password' => 'password',
                'email_verified_at' => now(),
                'manager_id' => $rm->id,
            ],
        );
        $pm->syncRoles(['project_manager']);
        $pm->update(['manager_id' => $rm->id]);

        $orderedValidators = [
            ['email' => 'agoumi@laprophan.com', 'name' => 'Dr Agoumi'],
            ['email' => 'ramses.afailal@laprophan.com', 'name' => 'Ramses'],
            ['email' => 'alexandre@laprophan.com', 'name' => 'Alexandre'],
            ['email' => 'abalil@laprophan.com', 'name' => 'Dr Abalil'],
        ];

        $validatorUsers = [];
        foreach ($orderedValidators as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );
            $user->update(['name' => $data['name']]);
            $user->syncRoles(['validator']);
            $validatorUsers[] = $user;
        }

        $reglementaires = [
            ['email' => 'regulatory@laprophan.com', 'name' => 'Réglementaire 1'],
            ['email' => 'regulatory2@laprophan.com', 'name' => 'Réglementaire 2'],
        ];

        foreach ($reglementaires as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );
            $user->syncRoles(['reglementaires']);
        }

        $biz = User::firstOrCreate(
            ['email' => 'bizdev@example.com'],
            [
                'name' => 'Abix Business Dev',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );
        $biz->syncRoles(['business_dev']);

        Brand::query()->firstOrCreate(
            ['sku' => 'BRAND-001'],
            ['name' => 'Marque démo', 'is_active' => true],
        );

        $this->call(MaterialNatureSeeder::class);

        $pipeline = ValidationPipeline::query()->firstOrCreate(
            ['is_default' => true],
            ['name' => 'Circuit standard'],
        );
        $pipeline->update(['name' => 'Circuit standard', 'is_default' => true]);
        $pipeline->steps()->delete();

        foreach ($validatorUsers as $index => $user) {
            ValidationPipelineStep::query()->create([
                'pipeline_id' => $pipeline->id,
                'user_id' => $user->id,
                'role_name' => null,
                'position' => $index + 1,
            ]);
        }

        ValidationPipelineStep::query()->create([
            'pipeline_id' => $pipeline->id,
            'user_id' => null,
            'role_name' => DemandWorkflowService::ROLE_REGLEMENTAIRES,
            'position' => count($validatorUsers) + 1,
        ]);
    }
}
