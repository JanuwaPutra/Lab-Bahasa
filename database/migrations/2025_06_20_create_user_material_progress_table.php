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
        Schema::create('user_material_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('learning_material_id')->constrained()->onDelete('cascade');
            $table->boolean('completed')->default(false); // Apakah materi sudah selesai dipelajari
            $table->boolean('quiz_passed')->default(false); // Apakah sudah lulus kuis
            $table->integer('quiz_score')->nullable(); // Nilai kuis (persentase)
            $table->integer('quiz_attempts')->default(0); // Berapa kali mencoba kuis
            $table->timestamp('last_quiz_attempt')->nullable(); // Kapan terakhir mencoba kuis
            $table->timestamp('completed_at')->nullable(); // Kapan menyelesaikan materi
            $table->timestamp('passed_at')->nullable(); // Kapan lulus kuis
            $table->json('quiz_answers')->nullable(); // Jawaban kuis terakhir
            $table->json('notes')->nullable(); // Catatan siswa
            $table->timestamps();
            
            // Kombinasi unik user_id dan learning_material_id
            $table->unique(['user_id', 'learning_material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_material_progress');
    }
}; 