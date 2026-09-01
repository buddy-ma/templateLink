<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demands', function (Blueprint $table) {
            $table->foreignId('manager_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('manager_approved_at')
                ->nullable()
                ->after('manager_id');
            $table->index(['status', 'manager_id']);
        });
    }

    public function down(): void
    {
        Schema::table('demands', function (Blueprint $table) {
            $table->dropIndex(['status', 'manager_id']);
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn('manager_approved_at');
        });
    }
};
