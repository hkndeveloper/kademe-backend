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
        Schema::create('rejection_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('criteria_type'); // age, department, university, class, etc.
            $table->string('operator'); // equals, not_equals, greater_than, less_than, contains, not_contains
            $table->string('value');
            $table->text('rejection_message')->nullable(); // Custom message for rejected applicants
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rejection_criteria');
    }
};
