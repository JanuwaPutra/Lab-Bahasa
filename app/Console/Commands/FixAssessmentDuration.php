<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assessment;
use Carbon\Carbon;

class FixAssessmentDuration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:assessment-duration {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix assessment duration based on creation and update timestamps';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        
        if ($id) {
            // Fix specific assessment
            $assessment = Assessment::find($id);
            if (!$assessment) {
                $this->error("Assessment with ID {$id} not found");
                return 1;
            }
            
            $this->fixAssessmentDuration($assessment);
            $this->info("Assessment with ID {$id} has been updated");
            return 0;
        }
        
        // Fix all assessments
        $count = 0;
        $total = Assessment::count();
        $this->info("Fixing duration for {$total} assessments...");
        
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();
        
        Assessment::chunk(100, function ($assessments) use (&$count, $progressBar) {
            foreach ($assessments as $assessment) {
                $this->fixAssessmentDuration($assessment);
                $count++;
                $progressBar->advance();
            }
        });
        
        $progressBar->finish();
        $this->newLine();
        $this->info("Fixed duration for {$count} assessments");
        
        return 0;
    }
    
    /**
     * Fix the duration for an assessment.
     */
    private function fixAssessmentDuration(Assessment $assessment)
    {
        // Reset duration to null first to force recalculation
        $assessment->duration = null;
        
        if ($assessment->created_at && $assessment->updated_at) {
            // Pastikan created_at lebih awal dari updated_at
            if ($assessment->created_at->gt($assessment->updated_at)) {
                // Jika created_at lebih besar dari updated_at, tukar nilainya untuk perhitungan
                $seconds = $assessment->created_at->diffInSeconds($assessment->updated_at);
            } else {
                $seconds = $assessment->updated_at->diffInSeconds($assessment->created_at);
            }
            
            // Pastikan nilai positif
            $seconds = abs($seconds);
            
            // Jika waktu terlalu singkat, tetapkan minimal 3 detik
            if ($seconds < 3) {
                $seconds = 3;
            }
            
            // Save duration in minutes with 1 decimal place
            $assessment->duration = round($seconds / 60, 1);
        } else {
            // If no timestamps, set a reasonable default based on test type
            $defaultDurations = [
                'pretest' => 5,
                'post_test' => 15,
                'placement' => 10,
                'listening' => 7,
                'reading' => 7,
                'speaking' => 5,
                'grammar' => 5,
                'default' => 7
            ];
            
            $assessment->duration = $defaultDurations[$assessment->type] ?? $defaultDurations['default'];
        }
        
        $assessment->save();
    }
}
