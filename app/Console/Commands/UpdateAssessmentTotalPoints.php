<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use Illuminate\Console\Command;

class UpdateAssessmentTotalPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-assessment-total-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing assessment records with total_points value based on score and percentage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update assessment total_points...');
        
        // Get all assessments without total_points
        $assessments = Assessment::whereNull('total_points')->get();
        
        $count = 0;
        
        foreach ($assessments as $assessment) {
            // Calculate total points based on score and percentage
            if ($assessment->percentage > 0) {
                $totalPoints = round(($assessment->score / ($assessment->percentage / 100)), 2);
            } else {
                // If percentage is 0, assume 100 total points
                $totalPoints = 100;
            }
            
            // Update the record
            $assessment->total_points = $totalPoints;
            $assessment->save();
            
            $count++;
        }
        
        $this->info("Updated {$count} assessment records with total_points.");
        
        return Command::SUCCESS;
    }
}
