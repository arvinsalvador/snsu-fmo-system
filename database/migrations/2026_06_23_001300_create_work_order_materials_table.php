<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_materials', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('work_order_id')->constrained('work_orders')->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('quantity_requested', 12, 2)->default(0);
            $table->decimal('quantity_issued', 12, 2)->default(0);
            $table->decimal('quantity_used', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['work_order_id', 'inventory_item_id']);
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_materials');
    }
};
