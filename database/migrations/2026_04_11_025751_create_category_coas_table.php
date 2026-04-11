<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_coas', function (Blueprint $column) {
            $column->id();
            $column->string('category_name')->unique();
            $column->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_coas');
    }
};
