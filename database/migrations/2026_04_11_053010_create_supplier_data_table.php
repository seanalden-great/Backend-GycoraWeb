<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_data', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index('supplier_data_name_index');
            $table->string('no_telp')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('post_code')->nullable();
            $table->string('accountnumber')->nullable();
            $table->string('accountnumber_holders_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_data');
    }
};
