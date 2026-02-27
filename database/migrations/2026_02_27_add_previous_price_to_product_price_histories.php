<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_price_histories', function (Blueprint $table) {
            $table->decimal('previous_price', 10, 2)->nullable()->comment('Previous price before this change');
            // Rename price to new_price for clarity
            $table->renameColumn('price', 'new_price');
        });
    }

    public function down(): void
    {
        Schema::table('product_price_histories', function (Blueprint $table) {
            $table->dropColumn('previous_price');
            $table->renameColumn('new_price', 'price');
        });
    }
};
