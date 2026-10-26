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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('csv_import_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id');
            $table->string('name')->nullable();
            $table->string('course_code')->nullable()->index();
            $table->unsignedTinyInteger('age_at_enrollment')->nullable();
            $table->decimal('admission_grade', 8, 2)->nullable();
            $table->decimal('average_grade', 8, 2)->nullable();
            $table->unsignedSmallInteger('approved_units_first_semester')->nullable();
            $table->unsignedSmallInteger('approved_units_second_semester')->nullable();
            $table->string('observed_outcome')->nullable();
            $table->json('academic_data');
            $table->timestamps();
            $table->unique(['institution_id', 'external_id']);
            $table->index(['institution_id', 'course_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
