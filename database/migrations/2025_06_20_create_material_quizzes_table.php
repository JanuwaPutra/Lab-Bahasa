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
        Schema::create('material_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_material_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('passing_score')->default(70); // Nilai minimum untuk lulus (persentase)
            $table->integer('time_limit')->nullable(); // Batas waktu dalam menit (null = tidak ada batas)
            $table->json('questions'); // Array pertanyaan dan jawaban
            $table->boolean('must_pass')->default(true); // Wajib lulus untuk lanjut ke materi berikutnya
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_quizzes');
    }
}; 