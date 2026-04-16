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
        Schema::create('badge_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Gümüş, Altın, vb.
            $table->integer('min_badges')->default(0); // Eşik değeri
            $table->string('title')->nullable(); // Gümüş Katılımcı vb.
            $table->string('frame_color')->default('#cbd5e1'); // CSS color or hex
            $table->string('reward_description')->nullable(); // KADEME Kupa Seti vb.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badge_tiers');
    }
};
