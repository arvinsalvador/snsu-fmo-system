<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_maintenance_records', function (Blueprint $table): void {
            $table->foreignId('maintenance_type_id')->nullable()->after('asset_id')->constrained('maintenance_types')->nullOnDelete();
            $table->date('maintenance_date')->nullable()->after('completed_by');
            $table->string('performed_by')->nullable()->after('maintenance_date');
            $table->date('next_maintenance_date')->nullable()->after('labor_cost');
            $table->decimal('total_cost', 12, 2)->nullable()->after('labor_cost');
            $table->softDeletes();

            $table->index(['maintenance_type_id', 'maintenance_date'], 'asset_maint_type_date_idx');
            $table->index('next_maintenance_date', 'asset_maint_next_date_idx');
        });

        Schema::table('maintenance_schedules', function (Blueprint $table): void {
            $table->softDeletes();
        });

        DB::table('asset_maintenance_records')
            ->whereNull('maintenance_date')
            ->update(['maintenance_date' => DB::raw('completion_date')]);
        DB::table('asset_maintenance_records')
            ->whereNull('total_cost')
            ->update(['total_cost' => DB::raw('labor_cost')]);
    }

    public function down(): void
    {
        Schema::table('maintenance_schedules', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('asset_maintenance_records', function (Blueprint $table): void {
            $table->dropForeign(['maintenance_type_id']);
            $table->dropIndex('asset_maint_type_date_idx');
            $table->dropIndex('asset_maint_next_date_idx');
            $table->dropColumn([
                'maintenance_type_id',
                'maintenance_date',
                'performed_by',
                'next_maintenance_date',
                'total_cost',
                'deleted_at',
            ]);
        });
    }
};
