<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            // `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT
            $table->id();

            // `user_id` bigint(20) unsigned NOT NULL
            // FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            // `product_id` bigint(20) unsigned NOT NULL
            $table->foreignId('product_id');

            // `created_at` & `updated_at` timestamp NULL DEFAULT NULL
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
