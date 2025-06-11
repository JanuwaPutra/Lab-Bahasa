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

        // Create a teacher user
        User::factory()->create([
            'name' => 'Teacher User',
            'email' => 'teacher@example.com',
            'role' => 'teacher',
            'password' => bcrypt('password'), // Set a known password for easy login
        ]);
        
        // Create a student user
        User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@example.com',
            'role' => 'student',
            'password' => bcrypt('password'), // Set a known password for easy login
        ]);
        
        $this->call([
            LearningMaterialSeeder::class,
            AssessmentSeeder::class,
            TestSettingsSeeder::class,
        ]);
    }
}
