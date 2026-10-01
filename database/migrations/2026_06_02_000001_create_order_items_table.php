<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items'); // لا يُحذف صنف عليه طلبات
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['order_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
