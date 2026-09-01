<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('demand_attachments') || Schema::hasColumn('demand_attachments', 'demand_event_id')) {
            return;
        }

        Schema::table('demand_attachments', function (Blueprint $table) {
            $table->foreignId('demand_event_id')
                ->nullable()
                ->after('drive_file_id')
                ->constrained('demand_events')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('demand_attachments') || ! Schema::hasColumn('demand_attachments', 'demand_event_id')) {
            return;
        }

        Schema::table('demand_attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('demand_event_id');
        });
    }
};
