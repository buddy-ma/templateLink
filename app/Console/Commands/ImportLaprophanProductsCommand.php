<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLaprophanProductsCommand extends Command
{
    protected $signature = 'products:import-laprophan
                            {path? : Path to the CSV file}
                            {--fresh : Delete existing catalog SKUs (LAP-*) before import}';

    protected $description = 'Import Laprophan products catalog CSV into the brands table';

    public function handle(): int
    {
        $path = $this->argument('path')
            ?? base_path('Laprophan_Products_Catalog - Products.csv');

        if (! is_file($path)) {
            $this->error("CSV not found: {$path}");

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $deleted = Brand::query()
                ->where('sku', 'like', 'LAP-%')
                ->whereDoesntHave('demands')
                ->delete();
            $this->info("Removed {$deleted} existing LAP-* brands unused by demands.");
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error('Unable to open CSV.');

            return self::FAILURE;
        }

        // Skip title + subtitle rows, then read header.
        fgetcsv($handle);
        fgetcsv($handle);
        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);
            $this->error('CSV header missing.');

            return self::FAILURE;
        }

        $imported = 0;
        $updated = 0;

        DB::transaction(function () use ($handle, &$imported, &$updated): void {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 2 || trim((string) $row[0]) === '' || ! is_numeric(trim((string) $row[0]))) {
                    continue;
                }

                $catalogNumber = (int) trim((string) $row[0]);
                $name = trim((string) ($row[1] ?? ''));
                if ($name === '') {
                    continue;
                }

                $sku = sprintf('LAP-%03d', $catalogNumber);
                $payload = [
                    'name' => $name,
                    'dosage_form' => $this->nullableString($row[2] ?? null),
                    'markers' => $this->nullableString($row[3] ?? null),
                    'presentation' => $this->nullableString($row[4] ?? null),
                    'ppv' => $this->nullableDecimal($row[5] ?? null),
                    'ph' => $this->nullableDecimal($row[6] ?? null),
                    'laboratory' => $this->nullableString($row[7] ?? null) ?? 'LAPROPHAN',
                    'source_url' => $this->nullableString($row[8] ?? null),
                    'is_active' => true,
                ];

                $brand = Brand::query()->updateOrCreate(
                    ['sku' => $sku],
                    $payload,
                );

                if ($brand->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }
            }
        });

        fclose($handle);

        $this->info("Import complete: {$imported} created, {$updated} updated.");

        return self::SUCCESS;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $value);
        if (! is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }
}
