<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForceDeletePostTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:force-delete {id? : The ID of the test to delete} {--user_id= : Delete tests for this user ID} {--language= : Filter by language} {--level= : Filter by level}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force delete a problematic post test';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Force delete post test');
        
        $id = $this->argument('id');
        $userId = $this->option('user_id');
        $language = $this->option('language');
        $level = $this->option('level');
        
        if ($id) {
            // Delete by ID
            $this->info("Deleting test with ID: {$id}");
            
            try {
                $deleted = DB::delete("DELETE FROM post_test_progress WHERE id = ?", [$id]);
                if ($deleted) {
                    $this->info("Successfully deleted test ID {$id}");
                } else {
                    $this->warn("No test found with ID {$id}");
                }
            } catch (\Exception $e) {
                $this->error("Error deleting test: " . $e->getMessage());
                Log::error('Error in ForceDeletePostTest', [
                    'error' => $e->getMessage(),
                    'test_id' => $id
                ]);
                return 1;
            }
        } elseif ($userId) {
            // Delete by user ID with optional filters
            $this->info("Deleting tests for user ID: {$userId}");
            
            $query = "DELETE FROM post_test_progress WHERE user_id = ?";
            $params = [$userId];
            
            if ($language) {
                $query .= " AND language = ?";
                $params[] = $language;
                $this->info("Filtering by language: {$language}");
            }
            
            if ($level) {
                $query .= " AND level = ?";
                $params[] = $level;
                $this->info("Filtering by level: {$level}");
            }
            
            try {
                $deleted = DB::delete($query, $params);
                if ($deleted) {
                    $this->info("Successfully deleted {$deleted} tests");
                } else {
                    $this->warn("No tests found matching criteria");
                }
            } catch (\Exception $e) {
                $this->error("Error deleting tests: " . $e->getMessage());
                Log::error('Error in ForceDeletePostTest', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'language' => $language,
                    'level' => $level
                ]);
                return 1;
            }
        } else {
            $this->error("You must provide either a test ID or a user ID");
            return 1;
        }
        
        $this->info('Operation completed');
        return 0;
    }
} 