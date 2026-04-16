<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participant_profiles', function (Blueprint $table) {
            $table->integer('age')->nullable()->after('class');
            $table->string('hometown')->nullable()->after('age');
            $table->string('period')->nullable()->after('hometown'); // örn: 2023-2024 Güz
        });
    }

    public function down(): void
    {
        Schema::table('participant_profiles', function (Blueprint $table) {
            $table->dropColumn(['age', 'hometown', 'period']);
        });
    }
};
