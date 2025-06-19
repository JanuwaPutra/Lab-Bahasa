<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\PostTestProgress;
use App\Models\Question;
use App\Models\TestSettings;
use App\Models\TeacherLanguage;
use DateTimeZone;
use DateTime;
use Exception;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // Check if the user is authenticated and has admin role
            if (!auth()->check() || auth()->user()->role !== 'admin') {
                return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
            }
            
            return $next($request);
        });
    }

    /**
     * Display a list of users for role management.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function roleManagement(Request $request)
    {
        $query = User::query();
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $users = $query->orderBy('name')->paginate(10);
        
        return view('admin.role_management', compact('users'));
    }

    /**
     * Update user role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:student,teacher,admin',
        ]);
        
        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();
        
        return redirect()->route('admin.role.management')
            ->with('success', 'Role pengguna berhasil diperbarui.');
    }
    
    /**
     * Display teacher language settings page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function teacherLanguageSettings(Request $request)
    {
        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        $teacherLanguages = TeacherLanguage::with('teacher')->get();
        
        $languages = [
            'id' => 'Indonesia',
            'en' => 'Inggris',
            'ru' => 'Rusia'
        ];
        
        $levels = [
            1 => 'Beginner',
            2 => 'Intermediate',
            3 => 'Advanced'
        ];
        
        return view('admin.teacher_language_settings', compact('teachers', 'teacherLanguages', 'languages', 'levels'));
    }
    
    /**
     * Update teacher language settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateTeacherLanguageSettings(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'language' => 'required|in:id,en,ru',
            'level' => 'required|in:1,2,3',
        ]);
        
        TeacherLanguage::updateOrCreate(
            [
                'teacher_id' => $request->teacher_id,
                'language' => $request->language,
            ],
            [
                'level' => $request->level,
            ]
        );
        
        return redirect()->route('admin.teacher-language.settings')
            ->with('success', 'Pengaturan bahasa guru berhasil diperbarui.');
    }

    /**
     * Display post-test monitoring page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function postTestMonitoring(Request $request)
    {
        // Get available languages and levels for filtering
        $languages = [
            'id' => 'Indonesia',
            'en' => 'Inggris',
            'ru' => 'Rusia'
        ];
        
        $levels = [
            1 => 'Beginner',
            2 => 'Intermediate',
            3 => 'Advanced'
        ];
        
        // Get filter values from request
        $selectedLanguage = $request->query('language');
        $selectedLevel = $request->query('level');
        
        return view('admin.post_test_monitoring', compact('languages', 'levels', 'selectedLanguage', 'selectedLevel'));
    }
    
    /**
     * Get real-time post-test data for monitoring.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostTestData(Request $request)
    {
        try {
            // Debug message
            \Log::info('Starting getPostTestData method');
            
            // Check if user is admin
            if (!auth()->check() || auth()->user()->role !== 'admin') {
                \Log::warning('Unauthorized access to post-test monitoring data', [
                    'user_id' => auth()->id() ?? 'unauthenticated',
                    'user_role' => auth()->check() ? auth()->user()->role : 'none'
                ]);
                return response()->json([
                    'error' => 'Unauthorized access',
                    'active_tests' => []
                ], 403);
            }
            
            // Get filter parameters
            $language = $request->query('language');
            $level = $request->query('level');
            
            // Debug info
            \Log::info('Fetching post-test monitoring data', [
                'language_filter' => $language,
                'level_filter' => $level
            ]);
            
            // First, run a thorough cleanup of all stale test data
            $this->cleanupStaleTestData();
            
            try {
                // Fix any stale records first
                $staleRecords = \App\Models\PostTestProgress::where('is_active', true)
                    ->where('completed', true)
                    ->count();
                        
                if ($staleRecords > 0) {
                    \Log::warning('Found stale post-test records', [
                        'count' => $staleRecords
                    ]);
                    
                    // Fix stale records using direct SQL - two-step approach to avoid constraint issues
                    // First get the IDs of stale records
                    $staleIds = \DB::select("SELECT id FROM post_test_progress WHERE completed = 1 AND is_active = 1");
                    
                    // Then update them one by one
                    foreach ($staleIds as $record) {
                        \DB::statement("UPDATE post_test_progress SET is_active = 0 WHERE id = ?", [$record->id]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error fixing stale records', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue execution, don't let this stop the whole process
            }
            
            try {
                // Also check for test results indicating completed tests
                $completedWithResults = \DB::select("
                    SELECT p.id 
                    FROM post_test_progress p 
                    JOIN assessments a ON a.user_id = p.user_id AND a.language = p.language AND a.level = p.level
                    WHERE p.is_active = 1 
                    AND a.type = 'post_test' 
                    AND a.created_at >= p.start_time
                ");
                
                if (count($completedWithResults) > 0) {
                    \Log::warning('Found tests with results that should be marked completed', [
                        'count' => count($completedWithResults)
                    ]);
                    
                    // Mark these as completed and inactive
                    foreach ($completedWithResults as $record) {
                        try {
                            \DB::statement("UPDATE post_test_progress SET is_active = 0, completed = 1 WHERE id = ?", [$record->id]);
                            \Log::info("Marked test as completed based on assessment results", ['id' => $record->id]);
                        } catch (\Exception $e) {
                            \Log::error("Error marking test as completed", [
                                'id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error fixing tests with results', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue execution, don't let this stop the whole process
            }
            
            try {
                // Fix orphaned records without users
                // Use a safer two-step approach
                $orphanedIds = \DB::select("
                    SELECT p.id 
                    FROM post_test_progress p 
                    LEFT JOIN users u ON p.user_id = u.id 
                    WHERE p.is_active = 1 AND u.id IS NULL
                ");
                
                if (count($orphanedIds) > 0) {
                    \Log::warning('Found orphaned post-test records', [
                        'count' => count($orphanedIds)
                    ]);
                    
                    // Update them one by one
                    foreach ($orphanedIds as $record) {
                        \DB::statement("UPDATE post_test_progress SET is_active = 0 WHERE id = ?", [$record->id]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error fixing orphaned records', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue execution, don't let this stop the whole process
            }
            
            // Build query using the most direct approach
            // IMPORTANT: Make sure we're only querying active, non-completed tests
            $queryConditions = ['p.is_active = 1', 'p.completed = 0', 'u.id IS NOT NULL'];
            $queryParams = [];
            
            if ($language) {
                $queryConditions[] = 'p.language = ?';
                $queryParams[] = $language;
            }
            
            if ($level) {
                $queryConditions[] = 'p.level = ?';
                $queryParams[] = $level;
            }
            
            $whereClause = implode(' AND ', $queryConditions);
            
            \Log::info('SQL query conditions', [
                'conditions' => $queryConditions,
                'params' => $queryParams,
                'where_clause' => $whereClause
            ]);
            
            // Get active tests with user information using direct SQL for better performance
            $sql = "
                SELECT 
                    p.id, p.user_id, p.language, p.level, p.start_time, 
                    p.answers, p.completed, p.is_active, p.created_at, p.updated_at,
                    u.id as user_id, u.name as user_name
                FROM post_test_progress p
                JOIN users u ON p.user_id = u.id
                WHERE $whereClause
                ORDER BY p.start_time DESC
                LIMIT 100
            ";
            
            \Log::info('SQL query', ['sql' => $sql]);
            
            try {
                $activeTestsData = \DB::select($sql, $queryParams);
                
                \Log::info('Post-test monitoring raw results', [
                    'count' => count($activeTestsData),
                    'ids' => array_map(function($record) { return $record->id; }, $activeTestsData),
                    'user_ids' => array_map(function($record) { return $record->user_id; }, $activeTestsData)
                ]);
            } catch (\Exception $e) {
                \Log::error('Error executing SQL query', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'sql' => $sql,
                    'params' => $queryParams
                ]);
                throw $e; // Re-throw to handle in the main catch block
            }
            
            $activeTests = [];
            
            foreach ($activeTestsData as $progress) {
                try {
                    // Skip any tests that might have been completed while we were processing
                    if (!empty($progress->completed) && $progress->completed == 1) {
                        \Log::info("Skipping completed test", ['id' => $progress->id]);
                        continue;
                    }
                    
                    // Get questions for this level and language
                    $questions = \App\Models\Question::active()
                        ->byAssessmentType('post_test')
                        ->byLevel($progress->level)
                        ->byLanguage($progress->language)
                        ->count();
                    
                    // Calculate elapsed seconds properly
                    $startTime = new \DateTime($progress->start_time);
                    $currentTime = now();
                    
                    // Get time limit for this test
                    $timeLimit = \App\Models\TestSettings::getTimeLimit('post_test', $progress->language) ?: 45;
                    $timeLimitSeconds = $timeLimit * 60;
                    
                    // Parse answers safely
                    $answers = [];
                    $remainingSecondsFromDb = null;
                    $isCompleted = false;
                    
                    if (!empty($progress->answers)) {
                        try {
                            $answers = json_decode($progress->answers, true) ?: [];
                            if (!is_array($answers)) {
                                $answers = [];
                            }
                            
                            // Check if remaining seconds is stored in answers
                            if (isset($answers['_remaining_seconds'])) {
                                $remainingSecondsFromDb = intval($answers['_remaining_seconds']);
                                // Remove this from the actual answers count
                                unset($answers['_remaining_seconds']);
                                
                                // Log the remaining seconds from database for debugging
                                \Log::debug('Remaining seconds from DB', [
                                    'progress_id' => $progress->id,
                                    'user_id' => $progress->user_id,
                                    'remaining_seconds' => $remainingSecondsFromDb
                                ]);
                            }
                            
                            // Check if test has been marked as completed in answers
                            if (isset($answers['_completed']) && $answers['_completed'] === true) {
                                $isCompleted = true;
                                // Mark the test as completed in the database and skip it
                                \DB::statement("UPDATE post_test_progress SET is_active = 0, completed = 1 WHERE id = ?", [$progress->id]);
                                \Log::info("Test marked completed via _completed flag", ['id' => $progress->id]);
                                continue;
                            }
                        } catch (\Exception $e) {
                            \Log::error('Error parsing answers JSON', [
                                'error' => $e->getMessage(),
                                'progress_id' => $progress->id,
                                'answers_raw' => $progress->answers
                            ]);
                            $answers = [];
                        }
                    }
                    
                    // Calculate remaining time - prioritize the value from database if available
                    $remainingSeconds = null;
                    
                    if ($remainingSecondsFromDb !== null) {
                        // Use the value from database
                        $remainingSeconds = max(0, $remainingSecondsFromDb);
                        \Log::debug('Using remaining seconds from database', [
                            'progress_id' => $progress->id,
                            'remaining_seconds' => $remainingSeconds,
                            'user_id' => $progress->user_id
                        ]);
                    } else {
                        // Calculate based on elapsed time
                        $elapsedSeconds = max(0, $currentTime->getTimestamp() - $startTime->getTimestamp());
                        $remainingSeconds = max(0, $timeLimitSeconds - $elapsedSeconds);
                        \Log::debug('Calculated remaining seconds from elapsed time', [
                            'progress_id' => $progress->id,
                            'elapsed_seconds' => $elapsedSeconds,
                            'time_limit_seconds' => $timeLimitSeconds,
                            'remaining_seconds' => $remainingSeconds,
                            'user_id' => $progress->user_id
                        ]);
                    }
                    
                    // If remaining time is 0, this test is likely completed - check completion again
                    if ($remainingSeconds <= 0) {
                        try {
                            // Update the record to mark it as completed
                            // Use a different approach that avoids the unique constraint issue
                            // First clear the is_active flag, then set completed
                            \DB::statement("UPDATE post_test_progress SET is_active = 0 WHERE id = ?", [$progress->id]);
                            \DB::statement("UPDATE post_test_progress SET completed = 1 WHERE id = ?", [$progress->id]);
                            
                            // Log successful update
                            \Log::info('Test marked as completed due to time expiration', ['progress_id' => $progress->id]);
                            
                            // Skip this test - it's now completed
                            continue;
                        } catch (\Exception $e) {
                            \Log::error('Error marking test as completed', [
                                'error' => $e->getMessage(),
                                'progress_id' => $progress->id
                            ]);
                            // Continue processing anyway
                        }
                    }
                    
                    // Additional check - if test start time is too old (more than 3 hours), it's likely stale
                    $testAgeInSeconds = $currentTime->getTimestamp() - $startTime->getTimestamp();
                    if ($testAgeInSeconds > (3 * 60 * 60)) { // 3 hours
                        \Log::warning('Test too old, marking as inactive', [
                            'progress_id' => $progress->id, 
                            'age_in_hours' => round($testAgeInSeconds / 3600, 1)
                        ]);
                        \DB::statement("UPDATE post_test_progress SET is_active = 0 WHERE id = ?", [$progress->id]);
                        continue;
                    }
                    
                    $remainingMinutes = floor($remainingSeconds / 60);
                    $remainingSecondsRemainder = $remainingSeconds % 60;
                    
                    // Calculate progress percentage
                    $answeredCount = count($answers);
                    $progressPercentage = 0;
                    if ($questions > 0 && $answeredCount > 0) {
                        $progressPercentage = round(($answeredCount / $questions) * 100);
                    }
                    
                    // Calculate time percentage based on remaining time
                    $timePercentage = $timeLimitSeconds > 0 ? 
                        min(100, round((($timeLimitSeconds - $remainingSeconds) / $timeLimitSeconds) * 100)) : 0;
                    
                    // Log for debugging
                    \Log::info('Post-test monitoring data calculated', [
                        'user_id' => $progress->user_id,
                        'user_name' => $progress->user_name,
                        'language' => $progress->language,
                        'level' => $progress->level,
                        'start_time' => $startTime,
                        'time_limit_seconds' => $timeLimitSeconds,
                        'remaining_seconds' => $remainingSeconds,
                        'remaining_seconds_from_db' => $remainingSecondsFromDb,
                        'answers' => count($answers),
                        'progress_percentage' => $progressPercentage
                    ]);
                    
                    // Language name mapping
                    $languageNames = [
                        'id' => 'Indonesia',
                        'en' => 'Inggris',
                        'ru' => 'Rusia'
                    ];
                    
                    // Level name mapping
                    $levelNames = [
                        1 => 'Beginner',
                        2 => 'Intermediate',
                        3 => 'Advanced'
                    ];
                    
                    // Format the start time in Asia/Jakarta timezone
                    try {
                        $jakartaTimezone = new \DateTimeZone('Asia/Jakarta');
                        $startTime->setTimezone($jakartaTimezone);
                        $jakartaStartTime = $startTime->format('H:i:s (d/m/Y)');
                    } catch (\Exception $e) {
                        \Log::error('Error formatting timezone', [
                            'error' => $e->getMessage(),
                            'start_time' => $progress->start_time
                        ]);
                        $jakartaStartTime = $progress->start_time;
                    }
                    
                    // Add to active tests
                    $activeTests[] = [
                        'student_id' => $progress->user_id,
                        'student_name' => $progress->user_name,
                        'language' => $progress->language,
                        'language_name' => $languageNames[$progress->language] ?? $progress->language,
                        'level' => $progress->level,
                        'level_name' => $levelNames[$progress->level] ?? "Level {$progress->level}",
                        'start_time' => $jakartaStartTime,
                        'time_limit' => $timeLimit,
                        'questions_total' => $questions,
                        'questions_answered' => $answeredCount,
                        'progress_percentage' => $progressPercentage,
                        'time_percentage' => $timePercentage,
                        'remaining_time' => sprintf('%02d:%02d', $remainingMinutes, $remainingSecondsRemainder)
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error processing individual test data', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'progress_id' => $progress->id ?? 'unknown',
                        'user_id' => $progress->user_id ?? 'unknown'
                    ]);
                    // Skip this test and continue with others
                }
            }
            
            \Log::info('Successfully completed getPostTestData method', [
                'active_tests_count' => count($activeTests)
            ]);
            
            return response()->json([
                'active_tests' => $activeTests,
                'timestamp' => now()->timestamp
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in post-test monitoring data API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Server error: ' . $e->getMessage(),
                'active_tests' => []
            ], 500);
        }
    }
    
    /**
     * Thoroughly clean up stale post-test data
     */
    private function cleanupStaleTestData()
    {
        try {
            \Log::info('Running thorough stale test data cleanup');
            
            // 1. Mark all completed tests as inactive
            $completedTests = \DB::select("SELECT id FROM post_test_progress WHERE completed = 1 AND is_active = 1");
            foreach ($completedTests as $test) {
                \DB::statement("UPDATE post_test_progress SET is_active = 0 WHERE id = ?", [$test->id]);
            }
            
            // 2. Check for tests with corresponding assessment results (these are definitely completed)
            $testsWithResults = \DB::select("
                SELECT p.id 
                FROM post_test_progress p 
                JOIN assessments a ON a.user_id = p.user_id 
                    AND a.language = p.language 
                    AND a.level = p.level 
                    AND a.type = 'post_test'
                WHERE p.is_active = 1 
                AND a.created_at >= p.start_time
            ");
            
            foreach ($testsWithResults as $test) {
                \DB::statement("UPDATE post_test_progress SET is_active = 0, completed = 1 WHERE id = ?", [$test->id]);
                \Log::info("Marked test as completed based on assessment results", ['id' => $test->id]);
            }
            
            // 3. Check for tests with expired time (old tests)
            $threeHoursAgo = now()->subHours(3)->format('Y-m-d H:i:s');
            $oldTests = \DB::select("SELECT id FROM post_test_progress WHERE is_active = 1 AND start_time < ?", [$threeHoursAgo]);
            foreach ($oldTests as $test) {
                \DB::statement("UPDATE post_test_progress SET is_active = 0, completed = 1 WHERE id = ?", [$test->id]);
            }
            
            // 4. Handle tests with time expired based on the time limit
            $testsToCheck = \DB::select("
                SELECT id, user_id, start_time, language 
                FROM post_test_progress 
                WHERE is_active = 1 AND completed = 0
            ");
            
            foreach ($testsToCheck as $test) {
                try {
                    $startTime = new \DateTime($test->start_time);
                    $currentTime = now();
                    $elapsedSeconds = $currentTime->getTimestamp() - $startTime->getTimestamp();
                    
                    // Get time limit for this test
                    $timeLimit = \App\Models\TestSettings::getTimeLimit('post_test', $test->language) ?: 45;
                    $timeLimitSeconds = $timeLimit * 60;
                    
                    if ($elapsedSeconds > $timeLimitSeconds) {
                        \Log::info("Test time limit exceeded, marking as completed", [
                            'id' => $test->id,
                            'elapsed_seconds' => $elapsedSeconds,
                            'time_limit_seconds' => $timeLimitSeconds
                        ]);
                        \DB::statement("UPDATE post_test_progress SET is_active = 0, completed = 1 WHERE id = ?", [$test->id]);
                    }
                } catch (\Exception $e) {
                    \Log::error("Error checking time limit for test", [
                        'id' => $test->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // 5. Fix orphaned tests (no associated user)
            \DB::statement("
                UPDATE post_test_progress p
                LEFT JOIN users u ON p.user_id = u.id
                SET p.is_active = 0, p.completed = 1
                WHERE p.is_active = 1 AND u.id IS NULL
            ");
            
            // 6. Look for other indications that a test is completed (answer data)
            $testsToCheckForCompletion = \DB::select("
                SELECT id, answers
                FROM post_test_progress
                WHERE is_active = 1 AND completed = 0
            ");
            
            foreach ($testsToCheckForCompletion as $test) {
                try {
                    if (!empty($test->answers)) {
                        $answers = json_decode($test->answers, true);
                        // Check if answers contain completion flag
                        if (is_array($answers) && isset($answers['_completed']) && $answers['_completed'] === true) {
                            \Log::info("Test marked as completed based on _completed flag in answers", ['id' => $test->id]);
                            \DB::statement("UPDATE post_test_progress SET is_active = 0, completed = 1 WHERE id = ?", [$test->id]);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("Error checking completion status in answers", [
                        'id' => $test->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            \Log::info('Stale test data cleanup completed');
        } catch (\Exception $e) {
            \Log::error('Error in cleanupStaleTestData', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function getPostTestMonitoring()
    {
        try {
            // Fix any constraint issues first using direct SQL
            try {
                \Log::info('Running direct constraint cleanup before monitoring');
                
                // First try to force clean up any conflicting records
                \DB::unprepared("
                    UPDATE post_test_progress 
                    SET is_active = 0, completed = 1, completed_at = NOW()
                    WHERE is_active = 1 AND completed = 1
                ");
                
                // Clean up any tests that have assessment results
                \DB::unprepared("
                    UPDATE post_test_progress p
                    JOIN assessments a ON 
                        a.user_id = p.user_id AND 
                        a.language = p.language AND 
                        a.level = p.level AND 
                        a.type = 'post_test' AND
                        a.created_at >= p.start_time
                    SET p.is_active = 0, p.completed = 1, p.completed_at = NOW()
                    WHERE p.is_active = 1
                ");
                
                // Clean up any duplicate active tests, keeping only the most recent
                $duplicateUsers = \DB::select("
                    SELECT user_id, language, level
                    FROM post_test_progress
                    WHERE is_active = 1
                    GROUP BY user_id, language, level
                    HAVING COUNT(*) > 1
                ");
                
                foreach ($duplicateUsers as $user) {
                    $tests = \DB::select("
                        SELECT id 
                        FROM post_test_progress
                        WHERE user_id = ? AND language = ? AND level = ? AND is_active = 1
                        ORDER BY created_at DESC
                    ", [$user->user_id, $user->language, $user->level]);
                    
                    // Keep the first one (most recent) and deactivate others
                    for ($i = 1; $i < count($tests); $i++) {
                        \DB::unprepared("
                            UPDATE post_test_progress
                            SET is_active = 0, completed = 1, completed_at = NOW()
                            WHERE id = {$tests[$i]->id}
                        ");
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error during constraint cleanup', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Run a cleanup of any stale tests first
            \Log::info('Running cleanup check before post-test monitoring');
            
            try {
                // Call the cleanup command directly
                \Artisan::call('tests:cleanup');
                \Log::info('Cleanup command completed before monitoring');
            } catch (\Exception $e) {
                \Log::error('Error running cleanup command', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Get currently active tests
            $activeTests = \DB::select("
                SELECT 
                    p.id, 
                    p.user_id, 
                    p.language, 
                    p.level, 
                    p.start_time, 
                    p.answers,
                    u.name as user_name,
                    u.profile_picture as user_picture
                FROM post_test_progress p
                JOIN users u ON p.user_id = u.id
                WHERE p.is_active = 1 AND p.completed = 0
                ORDER BY p.start_time DESC
            ");
            
            // Triple check - remove any tests that have assessment results
            $filteredTests = [];
            $removedCount = 0;
            
            foreach ($activeTests as $test) {
                // Check if there's an assessment result for this test
                $hasResults = \DB::select("
                    SELECT COUNT(*) as count
                    FROM assessments 
                    WHERE user_id = ? AND language = ? AND level = ? AND type = 'post_test'
                    AND created_at >= ?
                ", [$test->user_id, $test->language, $test->level, $test->start_time])[0]->count > 0;
                
                if ($hasResults) {
                    // This test should be marked as completed
                    \Log::info('Test has assessment results but is still marked as active, fixing', [
                        'test_id' => $test->id,
                        'user_id' => $test->user_id
                    ]);
                    
                    try {
                        // Use unprepared statement to bypass constraints
                        \DB::unprepared("
                            UPDATE post_test_progress 
                            SET is_active = 0, completed = 1, completed_at = NOW() 
                            WHERE id = {$test->id}
                        ");
                        $removedCount++;
                    } catch (\Exception $e) {
                        \Log::error('Error fixing test with results', [
                            'error' => $e->getMessage(),
                            'test_id' => $test->id
                        ]);
                        
                        // Try last resort direct deletion
                        try {
                            \DB::unprepared("
                                DELETE FROM post_test_progress
                                WHERE id = {$test->id}
                            ");
                            $removedCount++;
                            \Log::info('Deleted test as last resort', [
                                'test_id' => $test->id
                            ]);
                        } catch (\Exception $e2) {
                            \Log::error('Failed even to delete test', [
                                'error' => $e2->getMessage(),
                                'test_id' => $test->id
                            ]);
                        }
                    }
                    
                    continue; // Skip this test from the results
                }
                
                // Check if the test has a completion flag in the answers
                if (!empty($test->answers)) {
                    try {
                        $answers = json_decode($test->answers, true);
                        if (is_array($answers) && isset($answers['_completed']) && $answers['_completed'] === true) {
                            \Log::info('Test has completion flag in answers, marking as completed', [
                                'test_id' => $test->id,
                                'user_id' => $test->user_id
                            ]);
                            
                            \DB::unprepared("
                                UPDATE post_test_progress 
                                SET is_active = 0, completed = 1, completed_at = NOW() 
                                WHERE id = {$test->id}
                            ");
                            $removedCount++;
                            continue; // Skip this test
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error checking completion flag in answers', [
                            'error' => $e->getMessage(),
                            'test_id' => $test->id
                        ]);
                    }
                }
                
                // Check if this test is too old (3+ hours)
                $startTime = new \DateTime($test->start_time);
                $currentTime = new \DateTime();
                $timeDiff = $currentTime->getTimestamp() - $startTime->getTimestamp();
                $timeLimit = 3 * 60 * 60; // 3 hours
                
                if ($timeDiff > $timeLimit) {
                    \Log::info('Test is too old (3+ hours), marking as completed', [
                        'test_id' => $test->id,
                        'user_id' => $test->user_id,
                        'start_time' => $test->start_time,
                        'elapsed_time' => $timeDiff
                    ]);
                    
                    try {
                        \DB::unprepared("
                            UPDATE post_test_progress 
                            SET is_active = 0, completed = 1, completed_at = NOW() 
                            WHERE id = {$test->id}
                        ");
                        $removedCount++;
                    } catch (\Exception $e) {
                        \Log::error('Error fixing old test', [
                            'error' => $e->getMessage(),
                            'test_id' => $test->id
                        ]);
                    }
                    
                    continue; // Skip this test
                }
                
                // If we get here, the test is valid and active
                $filteredTests[] = $test;
            }
            
            if ($removedCount > 0) {
                \Log::info("Removed {$removedCount} tests during monitoring filtering");
            }
            
            // Format the tests for display
            $formattedTests = [];
            
            foreach ($filteredTests as $test) {
                // Get user info
                $userName = $test->user_name;
                $userPicture = $test->user_picture;
                
                // Calculate elapsed time
                $startTime = new \DateTime($test->start_time);
                $currentTime = new \DateTime();
                $timeDiff = $currentTime->getTimestamp() - $startTime->getTimestamp();
                
                // Format time nicely
                $hours = floor($timeDiff / 3600);
                $minutes = floor(($timeDiff % 3600) / 60);
                $seconds = $timeDiff % 60;
                
                $formattedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                
                // Get level name
                $levelName = '';
                switch ($test->level) {
                    case 1:
                        $levelName = 'Beginner';
                        break;
                    case 2:
                        $levelName = 'Intermediate';
                        break;
                    case 3:
                        $levelName = 'Advanced';
                        break;
                }
                
                // Get language name
                $languageName = '';
                switch ($test->language) {
                    case 'id':
                        $languageName = 'Indonesian';
                        break;
                    case 'en':
                        $languageName = 'English';
                        break;
                    case 'ru':
                        $languageName = 'Russian';
                        break;
                }
                
                // Calculate progress
                $answers = json_decode($test->answers, true) ?: [];
                $answeredQuestions = count($answers);
                
                // Get total questions for this level and language
                $totalQuestions = \DB::select("
                    SELECT COUNT(*) as count
                    FROM questions
                    WHERE assessment_type = 'post_test'
                    AND level = ?
                    AND language = ?
                    AND is_active = 1
                ", [$test->level, $test->language])[0]->count;
                
                $progress = $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100) : 0;
                
                // Add to formatted array
                $formattedTests[] = [
                    'id' => $test->id,
                    'user_id' => $test->user_id,
                    'user_name' => $userName,
                    'user_picture' => $userPicture,
                    'language' => $test->language,
                    'language_name' => $languageName,
                    'level' => $test->level,
                    'level_name' => $levelName,
                    'start_time' => $test->start_time,
                    'elapsed_time' => $formattedTime,
                    'elapsed_seconds' => $timeDiff,
                    'progress' => $progress,
                    'answered_questions' => $answeredQuestions,
                    'total_questions' => $totalQuestions
                ];
            }
            
            return response()->json([
                'success' => true,
                'active_tests' => $formattedTests
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getPostTestMonitoring', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error getting post-test monitoring data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 