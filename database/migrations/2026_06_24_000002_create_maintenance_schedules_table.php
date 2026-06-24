<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('frequency');
            $table->date('next_due_date');
            $table->date('last_completed_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['next_due_date', 'is_active']);
            $table->index(['frequency', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
