<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use Illuminate\Console\Command;

class UpdateAssessmentDurations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-assessment-durations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set default durations for assessments that don\'t have a duration value';

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
        $this->info('Starting to update assessment durations...');
        
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
        
        return Command::SUCCESS;
    }
}
