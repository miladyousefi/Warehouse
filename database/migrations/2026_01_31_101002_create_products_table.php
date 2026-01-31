<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('name_tr');
            $table->string('name_en');
            $table->string('sku')->unique()->nullable();
            $table->string('barcode')->nullable()->index();
            $table->text('description_tr')->nullable();
            $table->text('description_en')->nullable();
            $table->decimal('min_stock', 15, 4)->default(0);
            $table->decimal('max_stock', 15, 4)->nullable();
            $table->decimal('cost_price', 15, 4)->nullable();
            $table->decimal('selling_price', 15, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('track_quantity')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
