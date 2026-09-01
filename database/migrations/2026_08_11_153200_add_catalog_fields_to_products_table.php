<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = Schema::hasTable('brands') ? 'brands' : (Schema::hasTable('products') ? 'products' : null);
        if ($table === null || Schema::hasColumn($table, 'dosage_form')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->string('dosage_form')->nullable()->after('sku');
            $blueprint->string('markers')->nullable()->after('dosage_form');
            $blueprint->string('presentation')->nullable()->after('markers');
            $blueprint->decimal('ppv', 10, 2)->nullable()->after('presentation');
            $blueprint->decimal('ph', 10, 2)->nullable()->after('ppv');
            $blueprint->string('laboratory')->nullable()->after('ph');
            $blueprint->string('source_url', 500)->nullable()->after('laboratory');
        });
    }

    public function down(): void
    {
        $table = Schema::hasTable('brands') ? 'brands' : (Schema::hasTable('products') ? 'products' : null);
        if ($table === null || ! Schema::hasColumn($table, 'dosage_form')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropColumn([
                'dosage_form',
                'markers',
                'presentation',
                'ppv',
                'ph',
                'laboratory',
                'source_url',
            ]);
        });
    }
};
