<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('drive_folders')->nullOnDelete();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'name']);
            $table->index('owner_id');
        });

        Schema::create('drive_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('drive_folders')->nullOnDelete();
            $table->string('name');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 191)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['folder_id', 'name']);
            $table->index('owner_id');
            $table->index('mime');
            $table->index('size');
        });

        Schema::create('drive_shares', function (Blueprint $table) {
            $table->id();
            $table->morphs('shareable');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission', 20);
            $table->foreignId('shared_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['shareable_type', 'shareable_id', 'user_id'], 'drive_shares_unique');
        });

        Schema::create('drive_share_links', function (Blueprint $table) {
            $table->id();
            $table->morphs('shareable');
            $table->string('token', 64)->unique();
            $table->string('password')->nullable();
            $table->string('permission', 20);
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_share_links');
        Schema::dropIfExists('drive_shares');
        Schema::dropIfExists('drive_files');
        Schema::dropIfExists('drive_folders');
    }
};
