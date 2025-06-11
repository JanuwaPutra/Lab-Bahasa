<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use Illuminate\Console\Command;

class UpdateAssessmentCountFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-assessment-count-fields';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing assessments with correct_count and total_questions fields';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update assessment count fields...');
        
        // Get all assessments without correct_count or total_questions
        $assessments = Assessment::whereNull('correct_count')
            ->orWhereNull('total_questions')
            ->get();
        
        $count = 0;
        
        foreach ($assessments as $assessment) {
            // Default values
            $correctCount = 0;
            $totalQuestions = 0;
            
            // Try to extract from details if available
            if ($assessment->details) {
                $details = json_decode($assessment->details, true);
                if (is_array($details)) {
                    $totalQuestions = count($details);
                    
                    // Count correct answers
                    foreach ($details as $detail) {
                        if (isset($detail['is_correct']) && $detail['is_correct']) {
                            $correctCount++;
                        }
                    }
                }
            } 
            // If no details, try to calculate from the score and total_points
            else if ($assessment->total_points) {
                // Assume each question is worth the same points
                $totalQuestions = round($assessment->total_points);
                $correctCount = round(($assessment->score / $assessment->total_points) * $totalQuestions);
            }
            // If no details and no total_points, use 10 as a default
            else {
                $totalQuestions = 10;
                $correctCount = round(($assessment->score / 100) * $totalQuestions);
            }
            
            // Update the record
            $assessment->correct_count = $correctCount;
            $assessment->total_questions = $totalQuestions;
            $assessment->save();
            
            $count++;
        }
        
        $this->info("Updated {$count} assessment records with count fields.");
        
        return Command::SUCCESS;
    }
}
