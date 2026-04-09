<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('carts', function (Blueprint $table) {
            // 1. Drop Foreign Keys terlebih dahulu agar MySQL melepaskan cengkeramannya
            $table->dropForeign(['user_id']);
            $table->dropForeign(['product_id']);

            // 2. Sekarang aman untuk mendrop Unique Index lama
            $table->dropUnique('carts_user_product_unique');

            // 3. Tambahkan kolom baru
            $table->string('color', 50)->nullable()->after('product_id');

            // 4. Buat Unique Index baru yang mencakup color
            $table->unique(['user_id', 'product_id', 'color'], 'carts_user_product_color_unique');

            // 5. Pasang kembali Foreign Keys yang tadi dilepas
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('carts', function (Blueprint $table) {
            // 1. Drop Foreign Keys
            $table->dropForeign(['user_id']);
            $table->dropForeign(['product_id']);

            // 2. Drop Unique Index baru
            $table->dropUnique('carts_user_product_color_unique');

            // 3. Drop kolom
            $table->dropColumn('color');

            // 4. Kembalikan Unique Index lama
            $table->unique(['user_id', 'product_id'], 'carts_user_product_unique');

            // 5. Kembalikan Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }
};
