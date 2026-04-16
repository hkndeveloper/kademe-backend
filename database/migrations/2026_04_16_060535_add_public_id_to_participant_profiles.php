<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participant_profiles', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('user_id');
        });

        // Mevcut kayıtlara UUID ata
        $profiles = \App\Models\ParticipantProfile::all();
        foreach ($profiles as $profile) {
            $profile->update(['public_id' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        Schema::table('participant_profiles', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }
};
