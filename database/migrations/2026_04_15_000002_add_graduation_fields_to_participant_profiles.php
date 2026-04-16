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
            $table->string('graduation_status')->nullable()->after('status'); // completed, graduated, failed
            $table->foreignId('graduated_project_id')->nullable()->after('graduation_status')->constrained('projects')->onDelete('set null');
            $table->text('graduation_reason')->nullable()->after('graduated_project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participant_profiles', function (Blueprint $table) {
            $table->dropForeign(['graduated_project_id']);
            $table->dropColumn(['graduation_status', 'graduated_project_id', 'graduation_reason']);
        });
    }
};
