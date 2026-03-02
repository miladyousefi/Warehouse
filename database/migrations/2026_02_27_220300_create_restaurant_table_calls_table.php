<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_table_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_table_id')->constrained('restaurant_tables')->cascadeOnDelete();
            $table->enum('status', ['pending', 'handled'])->default('pending');
            $table->string('note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_table_id', 'status'], 'rtc_table_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_table_calls');
    }
};
