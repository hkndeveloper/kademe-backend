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
        Schema::table('participant_profiles', function (Blueprint $table) {
            $table->text('bio')->nullable();
            $table->uuid('cv_uuid')->unique()->nullable();
            $table->boolean('public_cv')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participant_profiles', function (Blueprint $table) {
            $table->dropColumn(['bio', 'cv_uuid', 'public_cv']);
        });
    }
};
