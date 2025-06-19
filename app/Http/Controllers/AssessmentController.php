<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    /**
     * Show the placement test form
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function placementTest()
    {
        return view('assessment.placement_test');
    }

    /**
     * Evaluate placement test
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function evaluatePlacementTest(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'language' => 'required|string|size:2',
        ]);

        $answers = $request->input('answers');
        $language = $request->input('language');
        
        // In a real implementation, you'd evaluate the answers and calculate the level
        $level = mt_rand(1, 6); // Placeholder: random level between 1-6
        $totalPoints = 100; // Total possible points
        $score = mt_rand(60, 100); // Placeholder: random score
        $percentage = ($score / $totalPoints) * 100;
        $passed = $percentage >= 70;
        
        // Save the assessment if authenticated
        if (Auth::check()) {
            Assessment::create([
                'user_id' => Auth::id(),
                'type' => 'placement',
                'level' => $level,
                'score' => $score,
                'total_points' => $totalPoints,
                'percentage' => $percentage,
                'passed' => $passed,
                'answers' => $answers,
                'language' => $language
            ]);
        }

        // Store in session for guest users
        session([
            'placement_completed' => true,
            'user_level' => $level,
            'placement_result' => [
                'score' => $score,
                'percentage' => $percentage,
                'passed' => $passed
            ],
            'placement_date' => now()
        ]);

        return redirect()->route('dashboard')->with('success', 'Placement test completed. Your level is ' . $level);
    }

    /**
     * Show the pretest form
     *
     * @param string $language The language code (id, en, ru)
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function pretest($language = null)
    {
        // Get language from parameter, session, or default to Indonesian
        $language = $language ?? session('language', 'id');
        
        // Validate language
        if (!in_array($language, ['id', 'en', 'ru'])) {
            $language = 'id';
        }
        
        // Store the selected language in session
        session(['language' => $language]);
        
        // Get language-specific questions
        $questions = \App\Models\Question::active()
            ->byAssessmentType('pretest')
            ->byLanguage($language)
            ->orderBy('level')
            ->get();
            
        // Get language name for display
        $languageNames = [
            'id' => 'Bahasa Indonesia',
            'en' => 'English',
            'ru' => 'Русский (Russian)'
        ];
        
        $languageName = $languageNames[$language] ?? 'Bahasa Indonesia';
        
        // Get time limit for this test type from settings
        $timeLimit = \App\Models\TestSettings::getTimeLimit('pretest', $language);
        
        // If no specific time limit is set, use default (30 minutes)
        if (!$timeLimit) {
            $timeLimit = 30;
        }
            
        return view('assessment.pretest', compact('questions', 'language', 'languageName', 'timeLimit'));
    }

    /**
     * Evaluate pretest
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function evaluatePretest(Request $request)
    {
        try {
            // First check if this is an empty submission
            $emptySubmission = $request->input('empty_submission', false);
            $timeExpired = $request->input('time_expired', false);
            
            // Log all incoming data for debugging
            \Log::info('Pretest evaluation raw request', [
                'all_data' => $request->all(),
                'empty_submission' => $emptySubmission,
                'time_expired' => $timeExpired
            ]);
            
            // Different validation rules based on submission type
            if ($emptySubmission) {
                $validator = \Validator::make($request->all(), [
                    'language' => 'required|string|in:id,en,ru',
                    'empty_submission' => 'boolean',
                    'time_expired' => 'boolean'
                ]);
            } else {
                $validator = \Validator::make($request->all(), [
                    'answers' => 'required|array',
                    'language' => 'required|string|in:id,en,ru',
                ]);
            }
            
            if ($validator->fails()) {
                \Log::error('Pretest validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $answers = $request->input('answers', []);
            $language = $request->input('language');
            $cheatingDetected = $request->input('cheating_detected', false);
            
            // Log the processed request data
            \Log::info('Pretest evaluation request processed', [
                'answers' => $answers,
                'language' => $language,
                'empty_submission' => $emptySubmission,
                'time_expired' => $timeExpired,
                'answer_count' => is_array($answers) ? count($answers) : 0
            ]);
            
            // Get questions from database
            $questions = \App\Models\Question::active()
                ->byAssessmentType('pretest')
                ->byLanguage($language)
                ->get();
            
            // Initialize score variables
            $score = 0;
            $correctCount = 0;
            $maxPossibleScore = 0;
            $totalQuestions = $questions->count();
            
            // Build detailed results
            $details = [];
            
            // If cheating detected or empty submission with time expired, skip evaluation and set score to 0
            if ($cheatingDetected || $emptySubmission) {
                $percentage = 0;
                $level = 1;
                $passed = false;
                
                \Log::info('Skipping evaluation due to ' . 
                    ($cheatingDetected ? 'cheating detection' : 'empty submission'));
            } else {
                foreach ($questions as $question) {
                    $questionId = $question->id;
                    $questionMaxScore = $question->points; // Default max score for the question
                    
                    // For multiple choice and true/false questions with option scores,
                    // calculate the maximum possible score from the option scores
                    if (($question->type == 'multiple_choice' || $question->type == 'true_false') && 
                        isset($question->option_scores) && !empty($question->option_scores)) {
                        // Find the highest score among options
                        $maxOptionScore = max($question->option_scores);
                        
                        // Make sure correct answer has at least 1 point for max score calculation
                        if ($question->type == 'multiple_choice') {
                            $correctAnswerScore = isset($question->option_scores[(int)$question->correct_answer]) ? 
                                (int)$question->option_scores[(int)$question->correct_answer] : 0;
                            if ($correctAnswerScore == 0) {
                                // If correct answer has 0 points, we need to add 1 to max possible score
                                $maxOptionScore = max($maxOptionScore, 1);
                            }
                        } else if ($question->type == 'true_false') {
                            $correctAnswerIndex = $question->correct_answer == 'true' ? 0 : 1;
                            $correctAnswerScore = isset($question->option_scores[$correctAnswerIndex]) ? 
                                (int)$question->option_scores[$correctAnswerIndex] : 0;
                            if ($correctAnswerScore == 0) {
                                // If correct answer has 0 points, we need to add 1 to max possible score
                                $maxOptionScore = max($maxOptionScore, 1);
                            }
                        }
                        
                        if ($maxOptionScore > 0) {
                            $questionMaxScore = $maxOptionScore;
                        }
                    }
                    
                    // Add this question's max score to the total max possible score
                    $maxPossibleScore += $questionMaxScore;
                    
                    // Skip if no answer provided
                    if (!isset($answers[$questionId])) {
                        continue;
                    }
                    
                    $userAnswer = $answers[$questionId];
                    $isCorrect = false;
                    $pointsEarned = 0;
                    
                    // Check if answer is correct based on question type
                    if ($question->type == 'multiple_choice') {
                        // For multiple choice, convert user answer to integer for comparison
                        $userAnswerInt = (int)$userAnswer;
                        $isCorrect = $userAnswerInt === (int)$question->correct_answer;
                        
                        // Use option_scores if available, otherwise use default points
                        if (isset($question->option_scores) && !empty($question->option_scores) && isset($question->option_scores[$userAnswerInt])) {
                            $pointsEarned = (int)$question->option_scores[$userAnswerInt];
                            
                            // If answer is correct but score is 0, give at least 1 point
                            if ($isCorrect && $pointsEarned == 0) {
                                $pointsEarned = 1;
                            }
                            
                            // Ensure points are never negative
                            $pointsEarned = max(0, $pointsEarned);
                        } else {
                            $pointsEarned = $isCorrect ? $question->points : 0;
                        }
                        
                        // Add points to the score regardless of whether the answer is "correct"
                        $score += $pointsEarned;
                        
                        // Only count as correct for the correct_count if the actual answer is correct
                        // Even if the score for the correct answer is 0, it should still count as correct
                        if ($isCorrect) {
                            $correctCount++;
                        }
                        
                        // Debug logging
                        \Log::info('Multiple choice answer check', [
                            'question_id' => $questionId,
                            'question_text' => $question->text,
                            'user_answer' => $userAnswer,
                            'user_answer_int' => $userAnswerInt,
                            'correct_answer' => $question->correct_answer,
                            'correct_answer_int' => (int)$question->correct_answer,
                            'is_correct' => $isCorrect,
                            'option_scores' => $question->option_scores,
                            'points_earned' => $pointsEarned,
                            'options' => $question->options
                        ]);
                    } elseif ($question->type == 'true_false') {
                        $isCorrect = $userAnswer == $question->correct_answer;
                        
                        // Use option_scores if available, otherwise use default points
                        if (isset($question->option_scores) && !empty($question->option_scores)) {
                            $scoreIndex = $userAnswer == 'true' ? 0 : 1;
                            $pointsEarned = isset($question->option_scores[$scoreIndex]) ? (int)$question->option_scores[$scoreIndex] : 0;
                            
                            // If answer is correct but score is 0, give at least 1 point
                            if ($isCorrect && $pointsEarned == 0) {
                                $pointsEarned = 1;
                            }
                            
                            // Ensure points are never negative
                            $pointsEarned = max(0, $pointsEarned);
                        } else {
                            $pointsEarned = $isCorrect ? $question->points : 0;
                        }
                        
                        // Add points to the score
                        $score += $pointsEarned;
                        
                        // Count as correct even if the score is 0
                        if ($isCorrect) {
                            $correctCount++;
                        }
                        
                        // Debug logging
                        \Log::info('True/False answer check', [
                            'question_id' => $questionId,
                            'question_text' => $question->text,
                            'user_answer' => $userAnswer,
                            'correct_answer' => $question->correct_answer,
                            'is_correct' => $isCorrect,
                            'option_scores' => $question->option_scores,
                            'points_earned' => $pointsEarned
                        ]);
                    } elseif ($question->type == 'fill_blank') {
                        $isCorrect = $userAnswer == $question->correct_answer;
                        
                        if ($isCorrect) {
                            $score += $question->points;
                            $correctCount++;
                            $pointsEarned = $question->points;
                        }
                        
                        // Debug logging
                        \Log::info('Fill Blank answer check', [
                            'question_id' => $questionId,
                            'question_text' => $question->text,
                            'user_answer' => $userAnswer,
                            'correct_answer' => $question->correct_answer,
                            'is_correct' => $isCorrect
                        ]);
                    } elseif ($question->type == 'essay') {
                        // For essay, just check if word count meets minimum requirement
                        $wordCount = str_word_count($userAnswer);
                        $isCorrect = $wordCount >= $question->min_words;
                        
                        if ($isCorrect) {
                            $score += $question->points;
                            $correctCount++;
                            $pointsEarned = $question->points;
                        }
                        
                        // Debug logging
                        \Log::info('Essay answer check', [
                            'question_id' => $questionId,
                            'question_text' => $question->text,
                            'user_answer' => $userAnswer,
                            'word_count' => $wordCount,
                            'min_words' => $question->min_words,
                            'is_correct' => $isCorrect
                        ]);
                    }
                    
                    // Add to details
                    $details[] = [
                        'question_id' => $questionId,
                        'question' => $question->text,
                        'user_answer' => $userAnswer,
                        'correct_answer' => $question->correct_answer,
                        'is_correct' => $isCorrect,
                        'type' => $question->type,
                        'points_earned' => $pointsEarned ?? ($isCorrect ? $question->points : 0)
                    ];
                }
                
                // Calculate percentage based on maximum possible score
                // Ensure we don't divide by zero
                $percentage = $maxPossibleScore > 0 ? ($score / $maxPossibleScore) * 100 : 0;
                $percentage = round($percentage, 2);
                
                // Clamp percentage to 0-100 range
                $percentage = max(0, min(100, $percentage));
                
                // Ensure score is never negative
                $score = max(0, $score);
                
                // Determine level based on percentage
                $level = 1;
                if ($percentage >= 80) {
                    $level = 3; // Advanced
                } elseif ($percentage >= 60) {
                    $level = 2; // Intermediate
                } else {
                    $level = 1; // Beginner
                }
                
                $passed = $percentage >= 70;
            }
            
            // Log all the calculation results
            \Log::info('Pretest calculation summary', [
                'score' => $score,
                'maxPossibleScore' => $maxPossibleScore,
                'percentage' => $percentage,
                'correctCount' => $correctCount,
                'totalQuestions' => $totalQuestions,
                'level' => $level,
                'passed' => $passed
            ]);
            
            // Save assessment to database
            $assessment = Assessment::create([
                'user_id' => Auth::id(),
                'type' => 'pretest',
                'level' => $level,
                'score' => $score,
                'total_points' => $maxPossibleScore,
                'percentage' => $percentage,
                'passed' => $passed,
                'answers' => $answers,
                'language' => $language,
                'correct_count' => $correctCount,
                'total_questions' => $questions->count(),
                'time_limit' => \App\Models\TestSettings::getTimeLimit('pretest', $language) ?: 30,
                'details' => $details
            ]);
            
            // Debug logging
            \Log::info('Pretest assessment created', [
                'user_id' => Auth::id(),
                'assessment_id' => $assessment->id,
                'type' => 'pretest',
                'level' => $level,
                'score' => $score,
                'percentage' => $percentage,
                'passed' => $passed,
                'language' => $language
            ]);

            // Return the result
            return response()->json([
                'level' => $level,
                'score' => $score,
                'total_points' => $maxPossibleScore,
                'percentage' => $percentage,
                'passed' => $passed,
                'correct_count' => $correctCount,
                'total_questions' => $questions->count(),
                'language' => $language
            ]);
        } catch (\Exception $e) {
            \Log::error('Pretest evaluation exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Safely deactivate all active tests for a user, language and level combination
     * This is a special handler to bypass the unique constraint
     * 
     * @param int $userId
     * @param string $language
     * @param int $level
     * @return bool
     */
    private function safelyDeactivateActiveTests($userId, $language, $level)
    {
        try {
            // Start a transaction
            \DB::beginTransaction();
            
            // Find all active tests for this user/language/level
            $activeTests = \DB::select(
                'SELECT id FROM post_test_progress WHERE user_id = ? AND language = ? AND level = ? AND is_active = 1',
                [$userId, $language, $level]
            );
            
            // If we have active tests, deactivate them one by one
            foreach ($activeTests as $test) {
                \DB::statement('UPDATE post_test_progress SET is_active = 0 WHERE id = ?', [$test->id]);
            }
            
            // Also deactivate ALL tests for this user/language/level as a safety measure
            // This is to handle the case where the unique constraint is in a weird state
            \DB::statement(
                'UPDATE post_test_progress SET is_active = 0 WHERE user_id = ? AND language = ? AND level = ?', 
                [$userId, $language, $level]
            );
            
            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Failed to safely deactivate tests', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'language' => $language,
                'level' => $level
            ]);
            return false;
        }
    }
    
    /**
     * Show the post-test view
     *
     * @param string $language The language code (id, en, ru)
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function postTest($language = null)
    {
        // Get language from parameter, session, or default to Indonesian
        $language = $language ?? session('language', 'id');
        
        // Validate language
        if (!in_array($language, ['id', 'en', 'ru'])) {
            $language = 'id';
        }
        
        // Store the selected language in session
        session(['language' => $language]);
        
        // Check current user level
        $user = auth()->user();
        $currentLevel = $user->getCurrentLevel($language) ?? 1;
        
        // Check for active tests (don't force create new ones)
        $existingActiveTest = \App\Models\PostTestProgress::where('user_id', $user->id)
            ->where('language', $language)
            ->where('level', $currentLevel)
            ->where('is_active', true)
            ->where('completed', false)
            ->first();
            
        // If there's already an active test, don't create a new one
        if (!$existingActiveTest) {
            try {
                \DB::beginTransaction();
                
                // Create a new post-test progress record
                $newProgress = \App\Models\PostTestProgress::create([
                    'user_id' => $user->id,
                    'language' => $language,
                    'level' => $currentLevel,
                    'start_time' => now(),
                    'answers' => [],
                    'completed' => false,
                    'is_active' => true
                ]);
                
                \DB::commit();
                
                \Log::info('FORCED new post-test progress record creation on page load', [
                    'progress_id' => $newProgress->id,
                    'user_id' => $user->id,
                    'language' => $language,
                    'level' => $currentLevel
                ]);
            } catch (\Exception $e) {
                \DB::rollBack();
                
                \Log::error('Failed to create new post-test progress record', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'language' => $language,
                    'level' => $currentLevel
                ]);
                
                // Continue without creating a new record - we'll try to use an existing one
            }
        } else {
            \Log::info('Using existing active post-test record instead of creating a new one', [
                'progress_id' => $existingActiveTest->id,
                'user_id' => $user->id,
                'language' => $language,
                'level' => $currentLevel
            ]);
        }
        
        // Get language-specific questions
        $questions = \App\Models\Question::active()
            ->byAssessmentType('post_test')
            ->byLevel($currentLevel)
            ->byLanguage($language)
            ->get();
            
        // Get language name for display
        $languageNames = [
            'id' => 'Bahasa Indonesia',
            'en' => 'English',
            'ru' => 'Русский (Russian)'
        ];
        
        $languageName = $languageNames[$language] ?? 'Bahasa Indonesia';
        
        // Get time limit for this test type from settings
        $timeLimit = \App\Models\TestSettings::getTimeLimit('post_test', $language);
        
        // If no specific time limit is set, use default (45 minutes)
        if (!$timeLimit) {
            $timeLimit = 45;
        }
            
        return view('assessment.post_test', compact('questions', 'currentLevel', 'language', 'languageName', 'timeLimit'));
    }

    /**
     * Evaluate post-test
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function evaluatePostTest(Request $request)
    {
        $user = auth()->user();
        $answers = $request->input('answers', []);
        $language = $request->input('language', session('language', 'id'));
        $elapsedTime = $request->input('elapsed_time');
        $timeExpired = $request->input('time_expired', false);
        $remainingSeconds = $request->input('remaining_seconds', 0);
        
        try {
            // Get user's current level
            $level = $user->getCurrentLevel($language);
            
            \Log::info('Post-test evaluation', [
                'user_id' => $user->id,
                'language' => $language,
                'level' => $level,
                'answers_count' => count($answers),
                'elapsed_time' => $elapsedTime,
                'time_expired' => $timeExpired,
                'remaining_seconds' => $remainingSeconds
            ]);
            
            // Get active post-test progress
            $progress = \App\Models\PostTestProgress::getActive($user->id, $language, $level);
            
            if (!$progress) {
                \Log::warning('No active post-test found during evaluation', [
                    'user_id' => $user->id,
                    'language' => $language,
                    'level' => $level
                ]);
                
                // Try to find any incomplete test
                $progress = \App\Models\PostTestProgress::where('user_id', $user->id)
                    ->where('language', $language)
                    ->where('level', $level)
                    ->where('completed', false)
                    ->latest()
                    ->first();
                    
                if (!$progress) {
                    \Log::error('No test found for evaluation, creating new one', [
                        'user_id' => $user->id,
                        'language' => $language,
                        'level' => $level
                    ]);
                    
                    // Create a new one as last resort
                    $progress = \App\Models\PostTestProgress::create([
                        'user_id' => $user->id,
                        'language' => $language,
                        'level' => $level,
                        'start_time' => now()->subMinutes(45), // Assume test was 45 min ago
                        'answers' => $answers,
                        'is_active' => true,
                        'completed' => false
                    ]);
                }
            }
            
            // Save the answers to the progress record
            $progress->answers = $answers;
            $progress->save();
            
            // Start a transaction for the evaluation process
            \DB::beginTransaction();
            
            // Evaluate the test scores
            // Get questions from database
            $questions = \App\Models\Question::active()
                ->byAssessmentType('post_test')
                ->byLevel($level)
                ->byLanguage($language)
                ->get();
                
            // Initialize score variables
            $score = 0;
            $correctCount = 0;
            $maxPossibleScore = 0;
            $totalQuestions = $questions->count();
            
            // Calculate scores
            foreach ($questions as $question) {
                $questionId = $question->id;
                $questionMaxScore = $question->points ?? 1; // Default max score for the question
                
                // Add this question's max score to the total max possible score
                $maxPossibleScore += $questionMaxScore;
                
                // Skip if no answer provided
                if (!isset($answers[$questionId])) {
                    continue;
                }
                
                $userAnswer = $answers[$questionId];
                $isCorrect = false;
                
                // Check if answer is correct based on question type
                if ($question->type == 'multiple_choice') {
                    // For multiple choice, convert user answer to integer for comparison
                    $userAnswerInt = (int)$userAnswer;
                    $isCorrect = $userAnswerInt === (int)$question->correct_answer;
                    
                    if ($isCorrect) {
                        $score += $questionMaxScore;
                        $correctCount++;
                    }
                } elseif ($question->type == 'true_false') {
                    $isCorrect = $userAnswer == $question->correct_answer;
                    
                    if ($isCorrect) {
                        $score += $questionMaxScore;
                        $correctCount++;
                    }
                } elseif ($question->type == 'fill_blank') {
                    $isCorrect = strtolower(trim($userAnswer)) == strtolower(trim($question->correct_answer));
                    
                    if ($isCorrect) {
                        $score += $questionMaxScore;
                        $correctCount++;
                    }
                } elseif ($question->type == 'essay') {
                    // For essay, just check if word count meets minimum requirement
                    $wordCount = str_word_count($userAnswer);
                    $isCorrect = $wordCount >= ($question->min_words ?? 50);
                    
                    if ($isCorrect) {
                        $score += $questionMaxScore;
                        $correctCount++;
                    }
                }
            }
            
            // Calculate percentage based on maximum possible score
            $percentage = $maxPossibleScore > 0 ? round(($score / $maxPossibleScore) * 100, 2) : 0;
            
            // Determine if passed (70% or higher)
            $passed = $percentage >= 70;
            
            // Level up if passed and not at max level
            $levelUp = false;
            if ($passed && $level < 3) {
                $newLevel = $level + 1;
                $user->setCurrentLevel($newLevel, $language);
                $levelUp = true;
            }
            
            // Create assessment record
            $assessment = \App\Models\Assessment::create([
                'user_id' => $user->id,
                'type' => 'post_test',
                'level' => $level,
                'language' => $language,
                'score' => $score,
                'total_points' => $maxPossibleScore,
                'percentage' => $percentage,
                'passed' => $passed,
                'answers' => $answers,
                'details' => [],
                'correct_count' => $correctCount,
                'total_questions' => $totalQuestions
            ]);
            
            // Mark the test as completed using our new method
            $markSuccess = $progress->markAsCompleted();
            
            if (!$markSuccess) {
                \Log::warning('Failed to mark test as completed using the model method, trying direct DB update', [
                    'test_id' => $progress->id
                ]);
                
                // Direct DB update as fallback
                try {
                    \DB::statement("UPDATE post_test_progress SET is_active = 0 WHERE id = ?", [$progress->id]);
                    \DB::statement("UPDATE post_test_progress SET completed = 1, completed_at = NOW() WHERE id = ?", [$progress->id]);
                } catch (\Exception $e) {
                    \Log::error('Failed direct DB update', [
                        'error' => $e->getMessage(),
                        'test_id' => $progress->id
                    ]);
                }
            }
            
            // Force delete any remaining active tests
            try {
                \DB::statement("
                    DELETE FROM post_test_progress 
                    WHERE user_id = ? AND language = ? AND level = ? AND is_active = 1
                ", [$user->id, $language, $level]);
            } catch (\Exception $e) {
                \Log::error('Error trying to force delete remaining active tests', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);
            }
            
            // Commit the transaction
            \DB::commit();
            
            // Return result with scores
            return response()->json([
                'success' => true,
                'message' => 'Post-test evaluation completed successfully',
                'score' => $score,
                'total_points' => $maxPossibleScore,
                'percentage' => $percentage,
                'passed' => $passed,
                'level_up' => $levelUp,
                'correct_count' => $correctCount,
                'total_questions' => $totalQuestions
            ]);
        } catch (\Exception $e) {
            // Rollback on error
            if (\DB::transactionLevel() > 0) {
                \DB::rollBack();
            }
            
            \Log::error('Error completing post-test evaluation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
                'language' => $language,
                'level' => $level
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error completing post-test evaluation: ' . $e->getMessage(),
                // Provide default values for UI display
                'score' => 0,
                'total_points' => 0,
                'percentage' => 0,
                'passed' => false,
                'level_up' => false,
                'correct_count' => 0,
                'total_questions' => 0
            ], 500);
        }
    }

    /**
     * Show the listening test form
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function listeningTest()
    {
        return view('assessment.listening_test');
    }

    /**
     * Evaluate listening test
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function evaluateListeningTest(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'language' => 'required|string|size:2',
        ]);

        // Implementation similar to other tests...
        
        return redirect()->route('dashboard')->with('success', 'Listening test completed.');
    }

    /**
     * Show the reading test form
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function readingTest()
    {
        return view('assessment.reading_test');
    }

    /**
     * Evaluate reading test
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function evaluateReadingTest(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'language' => 'required|string|size:2',
        ]);

        // Implementation similar to other tests...
        
        return redirect()->route('dashboard')->with('success', 'Reading test completed.');
    }

    /**
     * Show the speaking test form
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function speakingTest()
    {
        return view('assessment.speaking_test');
    }

    /**
     * Evaluate speaking test
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function evaluateSpeakingTest(Request $request)
    {
        $request->validate([
            'audio' => 'required',
            'language' => 'required|string|size:2',
        ]);

        // Implementation for speech evaluation...
        
        return redirect()->route('dashboard')->with('success', 'Speaking test completed.');
    }

    /**
     * Show progress report
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function progressReport()
    {
        $user = Auth::user();
        $assessments = [];
        
        if ($user) {
            $assessments = Assessment::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
                
            // Debug logging
            \Log::info('User assessments', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'assessment_count' => $assessments->count(),
                'pretest_exists' => $user->hasCompletedPretest(),
                'current_level' => $user->getCurrentLevel('id'),
                'pretest_date' => $user->pretestDate('id')
            ]);
        }
        
        return view('assessment.progress_report', compact('assessments'));
    }

    /**
     * API endpoints
     */
    public function apiPretest()
    {
        // Return questions for pretest
        return response()->json([
            'questions' => $this->getDummyQuestions('pretest'),
        ]);
    }

    public function apiEvaluatePretest(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'language' => 'required|string|size:2',
        ]);

        // Evaluate answers and return result
        return response()->json([
            'level' => mt_rand(1, 6),
            'score' => mt_rand(60, 100),
            'passed' => true,
        ]);
    }

    public function apiPostTest()
    {
        // Return questions for post-test
        return response()->json([
            'questions' => $this->getDummyQuestions('post_test'),
        ]);
    }

    public function apiEvaluatePostTest(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'language' => 'required|string|size:2',
        ]);

        // Evaluate answers and return result
        return response()->json([
            'level' => mt_rand(1, 6),
            'score' => mt_rand(60, 100),
            'passed' => true,
        ]);
    }

    public function apiPlacementTest()
    {
        // Return questions for placement test
        return response()->json([
            'questions' => $this->getDummyQuestions('placement'),
        ]);
    }

    public function apiEvaluatePlacement(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'language' => 'required|string|size:2',
        ]);

        // Evaluate answers and return result
        return response()->json([
            'level' => mt_rand(1, 6),
            'score' => mt_rand(60, 100),
            'passed' => true,
        ]);
    }

    public function apiListeningTest()
    {
        // Return questions for listening test
        return response()->json([
            'questions' => $this->getDummyQuestions('listening'),
            'audio_urls' => [
                'https://example.com/audio1.mp3',
                'https://example.com/audio2.mp3',
            ],
        ]);
    }

    public function apiReadingTest()
    {
        // Return questions for reading test
        return response()->json([
            'questions' => $this->getDummyQuestions('reading'),
            'passages' => [
                'This is a sample reading passage for testing reading comprehension.',
                'Another sample reading passage with different content.',
            ],
        ]);
    }

    public function apiSpeakingTest()
    {
        // Return prompts for speaking test
        return response()->json([
            'prompts' => [
                'Describe your favorite hobby.',
                'Talk about your family.',
                'Describe your daily routine.',
            ],
        ]);
    }

    public function apiProgressReport()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        
        $assessments = Assessment::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'assessments' => $assessments,
            'current_level' => $user->getCurrentLevel(),
        ]);
    }

    /**
     * Helper method to get dummy questions
     */
    private function getDummyQuestions($type)
    {
        // This would normally come from a database
        return [
            [
                'id' => 1,
                'question' => 'Sample question 1 for ' . $type,
                'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                'correct_answer' => 0,
            ],
            [
                'id' => 2,
                'question' => 'Sample question 2 for ' . $type,
                'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                'correct_answer' => 1,
            ],
            [
                'id' => 3,
                'question' => 'Sample question 3 for ' . $type,
                'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                'correct_answer' => 2,
            ],
        ];
    }

    /**
     * Show the grammar test.
     *
     * @return \Illuminate\View\View
     */
    public function grammarTest()
    {
        $language = session('language', 'id');
        $level = 1;
        
        if (auth()->check()) {
            $user = auth()->user();
            $level = $user->getCurrentLevel($language);
        }
        
        // Get questions for the grammar test
        $questions = $this->getQuestionsForTest('grammar', $level, $language);
        
        return view('assessment.grammar_test', [
            'language' => $language,
            'level' => $level,
            'questions' => $questions
        ]);
    }
    
    /**
     * Evaluate the grammar test.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function evaluateGrammarTest(Request $request)
    {
        $language = $request->input('language', 'id');
        $level = $request->input('level', 1);
        $answers = $request->input('answers', []);
        
        // Get questions for validation
        $questions = $this->getQuestionsForTest('grammar', $level, $language);
        
        // Calculate score
        $score = $this->calculateScore($questions, $answers);
        
        // Determine if passed
        $passed = $score['percentage'] >= 70;
        
        // Record assessment if user is logged in
        if (auth()->check()) {
            $user = auth()->user();
            
            $assessment = new Assessment([
                'user_id' => $user->id,
                'type' => 'grammar',
                'level' => $level,
                'language' => $language,
                'score' => $score['score'],
                'max_score' => $score['max_score'],
                'percentage' => $score['percentage'],
                'passed' => $passed,
                'answers' => $answers,
                'data' => [
                    'questions' => $questions
                ]
            ]);
            
            $assessment->save();
        }
        
        return view('assessment.grammar_test_result', [
            'language' => $language,
            'level' => $level,
            'score' => $score,
            'passed' => $passed,
            'questions' => $questions,
            'answers' => $answers
        ]);
    }

    /**
     * Save pretest answers during the test
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function savePretestAnswers(Request $request)
    {
        // Check if this is an empty submission
        $emptySubmission = $request->input('empty_submission', false);
        
        // Different validation rules based on submission type
        if ($emptySubmission) {
            $request->validate([
                'language' => 'required|string|in:id,en,ru',
                'empty_submission' => 'boolean'
            ]);
            $answers = [];
        } else {
            $request->validate([
                'answers' => 'required|array',
                'language' => 'required|string|in:id,en,ru',
            ]);
            $answers = $request->input('answers');
        }

        $language = $request->input('language');
        $forceClear = $request->input('force_clear', false);
        
        if (Auth::check()) {
            $user = Auth::user();
            
            // Save to session - only set start_time if not force_clear and no existing time
            $sessionStartTimeKey = 'pretest_start_time_' . $language;
            $sessionAnswersKey = 'pretest_answers_' . $language;
            
            if ($forceClear) {
                // Just clear answers without affecting time if this is a force clear
                session([$sessionAnswersKey => []]);
                Log::info('Pretest answers cleared (force)', [
                    'user_id' => $user->id,
                    'language' => $language
                ]);
            } else {
                // Normal save operation
                session([
                    $sessionAnswersKey => $answers,
                    $sessionStartTimeKey => session($sessionStartTimeKey, time())
                ]);
                
                Log::info('Pretest answers saved', [
                    'user_id' => $user->id,
                    'language' => $language,
                    'answer_count' => count($answers),
                    'empty_submission' => $emptySubmission,
                    'start_time' => session($sessionStartTimeKey)
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => $forceClear ? 'Answers cleared successfully' : 'Answers saved successfully'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }
    
    /**
     * Get saved pretest answers
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPretestAnswers(Request $request)
    {
        $language = $request->query('language', 'id');
        
        if (Auth::check()) {
            $user = Auth::user();
            $answers = session('pretest_answers_' . $language, []);
            
            // Log for debugging
            Log::info('Retrieved pretest answers', [
                'user_id' => $user->id,
                'language' => $language,
                'answer_count' => count($answers)
            ]);
            
            return response()->json([
                'success' => true,
                'answers' => $answers
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }
    
    /**
     * Get pretest timer status
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPretestTime(Request $request)
    {
        $language = $request->query('language', 'id');
        
        if (Auth::check()) {
            $user = Auth::user();
            $startTime = session('pretest_start_time_' . $language, null);
            
            // Log for debugging
            Log::info('Retrieved pretest time', [
                'user_id' => $user->id,
                'language' => $language,
                'start_time' => $startTime
            ]);
            
            return response()->json([
                'success' => true,
                'start_time' => $startTime
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }
    
    /**
     * Save post-test answers during the test
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function savePostTestAnswers(Request $request)
    {
        $user = auth()->user();
        $language = $request->input('language', session('language', 'id'));
        $answers = $request->input('answers', []);
        $isCompleted = $request->input('completed', false);
        $timeExpired = $request->input('time_expired', false);
        $remainingSeconds = $request->input('remaining_seconds', null);
        $forceComplete = $request->input('force_complete', false);
        $terminateTest = $request->input('terminate_test', false);
        
        // Validate language
        if (!in_array($language, ['id', 'en', 'ru'])) {
            $language = 'id';
        }
        
        // Get current level
        $level = $user->getCurrentLevel($language);
        
        // Log the save operation
        \Log::info('Saving post-test answers', [
            'user_id' => $user->id,
            'language' => $language,
            'level' => $level,
            'answers_count' => is_array($answers) ? count($answers) : 'not an array',
            'completed' => $isCompleted,
            'time_expired' => $timeExpired,
            'remaining_seconds' => $remainingSeconds,
            'force_complete' => $forceComplete,
            'terminate_test' => $terminateTest
        ]);
        
        try {
            // Special handling for terminate_test flag from retry button
            if ($terminateTest) {
                \Log::info('Terminate test flag detected, forcefully removing test from monitoring', [
                    'user_id' => $user->id,
                    'language' => $language,
                    'level' => $level
                ]);
                
                // Terminate any active tests for this user in this language and level
                try {
                    \DB::unprepared("
                        UPDATE post_test_progress 
                        SET is_active = 0, completed = 1, completed_at = NOW() 
                        WHERE user_id = {$user->id} AND language = '{$language}' AND level = {$level}
                    ");
                    
                    \Log::info('Successfully terminated all tests for user from monitoring');
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Tests terminated and removed from monitoring'
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error during test termination', [
                        'error' => $e->getMessage()
                    ]);
                    
                    // Try a different approach if the update fails
                    try {
                        \DB::unprepared("
                            DELETE FROM post_test_progress 
                            WHERE user_id = {$user->id} AND language = '{$language}' AND level = {$level}
                        ");
                        
                        \Log::info('Directly deleted all tests for user from monitoring');
                        
                        return response()->json([
                            'success' => true,
                            'message' => 'Tests forcefully deleted from monitoring'
                        ]);
                    } catch (\Exception $e2) {
                        \Log::error('Error during force delete from monitoring', [
                            'error' => $e2->getMessage()
                        ]);
                    }
                }
            }
            
            // Handle force complete flag (from client-side) with ultra-direct SQL
            if ($forceComplete) {
                \Log::info('Force complete flag detected, forcefully marking all active tests as completed', [
                    'user_id' => $user->id,
                    'language' => $language,
                    'level' => $level
                ]);
                
                // Use unprepared statement to bypass all constraints
                try {
                    \DB::unprepared("
                        UPDATE post_test_progress 
                        SET is_active = 0, completed = 1, completed_at = NOW() 
                        WHERE user_id = {$user->id} AND language = '{$language}' AND level = {$level}
                    ");
                    
                    \Log::info('Directly marked all tests as inactive and completed');
                    
                    // Also check for assessment results
                    $hasResults = \DB::select("
                        SELECT COUNT(*) as count
                        FROM assessments 
                        WHERE user_id = ? AND language = ? AND level = ? AND type = 'post_test'
                    ", [$user->id, $language, $level])[0]->count > 0;
                    
                    // If there are no results yet, create a default result
                    if (!$hasResults && $isCompleted) {
                        \Log::info('Creating default assessment result for forcefully completed test');
                        
                        \DB::table('assessments')->insert([
                            'user_id' => $user->id,
                            'type' => 'post_test',
                            'level' => $level,
                            'language' => $language,
                            'score' => 0,
                            'total_points' => 1,
                            'percentage' => 0,
                            'passed' => false,
                            'answers' => json_encode($answers),
                            'details' => json_encode([]),
                            'correct_count' => 0,
                            'total_questions' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Tests forcefully marked as completed'
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error during force complete with direct SQL', [
                        'error' => $e->getMessage()
                    ]);
                    
                    // Try a different approach - direct delete
                    try {
                        \DB::unprepared("
                            DELETE FROM post_test_progress 
                            WHERE user_id = {$user->id} AND language = '{$language}' AND level = {$level}
                        ");
                        
                        \Log::info('Directly deleted all tests for user');
                        
                        return response()->json([
                            'success' => true,
                            'message' => 'Tests forcefully deleted'
                        ]);
                    } catch (\Exception $e2) {
                        \Log::error('Error during force delete', [
                            'error' => $e2->getMessage()
                        ]);
                    }
                }
            }
            
            // Get current active test
            $progress = \App\Models\PostTestProgress::getActive($user->id, $language, $level);
            
            if (!$progress) {
                // If no active test found, create one
                $progress = \App\Models\PostTestProgress::getOrCreate($user->id, $language, $level);
                
                \Log::info('Created new post-test progress record during save', [
                    'progress_id' => $progress->id ?? 'unknown'
                ]);
            }
            
            // Process answers
            $answersData = is_array($answers) ? $answers : [];
            
            // Store remaining seconds in answers JSON for real-time monitoring
            if ($remainingSeconds !== null) {
                $answersData['_remaining_seconds'] = $remainingSeconds;
            }
            
            // Mark if completed in the answers metadata
            if ($isCompleted || $timeExpired) {
                $answersData['_completed'] = true;
            }
            
            // Set answers
            $progress->answers = $answersData;
            
            // Mark as completed if needed
            if ($isCompleted || $timeExpired) {
                $progress->completed = true;
                $progress->completed_at = now();
                $progress->is_active = false;
                
                // Double check with direct SQL to ensure it's removed from active monitoring
                // Use a two-step approach to avoid constraint issues
                try {
                    \DB::beginTransaction();
                    // First set is_active to false
                    \DB::statement("UPDATE post_test_progress SET is_active = 0 WHERE id = ?", [$progress->id]);
                    // Then mark as completed
                    \DB::statement("UPDATE post_test_progress SET completed = 1, completed_at = NOW() WHERE id = ?", [$progress->id]);
                    \DB::commit();
                    
                    \Log::info('Marked post-test as completed using two-step approach', [
                        'test_id' => $progress->id,
                        'user_id' => $user->id,
                        'is_time_expired' => $timeExpired
                    ]);
                } catch (\Exception $e) {
                    \DB::rollBack();
                    \Log::error('Error in two-step completion marking', [
                        'error' => $e->getMessage(),
                        'test_id' => $progress->id
                    ]);
                    
                    // Try with direct model save as fallback
                    try {
                        $progress->save();
                    } catch (\Exception $e2) {
                        \Log::error('Fallback save also failed', [
                            'error' => $e2->getMessage(),
                            'test_id' => $progress->id
                        ]);
                    }
                }
                
                // Also check for any other active tests for this user/language/level and mark them completed
                try {
                    $otherActiveTests = \App\Models\PostTestProgress::where('user_id', $user->id)
                        ->where('language', $language)
                        ->where('level', $level)
                        ->where('is_active', true)
                        ->where('id', '!=', $progress->id)
                        ->get();
                        
                    if ($otherActiveTests->count() > 0) {
                        \Log::warning('Found other active tests to mark as completed', [
                            'count' => $otherActiveTests->count(),
                            'user_id' => $user->id
                        ]);
                        
                        foreach ($otherActiveTests as $test) {
                            try {
                                // Use the same two-step approach for each
                                \DB::statement("UPDATE post_test_progress SET is_active = 0 WHERE id = ?", [$test->id]);
                                \DB::statement("UPDATE post_test_progress SET completed = 1, completed_at = NOW() WHERE id = ?", [$test->id]);
                                
                                \Log::info('Marked additional test as completed', ['test_id' => $test->id]);
                            } catch (\Exception $e) {
                                \Log::error('Error marking additional test as completed', [
                                    'error' => $e->getMessage(),
                                    'test_id' => $test->id
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error checking for other active tests', [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id
                    ]);
                }
                
                \Log::info('Marked post-test as completed', [
                    'test_id' => $progress->id,
                    'user_id' => $user->id,
                    'is_time_expired' => $timeExpired
                ]);
            } else {
                // Regular save for non-completed tests
                $progress->save();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Answers saved successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving post-test answers', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'language' => $language,
                'level' => $level
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error saving answers: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get saved post-test answers
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostTestAnswers(Request $request)
    {
        $language = $request->query('language', 'id');
        
        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user->id;
            $level = $user->getCurrentLevel($language);
            
            // Get answers from database
            $progress = \App\Models\PostTestProgress::where('user_id', $userId)
                ->where('language', $language)
                ->where('level', $level)
                ->where('is_active', true)
                ->where('completed', false)
                ->first();
                
            $answers = $progress ? $progress->answers : [];
            
            // For backward compatibility, also check session
            if (empty($answers)) {
                $sessionAnswersKey = 'post_test_answers_' . $language . '_level_' . $level;
                $sessionAnswers = session($sessionAnswersKey . '.' . $userId, []);
                
                if (!empty($sessionAnswers)) {
                    $answers = $sessionAnswers;
                    
                    // If we have session data but no database record, create one
                    if (!$progress) {
                        $sessionStartTimeKey = 'post_test_start_time_' . $language . '_level_' . $level;
                        $startTime = session($sessionStartTimeKey . '.' . $userId, now()->timestamp);
                        
                        \App\Models\PostTestProgress::create([
                            'user_id' => $userId,
                            'language' => $language,
                            'level' => $level,
                            'start_time' => $startTime ? \Carbon\Carbon::createFromTimestamp($startTime) : now(),
                            'answers' => $answers,
                            'completed' => false,
                            'is_active' => true
                        ]);
                    }
                }
            }
            
            // Log for debugging
            Log::info('Retrieved post-test answers', [
                'user_id' => $userId,
                'language' => $language,
                'level' => $level,
                'answer_count' => count($answers),
                'source' => $progress ? 'database' : (empty($answers) ? 'none' : 'session')
            ]);
            
            return response()->json([
                'success' => true,
                'answers' => $answers
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }
    
    /**
     * Get the post-test start time for a user
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPostTestTime(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        $language = $request->query('language', 'id');
        $user = Auth::user();
        $userId = $user->id;
        $level = $user->getCurrentLevel($language);
        
        // Get from the database first
        $progress = \App\Models\PostTestProgress::where('user_id', $userId)
            ->where('language', $language)
            ->where('level', $level)
            ->where('is_active', true)
            ->where('completed', false)
            ->first();
        
        // Get time limit for this test
        $timeLimit = \App\Models\TestSettings::getTimeLimit('post_test', $language) ?: 45;
        $timeLimitSeconds = $timeLimit * 60;
            
        if ($progress && $progress->start_time) {
            $startTime = $progress->start_time;
            $currentTime = now();
            
            // Calculate elapsed time
            $elapsedSeconds = max(0, $startTime->diffInSeconds($currentTime));
            
            // Calculate remaining time
            $remainingSeconds = max(0, $timeLimitSeconds - $elapsedSeconds);
            
            // Log for debugging
            \Log::info('Post-test time data retrieved', [
                'user_id' => $userId,
                'language' => $language,
                'level' => $level,
                'start_time' => $startTime,
                'current_time' => $currentTime,
                'elapsed_seconds' => $elapsedSeconds,
                'time_limit_seconds' => $timeLimitSeconds,
                'remaining_seconds' => $remainingSeconds
            ]);
            
            return response()->json([
                'success' => true,
                'start_time' => $startTime->timestamp,
                'current_server_time' => $currentTime->timestamp,
                'elapsed_seconds' => $elapsedSeconds,
                'time_limit_seconds' => $timeLimitSeconds,
                'remaining_seconds' => $remainingSeconds
            ]);
        }
        
        // Fallback to session data
        $sessionStartTimeKey = 'post_test_start_time_' . $language . '_level_' . $level;
        $startTime = session($sessionStartTimeKey . '.' . $userId, 0);
        
        if ($startTime > 0) {
            $currentTime = now()->timestamp;
            $elapsedSeconds = max(0, $currentTime - $startTime);
            $remainingSeconds = max(0, $timeLimitSeconds - $elapsedSeconds);
            
            return response()->json([
                'success' => true,
                'start_time' => $startTime,
                'current_server_time' => $currentTime,
                'elapsed_seconds' => $elapsedSeconds,
                'time_limit_seconds' => $timeLimitSeconds,
                'remaining_seconds' => $remainingSeconds
            ]);
        }
        
        return response()->json([
            'success' => true,
            'start_time' => 0,
            'current_server_time' => now()->timestamp,
            'elapsed_seconds' => 0,
            'time_limit_seconds' => $timeLimitSeconds,
            'remaining_seconds' => $timeLimitSeconds
        ]);
    }
}
