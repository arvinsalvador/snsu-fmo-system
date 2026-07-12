<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('report_type');
            $table->string('output_format')->default('pdf');
            $table->json('default_filters')->nullable();
            $table->json('included_sections');
            $table->string('orientation')->default('portrait');
            $table->string('paper_size')->default('a4');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['report_type', 'is_active']);
        });

        Schema::create('generated_reports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('report_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_type');
            $table->string('title');
            $table->date('reporting_period_start')->nullable();
            $table->date('reporting_period_end')->nullable();
            $table->json('filters')->nullable();
            $table->json('summary_data')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_format');
            $table->string('generation_status')->default('pending');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['generation_status', 'generated_at']);
            $table->index(['report_type', 'reporting_period_start']);
        });

        Schema::create('report_schedules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('report_template_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('frequency');
            $table->string('timezone')->default('Asia/Manila');
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->time('run_time');
            $table->string('date_range_mode');
            $table->json('custom_filters')->nullable();
            $table->string('output_format')->default('pdf');
            $table->string('delivery_method')->default('database_notification');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('processing_key')->nullable()->unique();
            $table->unsignedTinyInteger('consecutive_failures')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'next_run_at']);
        });

        Schema::create('report_schedule_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('delivery_channel')->default('database_notification');
            $table->foreignId('configured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['report_schedule_id', 'user_id', 'delivery_channel'], 'report_schedule_recipient_unique');
        });

        Schema::create('report_delivery_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('generated_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_type');
            $table->string('recipient_identifier');
            $table->string('delivery_channel');
            $table->string('delivery_status');
            $table->timestamp('delivered_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['generated_report_id', 'recipient_identifier', 'delivery_channel'], 'report_delivery_idempotency_unique');
        });

        Schema::create('report_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_audit_logs');
        Schema::dropIfExists('report_delivery_logs');
        Schema::dropIfExists('report_schedule_recipients');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('report_templates');
    }
};
