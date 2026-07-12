<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_maintenance_records', function (Blueprint $table): void {
            $table->string('review_status')->default('approved')->after('next_maintenance_date')->index();
            $table->foreignId('reviewed_by')->nullable()->after('review_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
            $table->foreignId('correction_requested_by')->nullable()->after('review_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('correction_requested_at')->nullable()->after('correction_requested_by');
            $table->text('correction_reason')->nullable()->after('correction_requested_at');
            $table->foreignId('corrected_by')->nullable()->after('correction_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('corrected_at')->nullable()->after('corrected_by');
            $table->text('rejection_reason')->nullable()->after('corrected_at');
            $table->timestamp('locked_at')->nullable()->after('rejection_reason');
            $table->index(['review_status', 'reviewed_at'], 'asset_maint_review_status_idx');
        });

        Schema::create('asset_maintenance_review_actions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('asset_maintenance_record_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->text('comments')->nullable();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acted_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['asset_maintenance_record_id', 'acted_at'], 'asset_maint_review_timeline_idx');
            $table->index(['action', 'acted_at'], 'asset_maint_review_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_review_actions');

        Schema::table('asset_maintenance_records', function (Blueprint $table): void {
            $table->dropIndex('asset_maint_review_status_idx');
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['correction_requested_by']);
            $table->dropForeign(['corrected_by']);
            $table->dropColumn([
                'review_status',
                'reviewed_by',
                'reviewed_at',
                'review_notes',
                'correction_requested_by',
                'correction_requested_at',
                'correction_reason',
                'corrected_by',
                'corrected_at',
                'rejection_reason',
                'locked_at',
            ]);
        });
    }
};
