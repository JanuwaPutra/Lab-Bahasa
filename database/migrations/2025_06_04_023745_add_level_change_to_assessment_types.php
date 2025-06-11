<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to modify the enum values
        DB::statement("ALTER TABLE assessments MODIFY COLUMN type ENUM('placement', 'pretest', 'post_test', 'listening', 'reading', 'speaking', 'grammar', 'level_change')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE assessments MODIFY COLUMN type ENUM('placement', 'pretest', 'post_test', 'listening', 'reading', 'speaking', 'grammar')");
    }
};
