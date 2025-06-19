<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TeacherLanguage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeacherLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing teacher language settings
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('teacher_languages')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Make sure the teacher exists
        $teacher = User::find(3);
        if (!$teacher) {
            $this->command->info('Teacher with ID 3 not found. Creating a test teacher...');
            
            // Create a test teacher if needed
            $teacher = User::updateOrCreate(
                ['email' => 'teacher@example.com'],
                [
                    'name' => 'Test Teacher',
                    'password' => bcrypt('password'),
                    'role' => 'teacher'
                ]
            );
        }
        
        // Add language settings for the teacher
        $teacherLanguages = [
            [
                'teacher_id' => $teacher->id,
                'language' => 'id',
                'level' => 1,
            ],
            [
                'teacher_id' => $teacher->id,
                'language' => 'en',
                'level' => 2,
            ],
            [
                'teacher_id' => $teacher->id,
                'language' => 'ru',
                'level' => 3,
            ],
        ];
        
        foreach ($teacherLanguages as $data) {
            TeacherLanguage::create($data);
        }
        
        $this->command->info('Teacher language settings seeded successfully.');
    }
}
