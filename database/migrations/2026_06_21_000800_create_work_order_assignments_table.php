<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_assignments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('work_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_staff_id')->constrained('staff_profiles')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->string('assignment_type');
            $table->text('remarks')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'unassigned_at'], 'wo_assignments_active_idx');
            $table->index(['assigned_staff_id', 'unassigned_at'], 'staff_assignments_active_idx');
            $table->index(['assigned_by', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_assignments');
    }
};
