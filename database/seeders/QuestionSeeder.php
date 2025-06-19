<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample pretest questions for Indonesian language
        $this->createPretestQuestions('id');
    }

    /**
     * Create pretest questions for a specific language
     */
    private function createPretestQuestions($language)
    {
        $questions = [
            // Question 1
            [
                'text' => 'Apa ibu kota Indonesia?',
                'type' => 'multiple_choice',
                'options' => ['Jakarta', 'Bandung', 'Surabaya', 'Medan'],
                'option_scores' => [1, 0, 0, 0],
                'correct_answer' => '0',
                'level' => 1,
                'points' => 1
            ],
            // Question 2
            [
                'text' => 'Benarkah Bali adalah sebuah provinsi di Indonesia?',
                'type' => 'true_false',
                'options' => [],
                'option_scores' => [1, 0],
                'correct_answer' => 'true',
                'level' => 1,
                'points' => 1
            ],
            // Question 3
            [
                'text' => 'Sebutkan salah satu contoh kalimat lengkap dalam bahasa Indonesia.',
                'type' => 'essay',
                'options' => [],
                'min_words' => 10,
                'level' => 2,
                'points' => 2
            ],
            // Question 4
            [
                'text' => 'Indonesia merayakan kemerdekaan pada tanggal ___ Agustus.',
                'type' => 'fill_blank',
                'options' => [],
                'correct_answer' => '17',
                'level' => 1,
                'points' => 1
            ],
            // Question 5
            [
                'text' => 'Sebutkan bahasa resmi Indonesia.',
                'type' => 'fill_blank',
                'options' => [],
                'correct_answer' => 'Bahasa Indonesia',
                'level' => 1,
                'points' => 1
            ],
        ];

        foreach ($questions as $question) {
            Question::create([
                'text' => $question['text'],
                'type' => $question['type'],
                'options' => $question['options'],
                'option_scores' => $question['option_scores'] ?? [],
                'correct_answer' => $question['correct_answer'] ?? null,
                'level' => $question['level'],
                'assessment_type' => 'pretest',
                'min_words' => $question['min_words'] ?? null,
                'points' => $question['points'],
                'active' => true,
                'language' => $language
            ]);
        }
    }
} 