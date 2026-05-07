<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: "Illuma Market"
            $table->string('location'); // Contoh: "PIK Avenue"
            $table->date('start_date'); // Contoh: "2026-05-06"
            $table->date('end_date')->nullable(); // Contoh: "2026-05-17" (Jika event hanya 1 hari, bisa disamakan dengan start_date)
            $table->text('description'); // Isi teks panjang
            $table->string('image_url')->nullable(); // Gambar cover event
            $table->string('link_url')->nullable(); // Link untuk tombol [Visit Event]
            $table->boolean('is_active')->default(true); // Untuk menyembunyikan event tanpa menghapusnya
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
};
