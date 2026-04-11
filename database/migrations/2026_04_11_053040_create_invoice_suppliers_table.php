<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('no_invoice')->unique();

            // Foreign Key ke supplier_data
            $table->foreignId('supplier_id')
                  ->constrained('supplier_data')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->bigInteger('amount');
            $table->integer('pph')->nullable();
            $table->integer('pph_percentage')->nullable();
            $table->dateTime('date')->default('2025-04-10 08:56:15');
            $table->string('nota')->nullable();
            $table->dateTime('deadline_invoice')->default('2025-04-10 08:56:15');
            $table->enum('payment_status', ['Paid', 'Not Yet'])->default('Not Yet');
            $table->string('payment_method')->nullable();

            // Foreign Keys ke tabel coas (Asumsi tabel coas sudah ada)
            $table->foreignId('kredit_coa_id')->nullable()->constrained('coas')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('debit_coa_id')->nullable()->constrained('coas')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('old_kredit_coa_id')->nullable()->constrained('coas')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('new_kredit_coa_id')->nullable()->constrained('coas')->onDelete('cascade')->onUpdate('cascade');

            $table->string('description')->nullable();
            $table->string('image_invoice')->nullable();
            $table->string('image_proof')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_suppliers');
    }
};
