<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->string('approval_status')->default('pending')->after('status_id')->index();
            $table->timestamp('approved_at')->nullable()->after('completed_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });

        Schema::create('work_order_approvals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'created_at']);
            $table->index(['approver_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_approvals');

        Schema::table('work_orders', function (Blueprint $table): void {
            $table->dropColumn(['approval_status', 'approved_at', 'rejected_at']);
        });
    }
};
