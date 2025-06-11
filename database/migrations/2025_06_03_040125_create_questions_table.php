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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->enum('type', ['multiple_choice', 'true_false', 'essay', 'fill_blank']);
            $table->json('options')->nullable();
            $table->string('correct_answer')->nullable();
            $table->integer('level')->default(1);
            $table->enum('assessment_type', ['placement', 'pretest', 'post_test', 'listening', 'reading', 'speaking', 'grammar']);
            $table->integer('min_words')->nullable();
            $table->integer('points')->default(1);
            $table->boolean('active')->default(true);
            $table->string('language', 10)->default('id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
