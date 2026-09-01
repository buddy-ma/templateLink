<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('material_natures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('validation_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('validation_pipeline_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('validation_pipelines')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('role_name', 80)->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['pipeline_id', 'position']);
        });

        Schema::create('demands', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('brands')->restrictOnDelete();
            $table->foreignId('material_nature_id')->constrained('material_natures')->restrictOnDelete();
            $table->text('description');
            $table->string('status', 40)->default('draft');
            $table->unsignedSmallInteger('current_step')->nullable();
            $table->text('refused_reason')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_by']);
            $table->index('current_step');
        });

        Schema::create('demand_validators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_id')->constrained('demands')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('role_name', 80)->nullable();
            $table->unsignedSmallInteger('position');
            $table->string('status', 20)->default('pending');
            $table->timestamp('acted_at')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['demand_id', 'position']);
        });

        Schema::create('demand_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_id')->constrained('demands')->cascadeOnDelete();
            $table->string('collection', 40);
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['demand_id', 'collection']);
        });

        Schema::create('demand_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_id')->constrained('demands')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 60);
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->unsignedSmallInteger('step')->nullable();
            $table->text('comment')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_events');
        Schema::dropIfExists('demand_attachments');
        Schema::dropIfExists('demand_validators');
        Schema::dropIfExists('demands');
        Schema::dropIfExists('validation_pipeline_steps');
        Schema::dropIfExists('validation_pipelines');
        Schema::dropIfExists('material_natures');
        Schema::dropIfExists('brands');
    }
};
