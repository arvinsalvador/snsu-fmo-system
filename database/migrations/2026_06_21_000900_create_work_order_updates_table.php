<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_updates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('work_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff_profiles')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('work_order_statuses')->restrictOnDelete();
            $table->text('notes');
            $table->unsignedInteger('estimated_remaining_days')->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'created_at']);
            $table->index(['staff_id', 'created_at']);
        });

        Schema::create('work_order_update_photos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('work_order_update_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('file_path', 2048);
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('caption', 500)->nullable();
            $table->timestamps();

            $table->index(['work_order_update_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_update_photos');
        Schema::dropIfExists('work_order_updates');
    }
};
