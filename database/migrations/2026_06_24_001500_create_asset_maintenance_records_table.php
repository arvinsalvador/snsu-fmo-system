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
            $table->foreignId('maintenance_type_id')->constrained()->restrictOnDelete();
            $table->date('maintenance_date');
            $table->string('performed_by');
            $table->text('remarks')->nullable();
            $table->text('findings')->nullable();
            $table->text('actions_taken')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['asset_id', 'maintenance_date'], 'asset_maint_asset_date_idx');
            $table->index(['maintenance_type_id', 'maintenance_date'], 'asset_maint_type_date_idx');
            $table->index('next_maintenance_date', 'asset_maint_next_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_records');
    }
};
