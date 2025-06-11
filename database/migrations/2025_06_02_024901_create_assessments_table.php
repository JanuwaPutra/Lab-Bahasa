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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['placement', 'pretest', 'post_test', 'listening', 'reading', 'speaking', 'grammar', 'level_change']);
            $table->integer('level')->nullable();
            $table->float('score')->nullable();
            $table->float('percentage')->nullable();
            $table->boolean('passed')->default(false);
            $table->json('answers')->nullable();
            $table->json('results')->nullable();
            $table->string('language', 10)->default('en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
