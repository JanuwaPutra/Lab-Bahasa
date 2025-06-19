<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create an admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => bcrypt('password'), // Set a known password for easy login
        ]);

        // Create a test teacher
        $teacher = \App\Models\User::updateOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'Test Teacher',
                'password' => bcrypt('password'),
                'role' => 'teacher'
            ]
        );
        
        echo "Teacher created with ID: " . $teacher->id . "\n";
        
        // Clear existing teacher language settings
        \Illuminate\Support\Facades\DB::table('teacher_languages')->where('teacher_id', $teacher->id)->delete();
        
        // Add language settings for the teacher
        \App\Models\TeacherLanguage::create([
            'teacher_id' => $teacher->id,
            'language' => 'id',
            'level' => 1,
        ]);
        
        \App\Models\TeacherLanguage::create([
            'teacher_id' => $teacher->id,
            'language' => 'en',
            'level' => 2,
        ]);
        
        \App\Models\TeacherLanguage::create([
            'teacher_id' => $teacher->id,
            'language' => 'ru',
            'level' => 3,
        ]);
        
        echo "Teacher language settings created.\n";

        // Create a student user
        User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@example.com',
            'role' => 'student',
            'password' => bcrypt('password'), // Set a known password for easy login
        ]);
        
        $this->call([
            TestUserWithPretestSeeder::class,
            TeacherUserSeeder::class,
            LearningMaterialSeeder::class,
            TestSettingsSeeder::class,
            QuestionSeeder::class,
            AssessmentSeeder::class,
        ]);
    }
}
