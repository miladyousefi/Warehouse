<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_price_histories')) {
            return;
        }

        // Rename old column only when needed.
        if (Schema::hasColumn('product_price_histories', 'price') && !Schema::hasColumn('product_price_histories', 'new_price')) {
            Schema::table('product_price_histories', function (Blueprint $table) {
                $table->renameColumn('price', 'new_price');
            });
        }

        // Add previous_price only when missing.
        if (!Schema::hasColumn('product_price_histories', 'previous_price')) {
            Schema::table('product_price_histories', function (Blueprint $table) {
                $table->decimal('previous_price', 10, 2)->nullable()->comment('Previous price before this change');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_price_histories')) {
            return;
        }

        if (Schema::hasColumn('product_price_histories', 'previous_price')) {
            Schema::table('product_price_histories', function (Blueprint $table) {
                $table->dropColumn('previous_price');
            });
        }

        if (Schema::hasColumn('product_price_histories', 'new_price') && !Schema::hasColumn('product_price_histories', 'price')) {
            Schema::table('product_price_histories', function (Blueprint $table) {
                $table->renameColumn('new_price', 'price');
            });
        }
    }
};
