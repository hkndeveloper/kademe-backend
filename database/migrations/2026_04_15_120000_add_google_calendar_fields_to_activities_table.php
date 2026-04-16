<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('google_calendar_event_id')->nullable()->after('qr_code_secret');
            $table->timestamp('google_calendar_last_synced_at')->nullable()->after('google_calendar_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn([
                'google_calendar_event_id',
                'google_calendar_last_synced_at',
            ]);
        });
    }
};
