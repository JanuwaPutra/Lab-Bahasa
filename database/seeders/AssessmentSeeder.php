<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Assessment;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AssessmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users with role 'student' or null
        $students = User::where('role', 'student')
            ->orWhereNull('role')
            ->get();
        
        if ($students->isEmpty()) {
            $this->command->info('No students found. Creating sample student...');
            
            // Create a sample student
            $student = User::create([
                'name' => 'Sample Student',
                'email' => 'student@example.com',
                'password' => bcrypt('password'),
                'role' => 'student'
            ]);
            
            $students = collect([$student]);
        }
        
        $testTypes = ['pretest', 'post_test', 'listening', 'reading', 'speaking', 'grammar'];
        $languages = ['en', 'id'];
        
        foreach ($students as $student) {
            // Create a pretest for each student
            Assessment::create([
                'user_id' => $student->id,
                'type' => 'pretest',
                'level' => rand(1, 10),
                'score' => rand(50, 100),
                'percentage' => rand(50, 100),
                'passed' => true,
                'language' => 'en',
                'duration' => rand(10, 30),
                'feedback' => 'Good job! You have completed the pretest successfully.',
                'details' => json_encode([
                    [
                        'question' => 'What is the capital of France?',
                        'user_answer' => 'Paris',
                        'correct_answer' => 'Paris',
                        'is_correct' => true
                    ],
                    [
                        'question' => 'What is the capital of Germany?',
                        'user_answer' => 'Berlin',
                        'correct_answer' => 'Berlin',
                        'is_correct' => true
                    ],
                    [
                        'question' => 'What is the capital of Italy?',
                        'user_answer' => 'Rome',
                        'correct_answer' => 'Rome',
                        'is_correct' => true
                    ]
                ])
            ]);
            
            // Create random assessments for each student
            for ($i = 0; $i < 5; $i++) {
                $type = $testTypes[array_rand($testTypes)];
                $language = $languages[array_rand($languages)];
                $score = rand(50, 100);
                
                Assessment::create([
                    'user_id' => $student->id,
                    'type' => $type,
                    'level' => rand(1, 10),
                    'score' => $score,
                    'percentage' => $score,
                    'passed' => $score >= 70,
                    'language' => $language,
                    'duration' => rand(10, 30),
                    'feedback' => $score >= 70 
                        ? 'Good job! You have passed the test.' 
                        : 'You need to study more to pass this test.',
                    'details' => json_encode([
                        [
                            'question' => 'Sample question 1',
                            'user_answer' => 'Sample answer 1',
                            'correct_answer' => 'Sample answer 1',
                            'is_correct' => true
                        ],
                        [
                            'question' => 'Sample question 2',
                            'user_answer' => $score >= 70 ? 'Correct answer' : 'Wrong answer',
                            'correct_answer' => 'Correct answer',
                            'is_correct' => $score >= 70
                        ]
                    ])
                ]);
            }
        }
    }
}
