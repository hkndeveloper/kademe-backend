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
            $table->timestamp('blacklisted_at')->nullable()->after('status');
            $table->text('blacklist_reason')->nullable()->after('blacklisted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participant_profiles', function (Blueprint $table) {
            $table->dropColumn(['blacklisted_at', 'blacklist_reason']);
        });
    }
};
