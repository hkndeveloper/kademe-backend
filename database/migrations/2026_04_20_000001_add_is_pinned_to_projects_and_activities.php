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
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('is_active');
            $table->softDeletes();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('type');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('is_pinned');
            $table->dropSoftDeletes();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('is_pinned');
            $table->dropSoftDeletes();
        });
    }
};
