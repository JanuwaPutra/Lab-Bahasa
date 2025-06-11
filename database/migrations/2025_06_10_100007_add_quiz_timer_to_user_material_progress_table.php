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
        Schema::table('user_material_progress', function (Blueprint $table) {
            $table->integer('quiz_timer')->nullable()->after('quiz_answers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_material_progress', function (Blueprint $table) {
            $table->dropColumn('quiz_timer');
        });
    }
};
