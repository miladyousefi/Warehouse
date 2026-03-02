<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_table_id')->nullable()->constrained('restaurant_tables')->nullOnDelete();
            $table->string('order_code', 40)->unique();
            $table->enum('status', ['pending', 'confirmed', 'served', 'closed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->text('customer_note')->nullable();
            $table->string('source', 20)->default('qr');
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'payment_status'], 'ro_status_payment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_orders');
    }
};
