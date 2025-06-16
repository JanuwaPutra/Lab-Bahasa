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
            $table->timestamp('quiz_start_time')->nullable()->after('quiz_answers');
            $table->timestamp('quiz_end_time')->nullable()->after('quiz_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_material_progress', function (Blueprint $table) {
            $table->dropColumn('quiz_start_time');
            $table->dropColumn('quiz_end_time');
        });
    }
};
