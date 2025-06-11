<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Console\Command;

class GenerateAssessmentDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-assessment-details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate details information for assessments that have answers but no details';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to generate assessment details...');
        
        // Get all assessments that have answers but no details
        $assessments = Assessment::whereNotNull('answers')
            ->whereNull('details')
            ->get();
        
        $count = 0;
        
        foreach ($assessments as $assessment) {
            $this->info("Processing assessment ID: {$assessment->id}");
            
            // Check if answers is already decoded
            $answers = $assessment->answers;
            if (is_string($answers)) {
                $answers = json_decode($answers, true);
            }
            
            if (empty($answers) || !is_array($answers)) {
                $this->warn("- Skipping: No valid answers found");
                continue;
            }
            
            // Fetch questions for this assessment type
            $questions = Question::byAssessmentType($this->getAssessmentType($assessment->type))
                ->byLanguage($assessment->language)
                ->when($assessment->type == 'post_test', function ($query) use ($assessment) {
                    return $query->byLevel($assessment->level);
                })
                ->get();
            
            if ($questions->isEmpty()) {
                $this->warn("- Skipping: No questions found for this assessment type");
                continue;
            }
            
            // Map questions by ID for easy lookup
            $questionsById = $questions->keyBy('id');
            
            // Build details array
            $details = [];
            
            foreach ($answers as $questionId => $userAnswer) {
                $question = $questionsById->get($questionId);
                if (!$question) {
                    continue;
                }
                
                $isCorrect = false;
                
                // Check if answer is correct
                if ($question->type == 'multiple_choice') {
                    $isCorrect = (int)$userAnswer === (int)$question->correct_answer;
                } elseif ($question->type == 'true_false' || $question->type == 'fill_blank') {
                    $isCorrect = $userAnswer == $question->correct_answer;
                } elseif ($question->type == 'essay') {
                    // For essay, just check if word count meets minimum requirement
                    $wordCount = str_word_count($userAnswer);
                    $isCorrect = $wordCount >= $question->min_words;
                }
                
                // Add to details
                $details[] = [
                    'question_id' => $questionId,
                    'question' => $question->text,
                    'user_answer' => $userAnswer,
                    'correct_answer' => $question->correct_answer,
                    'is_correct' => $isCorrect,
                    'type' => $question->type
                ];
            }
            
            // Save details to assessment
            $assessment->details = json_encode($details);
            $assessment->save();
            
            $this->info("- Generated details for {$assessment->id}: " . count($details) . " items");
            $count++;
        }
        
        $this->info("Generated details for {$count} assessments.");
        
        return Command::SUCCESS;
    }
    
    /**
     * Convert assessment type to question assessment type.
     */
    private function getAssessmentType(string $type): string
    {
        $map = [
            'pretest' => 'pretest',
            'post_test' => 'post_test',
            'placement' => 'placement',
            'listening' => 'listening',
            'reading' => 'reading',
            'speaking' => 'speaking'
        ];
        
        return $map[$type] ?? 'pretest';
    }
}
