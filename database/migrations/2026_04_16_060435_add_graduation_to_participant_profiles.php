<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participant_profiles', function (Blueprint $table) {
            $table->boolean('is_graduated')->default(false)->after('status');
            $table->timestamp('graduated_at')->nullable()->after('is_graduated');
            $table->string('graduation_certificate_id')->nullable()->after('graduated_at');
        });
    }

    public function down(): void
    {
        Schema::table('participant_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_graduated', 'graduated_at', 'graduation_certificate_id']);
        });
    }
};
