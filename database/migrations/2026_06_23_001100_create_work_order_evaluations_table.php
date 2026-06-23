<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_evaluations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('work_order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comments')->nullable();
            $table->timestamp('evaluated_at');
            $table->timestamps();

            $table->index(['evaluator_id', 'evaluated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_evaluations');
    }
};
