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
        Schema::create('virtual_tutor_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('level')->default('beginner'); // beginner, intermediate, advanced
            $table->string('exercise_type')->default('free_conversation');
            $table->json('conversation_history')->nullable();
            $table->text('last_message')->nullable();
            $table->text('last_response')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_tutor_conversations');
    }
};
