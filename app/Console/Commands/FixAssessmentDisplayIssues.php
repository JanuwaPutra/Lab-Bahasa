<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use App\Models\Question;
use Illuminate\Console\Command;

class FixAssessmentDisplayIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-assessment-display-issues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix assessment display issues (missing durations and details)';

    /**
     * Default durations for different assessment types (in minutes)
     */
    protected $defaultDurations = [
        'pretest' => 10,
        'post_test' => 30,
        'placement' => 20,
        'listening' => 15,
        'reading' => 15,
        'speaking' => 10,
        'grammar' => 10,
        'default' => 15
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fix assessment display issues...');
        
        $this->fixMissingDurations();
        $this->fixMissingDetails();
        
        return Command::SUCCESS;
    }
    
    /**
     * Fix assessments with missing durations
     */
    private function fixMissingDurations()
    {
        $this->info('Fixing missing durations...');
        
        // Get all assessments without a duration
        $assessments = Assessment::whereNull('duration')
            ->orWhere('duration', 0)
            ->get();
        
        $count = 0;
        
        foreach ($assessments as $assessment) {
            // Set the default duration based on assessment type
            $duration = $this->defaultDurations[$assessment->type] ?? $this->defaultDurations['default'];
            
            // Calculate a more realistic duration based on the number of questions if we have that data
            if ($assessment->total_questions) {
                // Assume each question takes about 1-2 minutes
                $questionCount = $assessment->total_questions;
                $minDuration = max(5, $questionCount); // At least 5 minutes
                $maxDuration = min(60, $questionCount * 2); // Max 60 minutes
                
                // Set a duration somewhere in between min and max
                $duration = intval(($minDuration + $maxDuration) / 2);
            }
            
            // Update the record
            $assessment->duration = $duration;
            $assessment->save();
            
            $count++;
        }
        
        $this->info("Updated {$count} assessment records with durations.");
    }
    
    /**
     * Fix assessments with missing details but existing answers
     */
    private function fixMissingDetails()
    {
        $this->info('Fixing missing details...');
        
        // Get all assessments that have answers
        $assessments = Assessment::whereNotNull('answers')
            ->orderBy('id', 'desc')
            ->get();
        
        $count = 0;
        $updated = 0;
        
        foreach ($assessments as $assessment) {
            $count++;
            $this->info("Processing assessment ID: {$assessment->id}");
            
            // Check if details need fixing
            $needsFixing = false;
            if ($assessment->details === null || $assessment->details === '[]' || $assessment->details === '') {
                $needsFixing = true;
            } else {
                // Check if details is already decoded
                $details = $assessment->details;
                if (is_string($details)) {
                    try {
                        $details = json_decode($details, true);
                    } catch (\Exception $e) {
                        $this->warn("- Invalid JSON in details: {$e->getMessage()}");
                        $needsFixing = true;
                    }
                }
                
                // Check if details is empty or invalid array
                if (empty($details) || !is_array($details) || count($details) === 0) {
                    $needsFixing = true;
                }
            }
            
            if (!$needsFixing) {
                $this->info("- Details already valid for assessment ID: {$assessment->id}");
                continue;
            }
            
            // Check if answers is already decoded
            $answers = $assessment->answers;
            if (is_string($answers)) {
                try {
                    $answers = json_decode($answers, true);
                } catch (\Exception $e) {
                    $this->warn("- Skipping: Invalid JSON in answers: {$e->getMessage()}");
                    continue;
                }
            }
            
            if (empty($answers) || !is_array($answers)) {
                $this->warn("- Skipping: No valid answers found");
                continue;
            }
            
            // Fetch questions for this assessment type
            $questions = Question::when(true, function ($query) use ($assessment) {
                    // Try to match by assessment type
                    return $query->where('assessment_type', $this->getAssessmentType($assessment->type));
                })
                ->when($assessment->language, function ($query) use ($assessment) {
                    // Filter by language if available
                    return $query->where('language', $assessment->language);
                })
                ->when($assessment->type == 'post_test' && $assessment->level, function ($query) use ($assessment) {
                    // Filter by level for post tests
                    return $query->where('level', $assessment->level);
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
            $correctCount = 0;
            
            foreach ($answers as $questionId => $userAnswer) {
                // Convert questionId to integer if it's a string
                if (is_string($questionId)) {
                    $questionId = intval($questionId);
                }
                
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
                    $isCorrect = $wordCount >= ($question->min_words ?? 10);
                }
                
                if ($isCorrect) {
                    $correctCount++;
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
            $assessment->details = $details;
            
            // Update correct_count and total_questions if needed
            if (empty($assessment->correct_count) || $assessment->correct_count == 0) {
                $assessment->correct_count = $correctCount;
            }
            
            if (empty($assessment->total_questions) || $assessment->total_questions == 0) {
                $assessment->total_questions = count($details);
            }
            
            // Set time_limit from settings if not already set
            if (empty($assessment->time_limit) || $assessment->time_limit == 0) {
                $assessment->time_limit = \App\Models\TestSettings::getTimeLimit($assessment->type, $assessment->language);
            }
            
            $assessment->save();
            $updated++;
            
            $this->info("- Generated details for {$assessment->id}: " . count($details) . " items, {$correctCount} correct");
        }
        
        $this->info("Processed {$count} assessments, updated {$updated} with details.");
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
            'speaking' => 'speaking',
            'grammar' => 'grammar'
        ];
        
        return $map[$type] ?? 'pretest';
    }
} 