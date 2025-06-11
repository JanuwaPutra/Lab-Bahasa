<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assessment;
use Illuminate\Support\Facades\DB;

class FixAssessmentDurationValue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:assessment-duration-value {id?} {value=1.05}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix assessment duration value in database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        $value = $this->argument('value');
        
        // Ensure value is a valid float
        $value = floatval($value);
        
        if ($id) {
            // Fix specific assessment
            $assessment = Assessment::find($id);
            if (!$assessment) {
                $this->error("Assessment with ID {$id} not found");
                return 1;
            }
            
            $this->fixAssessmentDuration($assessment, $value);
            $this->info("Assessment with ID {$id} has been updated with duration {$value}");
            return 0;
        }
        
        // Get latest assessment
        $latestAssessment = Assessment::latest()->first();
        
        if (!$latestAssessment) {
            $this->error("No assessments found");
            return 1;
        }
        
        $this->fixAssessmentDuration($latestAssessment, $value);
        $this->info("Latest assessment (ID: {$latestAssessment->id}) has been updated with duration {$value}");
        
        // Also update directly using DB query to ensure it works
        DB::statement("UPDATE assessments SET duration = ? WHERE id = ?", [$value, $latestAssessment->id]);
        $this->info("Direct DB update completed for assessment ID: {$latestAssessment->id}");
        
        return 0;
    }
    
    /**
     * Fix the duration for an assessment.
     */
    private function fixAssessmentDuration(Assessment $assessment, float $value)
    {
        // Update the duration directly
        $assessment->duration = $value;
        $assessment->save();
        
        // Also store in session
        session(['assessment_' . $assessment->id . '_duration' => $value]);
    }
}
