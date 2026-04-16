<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('application_deadline')->nullable();
            $table->string('format')->nullable(); // Hibrit, Online, Yüz yüze
            $table->string('period')->nullable(); // 2024 Bahar Dönemi
            $table->string('sub_description')->nullable(); // Sayfa başındaki kısa açıklama
            $table->json('timeline')->nullable(); // Program akışı JSON: [{"label": "Açılış", "date": "Mart 2026"}]
            $table->json('documents')->nullable(); // Belgeler JSON: [{"title": "Dosya", "url": "..."}]
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['application_deadline', 'format', 'period', 'sub_description', 'timeline', 'documents']);
        });
    }
};
