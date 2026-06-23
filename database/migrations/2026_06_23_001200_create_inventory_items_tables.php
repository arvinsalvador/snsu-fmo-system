<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('item_code')->unique();
            $table->foreignId('category_id')->constrained('inventory_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('unit', 50);
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category_id', 'status']);
            $table->index('name');
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('movement_type');
            $table->decimal('quantity', 12, 2);
            $table->decimal('stock_before', 12, 2);
            $table->decimal('stock_after', 12, 2);
            $table->text('remarks')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['inventory_item_id', 'occurred_at']);
            $table->index('movement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
    }
};
