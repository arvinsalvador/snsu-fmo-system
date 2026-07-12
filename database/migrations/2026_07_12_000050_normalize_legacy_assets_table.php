<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('assets', 'asset_code') && ! Schema::hasColumn('assets', 'asset_tag')) {
            Schema::table('assets', function (Blueprint $table): void {
                $table->renameColumn('asset_code', 'asset_tag');
            });
        }

        if (Schema::hasColumn('assets', 'exact_location') && ! Schema::hasColumn('assets', 'location')) {
            Schema::table('assets', function (Blueprint $table): void {
                $table->renameColumn('exact_location', 'location');
            });
        }

        if (! Schema::hasColumn('assets', 'name')) {
            Schema::table('assets', function (Blueprint $table): void {
                $table->string('name')->nullable()->after('asset_tag');
            });

            DB::table('assets')->whereNull('name')->update(['name' => DB::raw('asset_tag')]);
        }

        DB::table('assets')->update([
            'status' => DB::raw("CASE
                WHEN LOWER(REPLACE(status, ' ', '_')) = 'under_maintenance' THEN 'under_maintenance'
                WHEN LOWER(status) IN ('active', 'inactive', 'defective', 'lost', 'disposed', 'retired') THEN LOWER(status)
                ELSE 'inactive'
            END"),
        ]);
    }

    public function down(): void
    {
        // Legacy asset columns are intentionally not restored because doing so could discard normalized data.
    }
};
