<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->uuid('qr_token')->nullable()->unique()->after('uuid');
            $table->index('updated_at', 'assets_mobile_updated_idx');
        });
        DB::table('assets')->whereNull('qr_token')->orderBy('id')->eachById(fn ($asset) => DB::table('assets')->where('id', $asset->id)->update(['qr_token' => (string) Str::uuid()]));
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->index('updated_at', 'work_orders_mobile_updated_idx');
        });
        Schema::create('api_idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('method', 10);
            $table->string('path');
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'key']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_idempotency_keys');
        Schema::table('work_orders', fn (Blueprint $t) => $t->dropIndex('work_orders_mobile_updated_idx'));
        Schema::table('assets', function (Blueprint $t) {
            $t->dropIndex('assets_mobile_updated_idx');
            $t->dropUnique(['qr_token']);
            $t->dropColumn('qr_token');
        });
    }
};
