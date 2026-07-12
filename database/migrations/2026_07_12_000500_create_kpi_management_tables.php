<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_definitions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('metric_key')->unique();
            $table->string('calculation_type');
            $table->string('unit');
            $table->string('direction');
            $table->string('aggregation_period')->default('monthly');
            $table->string('data_source')->default('reporting_service');
            $table->string('scope_type')->default('organization');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category', 'is_active']);
        });
        Schema::create('kpi_targets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('kpi_definition_id')->constrained()->restrictOnDelete();
            $table->string('scope_type')->default('organization');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('target_value', 16, 4)->nullable();
            $table->decimal('minimum_value', 16, 4)->nullable();
            $table->decimal('maximum_value', 16, 4)->nullable();
            $table->decimal('warning_threshold', 16, 4)->nullable();
            $table->decimal('critical_threshold', 16, 4)->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->string('current_evaluation_status')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'period_start', 'period_end']);
            $table->index(['scope_type', 'scope_id']);
            $table->index('owner_user_id');
        });
        Schema::create('kpi_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('kpi_target_id')->constrained()->cascadeOnDelete();
            $table->date('evaluation_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('actual_value', 16, 4)->nullable();
            $table->decimal('target_value', 16, 4)->nullable();
            $table->decimal('variance', 16, 4)->nullable();
            $table->decimal('achievement_percentage', 10, 2)->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('status');
            $table->string('trend_direction')->nullable();
            $table->json('source_summary')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('calculated_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['kpi_target_id', 'evaluation_date']);
            $table->index(['status', 'evaluation_date']);
        });
        Schema::create('kpi_corrective_actions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('kpi_target_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_evaluation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->text('root_cause')->nullable();
            $table->text('planned_action');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->timestamp('due_soon_notified_at')->nullable();
            $table->timestamp('overdue_notified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'due_date']);
            $table->index('assigned_to');
        });
        Schema::create('kpi_corrective_action_evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kpi_corrective_action_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_corrective_action_evidence');
        Schema::dropIfExists('kpi_corrective_actions');
        Schema::dropIfExists('kpi_evaluations');
        Schema::dropIfExists('kpi_targets');
        Schema::dropIfExists('kpi_definitions');
    }
};
