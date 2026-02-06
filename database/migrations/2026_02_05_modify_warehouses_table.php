<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Drop the code column if it exists and make it nullable
            if (Schema::hasColumn('warehouses', 'code')) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            }
            // Add it back as nullable
            $table->string('code', 20)->nullable()->after('name_en');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('warehouses', 'code')) {
                $table->dropColumn('code');
            }
            $table->string('code', 20)->unique();
        });
    }
};
