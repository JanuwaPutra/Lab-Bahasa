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
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assessment_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('test_type', ['grammar', 'speech', 'reading', 'listening', 'speaking']);
            $table->text('original_text')->nullable();
            $table->text('corrected_text')->nullable();
            $table->text('recognized_text')->nullable();
            $table->text('reference_text')->nullable();
            $table->float('accuracy')->nullable();
            $table->text('feedback')->nullable();
            $table->integer('word_count')->nullable();
            $table->string('language', 10)->default('en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
};
