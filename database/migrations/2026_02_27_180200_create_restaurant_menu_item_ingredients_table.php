<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_menu_item_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_menu_item_id')->constrained('restaurant_menu_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->timestamps();

            $table->unique(['restaurant_menu_item_id', 'product_id'], 'menu_item_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_menu_item_ingredients');
    }
};
