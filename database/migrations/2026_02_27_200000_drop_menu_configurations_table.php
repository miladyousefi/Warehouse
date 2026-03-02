<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('menu_configurations');
    }

    public function down(): void
    {
        // Intentionally left empty. Manage Menu feature was removed.
    }
};
