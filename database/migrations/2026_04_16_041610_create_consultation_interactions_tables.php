<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::create('consultation_interactions_tables', function (Blueprint $table) {
    //         $table->id();
    //         $table->timestamps();
    //     });
    // }

    public function up()
    {
        // Tabel Log Konsultasi (Saat user klik kategori keluhan)
        Schema::create('consultation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('category_title');
            $table->string('consultation_type'); // misal: Video Call / Chat
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tabel Janji Temu (Saat user klik Buat Janji Temu)
        Schema::create('clinic_appointments', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->foreignId('clinic_treatment_id')->constrained('clinic_treatments')->onDelete('cascade');
            $table->dateTime('appointment_time');
            $table->text('reason');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_interactions_tables');
    }
};
