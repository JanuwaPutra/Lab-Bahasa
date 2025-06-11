<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Assessment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserWithPretestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User with Pretest',
            'email' => 'pretest@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        // Create a pretest assessment for the user
        Assessment::create([
            'user_id' => $user->id,
            'type' => 'pretest',
            'level' => 3,
            'score' => 75,
            'percentage' => 75.0,
            'passed' => true,
            'answers' => json_encode(['1' => '2', '2' => '1', '3' => 'test answer']),
            'results' => json_encode(['correct' => 2, 'incorrect' => 1]),
            'language' => 'en',
        ]);

        $this->command->info('Test user with pretest created successfully.');
    }
}
