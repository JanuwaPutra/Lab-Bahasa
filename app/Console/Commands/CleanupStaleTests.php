<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupStaleTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up stale post-test data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting stale test cleanup...');
        
        try {
            // 1. Mark all completed tests as inactive
            $this->info('Checking for completed tests marked as active...');
            $completedTests = DB::select("SELECT id FROM post_test_progress WHERE completed = 1 AND is_active = 1");
            
            $completedCount = count($completedTests);
            if ($completedCount > 0) {
                $this->info("Found {$completedCount} completed tests marked as active.");
                
                foreach ($completedTests as $test) {
                    try {
                        DB::statement("UPDATE post_test_progress SET is_active = 0 WHERE id = ?", [$test->id]);
                        $this->line("Updated test {$test->id}");
                    } catch (\Exception $e) {
                        $this->error("Error updating test {$test->id}: {$e->getMessage()}");
                    }
                }
            } else {
                $this->info('No completed tests marked as active found.');
            }
            
            // 2. Check for tests with corresponding assessment results
            $this->info('Checking for tests with assessment results...');
            $testsWithResults = DB::select("
                SELECT p.id 
                FROM post_test_progress p 
                JOIN assessments a ON a.user_id = p.user_id 
                    AND a.language = p.language 
                    AND a.level = p.level 
                    AND a.type = 'post_test'
                WHERE p.is_active = 1 
                AND a.created_at >= p.start_time
            ");
            
            $withResultsCount = count($testsWithResults);
            if ($withResultsCount > 0) {
                $this->info("Found {$withResultsCount} tests with results that should be marked completed.");
                
                foreach ($testsWithResults as $test) {
                    try {
                        DB::statement("UPDATE post_test_progress SET is_active = 0, completed = 1 WHERE id = ?", [$test->id]);
                        $this->line("Updated test {$test->id} with results");
                    } catch (\Exception $e) {
                        $this->error("Error updating test {$test->id} with results: {$e->getMessage()}");
                    }
                }
            } else {
                $this->info('No tests with results found.');
            }
            
            // 3. Check for tests with expired time (old tests)
            $this->info('Checking for old tests...');
            $threeHoursAgo = now()->subHours(3)->format('Y-m-d H:i:s');
            $oldTests = DB::select("SELECT id FROM post_test_progress WHERE is_active = 1 AND start_time < ?", [$threeHoursAgo]);
            
            $oldCount = count($oldTests);
            if ($oldCount > 0) {
                $this->info("Found {$oldCount} old tests to clean up.");
                
                foreach ($oldTests as $test) {
                    try {
                        DB::statement("UPDATE post_test_progress SET is_active = 0, completed = 1 WHERE id = ?", [$test->id]);
                        $this->line("Updated old test {$test->id}");
                    } catch (\Exception $e) {
                        $this->error("Error updating old test {$test->id}: {$e->getMessage()}");
                    }
                }
            } else {
                $this->info('No old tests found.');
            }
            
            // 4. Handle tests with time expired based on the time limit
            $this->info('Checking for tests with expired time limits...');
            $testsToCheck = DB::select("
                SELECT id, user_id, start_time, language 
                FROM post_test_progress 
                WHERE is_active = 1 AND completed = 0
            ");
            
            $expiredCount = 0;
            foreach ($testsToCheck as $test) {
                try {
                    $startTime = new \DateTime($test->start_time);
                    $currentTime = now();
                    $elapsedSeconds = $currentTime->getTimestamp() - $startTime->getTimestamp();
                    
                    // Get time limit for this test (45 minutes by default)
                    $timeLimit = 45; // Default 45 minutes
                    $timeLimitSeconds = $timeLimit * 60;
                    
                    if ($elapsedSeconds > $timeLimitSeconds) {
                        DB::statement("UPDATE post_test_progress SET is_active = 0, completed = 1 WHERE id = ?", [$test->id]);
                        $expiredCount++;
                    }
                } catch (\Exception $e) {
                    $this->error("Error checking time limit for test {$test->id}: {$e->getMessage()}");
                }
            }
            
            if ($expiredCount > 0) {
                $this->info("Marked {$expiredCount} tests as completed due to time expiration.");
            } else {
                $this->info('No tests with expired time limits found.');
            }
            
            // 5. Fix orphaned tests (no associated user)
            $this->info('Checking for orphaned tests...');
            $rowsAffected = DB::update("
                UPDATE post_test_progress p
                LEFT JOIN users u ON p.user_id = u.id
                SET p.is_active = 0, p.completed = 1
                WHERE p.is_active = 1 AND u.id IS NULL
            ");
            
            if ($rowsAffected > 0) {
                $this->info("Fixed {$rowsAffected} orphaned tests.");
            } else {
                $this->info('No orphaned tests found.');
            }
            
            // 6. Look for other indications that a test is completed (answer data)
            $this->info('Checking for tests with completion flags in answers...');
            $testsToCheckForCompletion = DB::select("
                SELECT id, answers
                FROM post_test_progress
                WHERE is_active = 1 AND completed = 0
            ");
            
            $flaggedCompletedCount = 0;
            foreach ($testsToCheckForCompletion as $test) {
                try {
                    if (!empty($test->answers)) {
                        $answers = json_decode($test->answers, true);
                        // Check if answers contain completion flag
                        if (is_array($answers) && isset($answers['_completed']) && $answers['_completed'] === true) {
                            DB::statement("UPDATE post_test_progress SET is_active = 0, completed = 1 WHERE id = ?", [$test->id]);
                            $flaggedCompletedCount++;
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Error checking completion status in answers for test {$test->id}: {$e->getMessage()}");
                }
            }
            
            if ($flaggedCompletedCount > 0) {
                $this->info("Marked {$flaggedCompletedCount} tests as completed based on answer flags.");
            } else {
                $this->info('No tests with completion flags found.');
            }
            
            // Summary
            $totalFixed = $completedCount + $withResultsCount + $oldCount + $expiredCount + $rowsAffected + $flaggedCompletedCount;
            $this->info("Cleanup completed. Total tests fixed: {$totalFixed}");
            
            return 0;
        } catch (\Exception $e) {
            Log::error('Error in CleanupStaleTests command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->error("Error during cleanup: {$e->getMessage()}");
            return 1;
        }
    }
} 