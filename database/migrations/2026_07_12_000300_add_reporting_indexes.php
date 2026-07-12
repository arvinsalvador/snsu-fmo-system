<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->index(['status_id', 'requested_at'], 'wo_report_status_date_idx');
            $table->index(['building_id', 'requested_at'], 'wo_report_building_date_idx');
        });
        Schema::table('asset_maintenance_records', function (Blueprint $table): void {
            $table->index(['review_status', 'maintenance_date'], 'asset_maint_review_date_idx');
        });
        Schema::table('work_order_materials', function (Blueprint $table): void {
            $table->index(['inventory_item_id', 'issued_at'], 'wo_material_item_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->dropIndex('wo_report_status_date_idx');
            $table->dropIndex('wo_report_building_date_idx');
        });
        Schema::table('asset_maintenance_records', function (Blueprint $table): void {
            $table->dropIndex('asset_maint_review_date_idx');
        });
        Schema::table('work_order_materials', function (Blueprint $table): void {
            $table->dropIndex('wo_material_item_date_idx');
        });
    }
};
