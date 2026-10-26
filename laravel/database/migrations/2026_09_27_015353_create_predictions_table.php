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
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('risk_score', 5, 2);
            $table->string('risk_level', 20);
            $table->string('predicted_class', 50);
            $table->string('cause', 40)->default('undetermined');
            $table->json('explanation')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'created_at']);
            $table->index(['risk_level', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
