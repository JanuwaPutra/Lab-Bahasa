<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetPostTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post-test:reset {--all} {--user=} {--language=} {--level=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset post-test data and fix stale records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting post-test data cleanup...');
        
        $resetAll = $this->option('all');
        $username = $this->option('user');
        $language = $this->option('language');
        $level = $this->option('level');
        
        if (!$resetAll && !$username && !$language && !$level) {
            $this->warn('No specific criteria provided. Use --all to reset all test data or provide specific filters.');
            return 1;
        }
        
        try {
            // Build the query conditions
            $conditions = ['1=1']; // Always true condition to start with
            $params = [];
            
            if ($username) {
                // Get user ID from username
                $userIds = DB::select('SELECT id FROM users WHERE name = ?', [$username]);
                if (empty($userIds)) {
                    $this->error("User with name '$username' not found.");
                    return 1;
                }
                
                $userId = $userIds[0]->id;
                $conditions[] = 'p.user_id = ?';
                $params[] = $userId;
                $this->info("Filtering by user: $username (ID: $userId)");
            }
            
            if ($language) {
                $conditions[] = 'p.language = ?';
                $params[] = $language;
                $this->info("Filtering by language: $language");
            }
            
            if ($level) {
                $conditions[] = 'p.level = ?';
                $params[] = $level;
                $this->info("Filtering by level: $level");
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            // First, find all the test IDs
            $query = "SELECT p.id FROM post_test_progress p WHERE $whereClause";
            $testIds = DB::select($query, $params);
            
            $count = count($testIds);
            if ($count === 0) {
                $this->warn('No matching post-test records found.');
                return 0;
            }
            
            $this->info("Found $count post-test records to process.");
            $bar = $this->output->createProgressBar($count);
            $bar->start();
            
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($testIds as $test) {
                try {
                    // Two-step approach to avoid constraint violations
                    // First deactivate the test
                    DB::statement('UPDATE post_test_progress SET is_active = 0 WHERE id = ?', [$test->id]);
                    
                    // Then mark it as completed
                    DB::statement('UPDATE post_test_progress SET completed = 1 WHERE id = ?', [$test->id]);
                    
                    $successCount++;
                } catch (\Exception $e) {
                    Log::error('Error resetting post-test', [
                        'id' => $test->id,
                        'error' => $e->getMessage()
                    ]);
                    $errorCount++;
                }
                
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            
            $this->info("Post-test data cleanup completed.");
            $this->info("Successful updates: $successCount");
            
            if ($errorCount > 0) {
                $this->warn("Failed updates: $errorCount (see logs for details)");
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error('Error in ResetPostTestData command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
} 