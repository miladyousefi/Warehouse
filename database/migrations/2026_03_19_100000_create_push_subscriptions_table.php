<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('push_subscriptions')) {
            Schema::create('push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('endpoint', 512);
                $table->string('public_key', 512);
                $table->string('auth_token', 255);
                $table->string('content_encoding', 50)->nullable();
                $table->string('user_agent', 1000)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->string('endpoint', 512)->change();
        });

        try {
            Schema::table('push_subscriptions', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'endpoint'],
                    'push_subscriptions_user_endpoint_unique',
                );
            });
        } catch (QueryException) {
            // The index may already exist if the migration was partially applied.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
