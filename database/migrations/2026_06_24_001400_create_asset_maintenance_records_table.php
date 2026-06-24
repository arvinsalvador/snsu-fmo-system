<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenance_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_profile_id')->nullable()->constrained('staff_profiles')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('completion_date');
            $table->text('findings')->nullable();
            $table->text('actions_taken');
            $table->text('remarks')->nullable();
            $table->decimal('labor_cost', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'completion_date']);
            $table->index(['maintenance_schedule_id', 'completion_date'], 'asset_maint_schedule_date_idx');
            $table->index(['work_order_id', 'completion_date']);
            $table->index(['staff_profile_id', 'completion_date'], 'asset_maint_staff_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_records');
    }
};
