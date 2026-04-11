<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coas', function (Blueprint $column) {
            $column->id();
            $column->string('name')->unique();
            $column->string('coa_no')->unique();

            // Foreign Key ke tabel category_coas
            $column->foreignId('coa_category_id')
                  ->constrained('category_coas')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            $column->bigInteger('amount')->nullable();
            $column->bigInteger('debit')->nullable();
            $column->bigInteger('credit')->nullable();
            $column->date('date')->nullable();
            $column->date('posted_date')->nullable();
            $column->boolean('posted')->default(0);
            $column->enum('type', ['Debit', 'Kredit']);
            $column->string('description')->nullable();
            $column->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coas');
    }
};
