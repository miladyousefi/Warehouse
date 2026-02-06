<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add unit_price field to products table
        if (!Schema::hasColumn('products', 'unit_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('unit_price', 12, 2)->nullable()->after('track_quantity');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'unit_price')) {
                $table->dropColumn('unit_price');
            }
        });
    }
};
