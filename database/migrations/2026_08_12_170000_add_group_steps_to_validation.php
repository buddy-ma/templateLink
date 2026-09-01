<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('validation_pipeline_steps')) {
            $this->dropUniqueIfExists('validation_pipeline_steps', 'validation_pipeline_steps_pipeline_id_user_id_unique');

            if (! Schema::hasColumn('validation_pipeline_steps', 'role_name')) {
                Schema::table('validation_pipeline_steps', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });

                Schema::table('validation_pipeline_steps', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                    $table->string('role_name', 80)->nullable()->after('user_id');
                });

                Schema::table('validation_pipeline_steps', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                });
            }
        }

        if (Schema::hasTable('demand_validators')) {
            $this->dropUniqueIfExists('demand_validators', 'demand_validators_demand_id_user_id_unique');

            if (! Schema::hasColumn('demand_validators', 'role_name')) {
                Schema::table('demand_validators', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });

                Schema::table('demand_validators', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                    $table->string('role_name', 80)->nullable()->after('user_id');
                });

                Schema::table('demand_validators', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                });
            }

            if (! Schema::hasColumn('demand_validators', 'acted_by')) {
                Schema::table('demand_validators', function (Blueprint $table) {
                    $table->foreignId('acted_by')->nullable()->after('comment')->constrained('users')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('demand_validators') && Schema::hasColumn('demand_validators', 'acted_by')) {
            Schema::table('demand_validators', function (Blueprint $table) {
                $table->dropConstrainedForeignId('acted_by');
            });
        }

        if (Schema::hasTable('demand_validators') && Schema::hasColumn('demand_validators', 'role_name')) {
            Schema::table('demand_validators', function (Blueprint $table) {
                $table->dropColumn('role_name');
            });
        }

        if (Schema::hasTable('validation_pipeline_steps') && Schema::hasColumn('validation_pipeline_steps', 'role_name')) {
            Schema::table('validation_pipeline_steps', function (Blueprint $table) {
                $table->dropColumn('role_name');
            });
        }
    }

    private function dropUniqueIfExists(string $table, string $index): void
    {
        $sm = Schema::getConnection()->getSchemaBuilder();
        $indexes = $sm->getIndexes($table);

        foreach ($indexes as $meta) {
            if (($meta['name'] ?? null) === $index) {
                Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                    $blueprint->dropUnique($index);
                });

                return;
            }
        }
    }
};
