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
        Schema::create('participant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Kişisel Bilgiler
            $table->string('tc_no')->nullable()->index();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            
            // Eğitim Bilgiler
            $table->string('university')->nullable();
            $table->string('department')->nullable();
            $table->string('class')->nullable(); // örn: 1. Sınıf
            
            // Sistem Bilgileri
            $table->integer('credits')->default(100);
            $table->enum('status', ['active', 'passive', 'alumni', 'blacklisted', 'failed'])->default('active');
            $table->json('digital_cv')->nullable(); // Kurslar, başarılar vb.
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_profiles');
    }
};
