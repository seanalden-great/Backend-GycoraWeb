<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            // Opsional: Jika ingin ulasan untuk treatment juga
            $table->foreignId('clinic_treatment_id')->nullable()->constrained('clinic_treatments')->onDelete('cascade');
            $table->string('transaction_id')->nullable(); // Untuk memastikan dia benar-benar beli

            $table->tinyInteger('rating')->comment('1 to 5');
            $table->text('comment')->nullable();
            $table->string('image_url')->nullable(); // Foto ulasan dari S3
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};
