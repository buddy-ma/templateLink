<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Renames legacy products → brands for environments that already ran the old schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && ! Schema::hasTable('brands')) {
            Schema::rename('products', 'brands');
        }

        if (Schema::hasTable('demands') && Schema::hasColumn('demands', 'product_id') && ! Schema::hasColumn('demands', 'brand_id')) {
            Schema::table('demands', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });

            Schema::table('demands', function (Blueprint $table) {
                $table->renameColumn('product_id', 'brand_id');
            });

            Schema::table('demands', function (Blueprint $table) {
                $table->foreign('brand_id')->references('id')->on('brands')->restrictOnDelete();
            });
        }

        if (Schema::hasTable('demand_attachments') && ! Schema::hasColumn('demand_attachments', 'drive_file_id')) {
            Schema::table('demand_attachments', function (Blueprint $table) {
                $table->foreignId('drive_file_id')->nullable()->after('uploaded_by')->constrained('drive_files')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('demand_attachments') && Schema::hasColumn('demand_attachments', 'drive_file_id')) {
            Schema::table('demand_attachments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('drive_file_id');
            });
        }

        if (Schema::hasTable('demands') && Schema::hasColumn('demands', 'brand_id') && ! Schema::hasColumn('demands', 'product_id')) {
            Schema::table('demands', function (Blueprint $table) {
                $table->dropForeign(['brand_id']);
            });

            Schema::table('demands', function (Blueprint $table) {
                $table->renameColumn('brand_id', 'product_id');
            });

            Schema::table('demands', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('brands')->restrictOnDelete();
            });
        }

        if (Schema::hasTable('brands') && ! Schema::hasTable('products')) {
            Schema::rename('brands', 'products');
        }
    }
};
