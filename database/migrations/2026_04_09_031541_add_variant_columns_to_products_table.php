<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->longText('variant_images')->nullable();
            $table->string('variant_video', 500)->nullable();
            $table->longText('color')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->after('image_url');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['variant_images', 'variant_video', 'color', 'status']);
        });
    }
};
