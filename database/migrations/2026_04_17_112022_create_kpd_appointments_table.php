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
        Schema::create('kpd_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Danışan
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->onDelete('set null'); // Danışman
            $table->integer('room_id')->default(1); // Oda 1 veya 2
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('type')->default('online'); // online, office
            $table->string('topic')->nullable();
            $table->string('status')->default('pending'); // pending, confirmed, completed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpd_appointments');
    }
};
