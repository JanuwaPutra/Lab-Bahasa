<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairDuplicateTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:repair-duplicates {--force : Force execute without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repair duplicate post-test entries by bypassing constraints';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting repair of duplicate post-test entries...');
        
        if (!$this->option('force') && !$this->confirm('This will directly modify the database. Continue?')) {
            $this->info('Operation cancelled.');
            return 1;
        }
        
        // Step 1: Find duplicate entries (multiple active tests for the same user/language/level)
        $this->info('Finding duplicate active tests...');
        
        $duplicates = DB::select("
            SELECT user_id, language, level, COUNT(*) as count
            FROM post_test_progress
            WHERE is_active = 1
            GROUP BY user_id, language, level
            HAVING COUNT(*) > 1
        ");
        
        if (empty($duplicates)) {
            $this->info('No duplicate active tests found.');
        } else {
            $this->info('Found ' . count($duplicates) . ' duplicate active test groups.');
            
            foreach ($duplicates as $duplicate) {
                $this->info("User {$duplicate->user_id}, Language {$duplicate->language}, Level {$duplicate->level}: {$duplicate->count} active tests");
                
                // Get all tests for this user/language/level
                $tests = DB::select("
                    SELECT id, created_at, start_time, completed, is_active
                    FROM post_test_progress
                    WHERE user_id = ? AND language = ? AND level = ? AND is_active = 1
                    ORDER BY created_at DESC
                ", [$duplicate->user_id, $duplicate->language, $duplicate->level]);
                
                // Keep only the most recent one active
                $keepId = $tests[0]->id;
                $this->info("Keeping test ID {$keepId} active (most recent)");
                
                // Deactivate all others using direct SQL to bypass constraint
                for ($i = 1; $i < count($tests); $i++) {
                    $testId = $tests[$i]->id;
                    
                    // Use a direct SQL query to bypass constraints
                    DB::statement("
                        UPDATE post_test_progress
                        SET is_active = 0, completed = 1, completed_at = NOW()
                        WHERE id = ?
                    ", [$testId]);
                    
                    $this->info("Marked test ID {$testId} as inactive and completed");
                }
            }
        }
        
        // Step 2: Fix constraint issues by clearing any lingering active=0 constraints
        $this->info('Repairing constraint issues in the database...');
        
        try {
            // First check if we need to disable foreign key checks
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }
            
            // Directly fix any leftover constraint issues using raw SQL
            $affected = DB::affectingStatement("
                DELETE FROM `unique_active_test`
                WHERE 1=1
            ");
            
            $this->info("Cleared constraint tables, affected rows: {$affected}");
            
            // Re-enable foreign key checks if needed
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        } catch (\Exception $e) {
            $this->error("Error clearing constraints: " . $e->getMessage());
            
            // Try alternative approach for MySQL
            try {
                $this->info("Trying alternative approach...");
                
                // First drop the constraint
                DB::statement("
                    ALTER TABLE post_test_progress
                    DROP INDEX unique_active_test
                ");
                
                $this->info("Dropped constraint index");
                
                // Then recreate it
                DB::statement("
                    ALTER TABLE post_test_progress
                    ADD CONSTRAINT unique_active_test
                    UNIQUE KEY (user_id, language, level, is_active)
                ");
                
                $this->info("Recreated constraint index");
            } catch (\Exception $e2) {
                $this->error("Alternative approach failed: " . $e2->getMessage());
            }
        }
        
        // Step 3: Clean up any stale tests (force them to be completed)
        $this->info('Cleaning up stale tests...');
        
        $staleTests = DB::select("
            SELECT id, user_id, language, level
            FROM post_test_progress
            WHERE is_active = 1 AND completed = 0
            AND (
                -- More than 3 hours old
                start_time < DATE_SUB(NOW(), INTERVAL 3 HOUR)
                OR
                -- Has a corresponding assessment
                (user_id IN (
                    SELECT user_id FROM assessments
                    WHERE type = 'post_test'
                ))
            )
        ");
        
        if (empty($staleTests)) {
            $this->info('No stale tests found.');
        } else {
            $this->info('Found ' . count($staleTests) . ' stale tests to clean up.');
            
            foreach ($staleTests as $test) {
                try {
                    // Use direct SQL to update without triggering constraints
                    DB::statement("
                        UPDATE post_test_progress
                        SET is_active = 0, completed = 1, completed_at = NOW()
                        WHERE id = ?
                    ", [$test->id]);
                    
                    $this->info("Cleaned up stale test ID {$test->id}");
                } catch (\Exception $e) {
                    $this->error("Error cleaning up test ID {$test->id}: " . $e->getMessage());
                }
            }
        }
        
        // Step 4: Force complete any test with results
        $this->info('Checking for tests with results...');
        
        $testsWithResults = DB::select("
            SELECT p.id, p.user_id, p.language, p.level
            FROM post_test_progress p
            JOIN assessments a ON a.user_id = p.user_id
                AND a.language = p.language
                AND a.level = p.level
                AND a.type = 'post_test'
            WHERE p.is_active = 1 AND p.completed = 0
            AND a.created_at >= p.start_time
        ");
        
        if (empty($testsWithResults)) {
            $this->info('No tests with results found.');
        } else {
            $this->info('Found ' . count($testsWithResults) . ' tests with results.');
            
            foreach ($testsWithResults as $test) {
                try {
                    // Use direct SQL to update without triggering constraints
                    DB::statement("
                        UPDATE post_test_progress
                        SET is_active = 0, completed = 1, completed_at = NOW()
                        WHERE id = ?
                    ", [$test->id]);
                    
                    $this->info("Marked test ID {$test->id} with results as completed");
                } catch (\Exception $e) {
                    $this->error("Error marking test ID {$test->id} as completed: " . $e->getMessage());
                }
            }
        }
        
        // Finally, check if we fixed everything
        $remainingActive = DB::select("
            SELECT COUNT(*) as count
            FROM post_test_progress
            WHERE is_active = 1
        ")[0]->count;
        
        $this->info("Repair completed. {$remainingActive} active tests remain in the system.");
        
        return 0;
    }
} 