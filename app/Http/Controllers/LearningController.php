<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use App\Models\MaterialQuiz;
use App\Models\UserMaterialProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LearningController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['index']);
    }
    
    /**
     * Show the learning dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        $level = 1;
        $language = session('language', 'id');
        
        if ($user) {
            $level = $user->getCurrentLevel($language);
        } elseif (session('user_level')) {
            $level = session('user_level');
        }
        
        $materials = LearningMaterial::active()
            ->byLevel($level)
            ->byLanguage($language)
            ->orderBy('order')
            ->get();
            
        return view('learning.index', compact('materials', 'level', 'language'));
    }
    
    /**
     * Display learning materials.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function materials()
    {
        $user = Auth::user();
        $level = 1;
        $language = session('language', 'id');
        $canTakePostTest = false;
        
        if ($user) {
            $level = $user->getCurrentLevel($language);
            $canTakePostTest = $user->canTakePostTest($language);
        } elseif (session('user_level')) {
            $level = session('user_level');
        }
        
        $materials = LearningMaterial::active()
            ->byLevel($level)
            ->byLanguage($language)
            ->orderBy('order')
            ->get();
            
        // Get user progress for each material
        $userProgress = [];
        
        if ($user) {
            foreach ($materials as $material) {
                $progress = $material->getProgressForUser($user->id);
                
                if (!$progress) {
                    // Create empty progress record if doesn't exist
                    $progress = UserMaterialProgress::create([
                        'user_id' => $user->id,
                        'learning_material_id' => $material->id,
                    ]);
                }
                
                $userProgress[$material->id] = $progress;
            }
        }
            
        return view('learning.materials', compact('materials', 'level', 'language', 'userProgress', 'canTakePostTest'));
    }
    
    /**
     * Display a specific learning material.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function showMaterial($id)
    {
        $user = Auth::user();
        $material = LearningMaterial::findOrFail($id);
        
        // Check if user can access this material
        if (!$material->canUserAccess($user->id)) {
            return redirect()->route('learning.materials')
                ->with('error', 'Anda harus menyelesaikan materi sebelumnya terlebih dahulu.');
        }
        
        // Get user progress for this material
        $progress = $material->getProgressForUser($user->id);
        
        if (!$progress) {
            // Create progress record if doesn't exist
            $progress = UserMaterialProgress::create([
                'user_id' => $user->id,
                'learning_material_id' => $material->id,
            ]);
        }
        
        // Get quiz if available
        $quiz = $material->quiz;
        $quizQuestions = [];
        
        if ($quiz && $quiz->active) {
            $quizQuestions = $quiz->formatQuestionsForDisplay();
        }
        
        if ($quiz->time_limit) {
            session(['quiz_start_time_' . $quiz->id => now()]);
        }
        
        return view('learning.show', compact('material', 'progress', 'quiz', 'quizQuestions'));
    }
    
    /**
     * Display a specific learning material quiz.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function showQuiz($id)
    {
        $user = Auth::user();
        $material = LearningMaterial::findOrFail($id);
        
        // Check if user can access this material
        if (!$material->canUserAccess($user->id)) {
            return redirect()->route('learning.materials')
                ->with('error', 'Anda harus menyelesaikan materi sebelumnya terlebih dahulu.');
        }
        
        // Get user progress for this material
        $progress = $material->getProgressForUser($user->id);
        
        if (!$progress) {
            return redirect()->route('learning.material.show', $material->id)
                ->with('error', 'Anda harus membaca materi terlebih dahulu sebelum mengambil kuis.');
        }
        
        // Check if material is completed
        if (!$progress->completed) {
            return redirect()->route('learning.material.show', $material->id)
                ->with('error', 'Anda harus menyelesaikan materi terlebih dahulu sebelum mengambil kuis.');
        }
        
        // Check if quiz already passed
        if ($progress->quiz_passed) {
            return redirect()->route('learning.material.show', $material->id)
                ->with('info', 'Anda sudah lulus kuis untuk materi ini.');
        }
        
        // Get quiz
        $quiz = $material->quiz;
        
        if (!$quiz || !$quiz->active) {
            return redirect()->route('learning.material.show', $material->id)
                ->with('error', 'Kuis tidak tersedia untuk materi ini.');
        }
        
        // ALWAYS reset answers if explicitly requested
        if (request()->has('reset_answers')) {
            $progress->temp_answers = null;
            $progress->save();
            
            Log::info('Explicitly reset quiz answers', [
                'user_id' => $user->id, 
                'material_id' => $material->id,
                'force_clear' => request()->has('force_clear')
            ]);
            
            // Force a clean slate by redirecting without the reset_answers parameter
            // This prevents the reset_answers parameter from persisting in browser history
            if (request()->has('reset_timer') && !request()->has('force_clear')) {
                return redirect()->route('learning.material.quiz.show', [
                    'id' => $material->id,
                    'reset_timer' => '1',
                    '_' => time() // Cache buster
                ]);
            }
        }
        
        // This flag forces a brand new timer, resetting any existing timer
        $forceNewTimer = request()->has('reset_timer');
        
        // The start_new parameter just ensures we can start a quiz even if it was submitted elsewhere
        $forceNewQuiz = request()->has('start_new');
        
        // Check if the quiz was submitted in another browser/tab without explicit request for a new quiz
        if (!$forceNewQuiz && 
            (session('quiz_submitted_' . $user->id . '_' . $quiz->id) || 
            ($progress->quiz_attempts > 0 && !$progress->quiz_end_time))) {
            // If there's a global session flag or the quiz has attempts but no active timer
            // Redirect to material page to require the user to click "Kerjakan Quiz" again
            return redirect()->route('learning.materials')
                ->with('warning', 'Kuis ini telah diselesaikan di browser/tab lain. Silakan mulai kuis baru dari halaman materi.');
        }
        
        // Check if timer has expired - we need to reset it
        $timerExpired = session('quiz_timer_expired_' . $user->id . '_' . $quiz->id) || 
                        ($progress->quiz_end_time && now()->isAfter($progress->quiz_end_time));
        
        if ($timerExpired) {
            $forceNewTimer = true;
            session()->forget('quiz_timer_expired_' . $user->id . '_' . $quiz->id);
        }
        
        // Format quiz questions for display
        $quizQuestions = $quiz->formatQuestionsForDisplay();
        
        // Clear any previous quiz submission session
        session()->forget('quiz_submitted_' . $user->id . '_' . $quiz->id);
        session()->save();
        
        // Get temporary answers if they exist
        $tempAnswers = $progress->temp_answers ?? [];
        
        // Reset answers if starting a new timer
        if ($forceNewTimer) {
            $tempAnswers = [];
            $progress->temp_answers = null;
            $progress->save();
            
            Log::info('Reset quiz answers due to timer reset', [
                'user_id' => $user->id, 
                'material_id' => $material->id
            ]);
        }
        
        // Handle quiz timer
        // Only create a new timer if:
        // 1. There is no existing timer OR
        // 2. Explicitly requested to reset timer OR
        // 3. Timer has expired
        if (!$progress->quiz_end_time || $forceNewTimer || $timerExpired) {
            $timeLimit = $quiz->time_limit * 60; // Convert to seconds
            $endTime = now()->addSeconds($timeLimit);
            
            // Store in database
            $progress->quiz_start_time = now();
            $progress->quiz_end_time = $endTime;
            $progress->save();
            
            Log::info('Created new quiz timer', [
                'user_id' => $user->id, 
                'material_id' => $material->id,
                'end_time' => $endTime,
                'reason' => $forceNewTimer ? 'forced' : ($timerExpired ? 'expired' : 'new')
            ]);
        } else {
            Log::info('Continuing with existing quiz timer', [
                'user_id' => $user->id, 
                'material_id' => $material->id,
                'end_time' => $progress->quiz_end_time
            ]);
        }
        
        // Get exact time objects for precise calculation
        $now = now();
        $endTime = $progress->quiz_end_time;
        
        // Calculate remaining milliseconds for precision
        $remainingMillis = $now->diffInMilliseconds($endTime, false);
        $remainingSeconds = floor($remainingMillis / 1000);
        
        // If time is up or negative, set to 0
        if ($remainingSeconds <= 0) {
            $remainingSeconds = 0;
            $remainingMillis = 0;
        }
        
        // Get server time and end time with millisecond precision for JavaScript
        $serverTime = $now->timestamp + ($now->micro / 1000000);
        $endTimeValue = $endTime->timestamp + ($endTime->micro / 1000000);
        
        // Add a flag to the view to indicate if answers should be reset
        $resetAnswers = request()->has('reset_answers') || $forceNewTimer;
        
        return view('learning.quiz', compact(
            'material', 
            'quiz', 
            'quizQuestions', 
            'progress', 
            'remainingSeconds',
            'remainingMillis', 
            'serverTime', 
            'endTimeValue',
            'tempAnswers',
            'resetAnswers'
        ));
    }
    
    /**
     * Mark a material as completed.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markCompleted(Request $request, $id)
    {
        $user = Auth::user();
        $material = LearningMaterial::findOrFail($id);
        
        // Get or create progress
        $progress = $material->getProgressForUser($user->id);
        
        if (!$progress) {
            $progress = UserMaterialProgress::create([
                'user_id' => $user->id,
                'learning_material_id' => $material->id,
            ]);
        }
        
        // Mark as completed
        $progress->markCompleted();
        
        // Check if can proceed to next material
        $canProceedToNext = $progress->canProceedToNext();
        
        // Get next material if exists
        $nextMaterial = $material->getNextMaterial();
        $nextMaterialId = $nextMaterial ? $nextMaterial->id : null;
        
        return response()->json([
            'success' => true,
            'message' => 'Materi berhasil ditandai sebagai selesai.',
            'can_proceed' => $canProceedToNext,
            'next_material_id' => $nextMaterialId,
            'can_take_post_test' => $user->canTakePostTest(session('language', 'id')),
        ]);
    }
    
    /**
     * Submit quiz answers.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function submitQuiz(Request $request, $id)
    {
        $user = Auth::user();
        $material = LearningMaterial::findOrFail($id);
        $quiz = $material->quiz;
        
        Log::info('Quiz submission received', [
            'user_id' => $user->id,
            'material_id' => $id,
            'quiz_id' => $quiz ? $quiz->id : null,
            'answers' => $request->input('answers'),
            'is_ajax' => $request->ajax() || $request->query('ajax') == 1
        ]);
        
        if (!$quiz) {
            Log::error('Quiz not found for material', ['material_id' => $id]);
            
            if ($request->ajax() || $request->query('ajax') == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kuis tidak ditemukan.',
                ], 404);
            }
            
            return redirect()->route('learning.material.show', $material->id)
                ->with('error', 'Kuis tidak ditemukan.');
        }
        
        // Get or create progress for clearing timer data
        $progress = $material->getProgressForUser($user->id);
        if ($progress) {
            // Clear quiz timer data from database
            $progress->quiz_start_time = null;
            $progress->quiz_end_time = null;
            
            // Clear temporary answers since the quiz is submitted
            $progress->temp_answers = null;
            $progress->save();
        }
        
        // Mark this quiz as submitted in the session to prevent duplicate attempts in other tabs
        session(['quiz_submitted_' . $user->id . '_' . $quiz->id => true]);
        
        // Validate the request
        try {
            $validated = $request->validate([
                'answers' => 'required|array',
            ]);
            
            $answers = $request->input('answers');
            
            // Calculate score
            $score = $quiz->calculateScore($answers);
            $passed = $quiz->isPassing($score);
            
            // Get detailed results for each question
            $questionResults = [];
            $correctCount = 0;
            $incorrectCount = 0;
            
            foreach ($quiz->questions as $index => $question) {
                $userAnswer = isset($answers[$index]) ? $answers[$index] : null;
                $isCorrect = $userAnswer !== null && $quiz->checkAnswer($index, $userAnswer);
                
                if ($isCorrect) {
                    $correctCount++;
                } else {
                    $incorrectCount++;
                }
                
                $questionResults[$index] = [
                    'question_text' => $question['text'],
                    'user_answer' => $userAnswer,
                    'correct_answer' => $question['correct_answer'],
                    'is_correct' => $isCorrect,
                    'type' => $question['type'] ?? 'multiple_choice',
                ];
                
                // Jika multiple choice, tambahkan teks opsi
                if (($question['type'] ?? 'multiple_choice') === 'multiple_choice' && isset($question['options'])) {
                    $questionResults[$index]['correct_answer_text'] = $question['options'][$question['correct_answer']] ?? '';
                    if ($userAnswer !== null) {
                        $questionResults[$index]['user_answer_text'] = $question['options'][$userAnswer] ?? '';
                    }
                }
            }
            
            // Get or create progress
            if (!$progress) {
                $progress = UserMaterialProgress::create([
                    'user_id' => $user->id,
                    'learning_material_id' => $material->id,
                ]);
            }
            
            // Record quiz attempt
            $progress->recordQuizAttempt($score, $answers);
            
            // Check if can proceed to next material
            $canProceedToNext = $progress->canProceedToNext();
            
            // Get next material if exists
            $nextMaterial = $material->getNextMaterial();
            $nextMaterialId = $nextMaterial ? $nextMaterial->id : null;
            
            $response = [
                'success' => true,
                'message' => $passed ? 'Selamat! Anda telah lulus kuis.' : 'Anda belum lulus kuis. Silakan coba lagi.',
                'score' => (int)$score,
                'passed' => $passed,
                'passing_score' => $quiz->passing_score,
                'can_proceed' => $canProceedToNext,
                'next_material_id' => $nextMaterialId,
                'can_take_post_test' => $user->canTakePostTest(session('language', 'id')),
                'question_results' => $questionResults,
                'correct_count' => $correctCount,
                'incorrect_count' => $incorrectCount,
                'total_questions' => count($quiz->questions),
            ];
            
            Log::info('Quiz submission processed successfully', $response);
            
            if ($request->ajax() || $request->query('ajax') == 1) {
                return response()->json($response);
            }
            
            // For regular form submissions, redirect with flash data
            $message = $passed ? 'Selamat! Anda telah lulus kuis.' : 'Anda belum lulus kuis. Silakan coba lagi.';
            $messageType = $passed ? 'success' : 'warning';
            
            // Debug log
            Log::info('Regular form submission, redirecting', [
                'message' => $message,
                'messageType' => $messageType,
                'redirectTo' => $passed && $nextMaterial ? 'next_material' : 'current_material',
                'fallback' => $request->input('fallback'),
                'next_material_id' => $nextMaterialId
            ]);
            
            if ($passed && $nextMaterial) {
                return redirect()->route('learning.material.show', $nextMaterial->id)
                    ->with($messageType, $message)
                    ->with('quiz_result', $response);
            }
            
            // Jika form biasa, tambahkan semua data ke session
            return redirect()->route('learning.material.quiz.show', $material->id)
                ->with($messageType, $message)
                ->with('quiz_result', $response)
                ->with('reset_answers', true)
                ->with('auto_reset', true);
                
        } catch (\Exception $e) {
            Log::error('Error processing quiz submission', [
                'material_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax() || $request->query('ajax') == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memproses jawaban kuis: ' . $e->getMessage(),
                ], 500);
            }
            
            return redirect()->route('learning.material.quiz.show', $material->id)
                ->with('error', 'Terjadi kesalahan saat memproses jawaban kuis.');
        }
    }
    
    /**
     * Check if a user can take post-test.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPostTestEligibility()
    {
        $user = Auth::user();
        $language = session('language', 'id');
        
        $canTakePostTest = $user->canTakePostTest($language);
        
        return response()->json([
            'can_take_post_test' => $canTakePostTest,
        ]);
    }
    
    /**
     * Evaluate exercise answer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function evaluateAnswer(Request $request)
    {
        $request->validate([
            'answer' => 'required|string',
            'question_id' => 'required|integer',
        ]);

        $answer = $request->input('answer');
        $questionId = $request->input('question_id');
        
        // In a real implementation, you'd retrieve the question from database 
        // and compare the answer with the correct one
        
        // Placeholder implementation
        $isCorrect = (bool)mt_rand(0, 1);
        $feedback = $isCorrect 
            ? "Correct! Well done." 
            : "Not quite right. Try again!";
            
        return response()->json([
            'is_correct' => $isCorrect,
            'feedback' => $feedback
        ]);
    }
    
    /**
     * API endpoints
     */
    
    /**
     * Get learning questions based on level and language.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiQuestions(Request $request)
    {
        $level = $request->query('level', 1);
        $language = $request->query('language', 'en');
        
        // This would normally come from a database
        $questions = [
            [
                'id' => 1,
                'question' => 'Sample question 1 for level ' . $level,
                'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                'correct_answer' => 0,
                'type' => 'multiple_choice'
            ],
            [
                'id' => 2,
                'question' => 'Sample question 2 for level ' . $level,
                'type' => 'text_input',
                'correct_answer' => 'sample answer'
            ],
            [
                'id' => 3,
                'question' => 'Sample question 3 for level ' . $level,
                'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                'correct_answer' => 2,
                'type' => 'multiple_choice'
            ],
        ];
        
        return response()->json([
            'questions' => $questions,
            'level' => $level,
            'language' => $language
        ]);
    }
    
    /**
     * Get learning materials based on level and language.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiMaterials(Request $request)
    {
        $level = $request->query('level', 1);
        $language = $request->query('language', 'en');
        
        $materials = LearningMaterial::active()
            ->byLevel($level)
            ->byLanguage($language)
            ->orderBy('order')
            ->get();
            
        return response()->json([
            'materials' => $materials,
            'level' => $level,
            'language' => $language
        ]);
    }
    
    /**
     * API endpoint to sync quiz timer
     * 
     * @param Request $request
     * @param int $materialId
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncQuizTimer(Request $request, $materialId)
    {
        $user = Auth::user();
        $material = LearningMaterial::findOrFail($materialId);
        $quiz = $material->quiz;
        
        if (!$quiz) {
            return response()->json(['error' => 'Quiz not found'], 404);
        }
        
        $progress = $material->getProgressForUser($user->id);
        
        if (!$progress || !$progress->quiz_end_time) {
            return response()->json(['error' => 'No active quiz session'], 404);
        }
        
        // Exact remaining time calculation
        $now = now();
        $endTime = $progress->quiz_end_time;
        $remainingMillis = $now->diffInMilliseconds($endTime, false);
        
        // If time is up or negative, set to 0
        if ($remainingMillis <= 0) {
            $remainingMillis = 0;
            $remainingSeconds = 0;
            $minutes = 0;
            $seconds = 0;
        } else {
            $remainingSeconds = floor($remainingMillis / 1000);
            $minutes = floor($remainingSeconds / 60);
            $seconds = $remainingSeconds % 60;
        }
        
        // Format the time string on server-side
        $timeDisplay = sprintf("%02d:%02d", $minutes, $seconds);
        
        return response()->json([
            'remaining_millis' => $remainingMillis,
            'remaining_seconds' => $remainingSeconds,
            'display_time' => $timeDisplay,
            'is_time_up' => $remainingMillis <= 0,
            'server_time' => $now->timestamp * 1000 + $now->micro / 1000,
            'end_time' => $endTime->timestamp * 1000 + $endTime->micro / 1000,
            'now_iso' => $now->toIso8601String(),
            'end_iso' => $endTime->toIso8601String()
        ]);
    }
    
    /**
     * Check if quiz is already completed or time is up
     * 
     * @param Request $request
     * @param int $materialId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkQuizStatus(Request $request, $materialId)
    {
        $user = Auth::user();
        $material = LearningMaterial::findOrFail($materialId);
        $quiz = $material->quiz;
        $progress = $material->getProgressForUser($user->id);
        
        $response = [
            'redirect' => false,
            'message' => '',
            'redirect_url' => ''
        ];
        
        // If no quiz found
        if (!$quiz) {
            $response['redirect'] = true;
            $response['message'] = 'Kuis tidak ditemukan.';
            $response['redirect_url'] = route('learning.material.show', $material->id);
            return response()->json($response);
        }
        
        // Check if quiz was submitted in another tab/browser
        if (session('quiz_submitted_' . $user->id . '_' . $quiz->id)) {
            $response['redirect'] = true;
            $response['message'] = 'Kuis ini telah diselesaikan di tab/browser lain. Anda akan diarahkan ke halaman materi.';
            $response['redirect_url'] = route('learning.materials');
            return response()->json($response);
        }
        
        // If no progress or quiz timer data is cleared (indicating a submission in another session)
        if (!$progress || !$progress->quiz_end_time) {
            // If already has attempts, it means the quiz was submitted in another browser
            if ($progress && $progress->quiz_attempts > 0) {
                $response['redirect'] = true;
                $response['message'] = 'Kuis ini telah diselesaikan di browser lain. Anda akan diarahkan ke halaman materi.';
                $response['redirect_url'] = route('learning.materials');
                return response()->json($response);
            }
            
            // If the quiz was never started or was completed and needs a restart
            if (!$request->has('in_progress')) { // Only redirect if not already on the quiz page
                $response['redirect'] = true;
                $response['message'] = 'Anda perlu memulai kuis terlebih dahulu.';
                $response['redirect_url'] = route('learning.material.show', $material->id);
            }
            
            return response()->json($response);
        }
        
        // Check if time is up
        $remainingSeconds = now()->diffInSeconds($progress->quiz_end_time, false);
        
        if ($remainingSeconds <= 0) {
            // Mark the timer as expired
            session(['quiz_timer_expired_' . $user->id . '_' . $quiz->id => true]);
            
            $response['redirect'] = true;
            $response['message'] = 'Waktu pengerjaan kuis telah habis.';
            $response['redirect_url'] = route('learning.materials');
        }
        
        return response()->json($response);
    }
    
    /**
     * Save temporary quiz answers
     * 
     * @param Request $request
     * @param int $materialId
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveQuizAnswers(Request $request, $materialId)
    {
        $user = Auth::user();
        $material = LearningMaterial::findOrFail($materialId);
        $progress = $material->getProgressForUser($user->id);
        
        if (!$progress) {
            // Buat progress baru jika tidak ditemukan
            $progress = UserMaterialProgress::create([
                'user_id' => $user->id,
                'learning_material_id' => $material->id,
            ]);
            
            if (!$progress) {
                return response()->json(['error' => 'Failed to create progress record'], 500);
            }
        }
        
        // Get the answers from the request
        $answers = $request->input('answers', []);
        
        // Check if this is a reset request
        $isReset = $request->input('reset', false);
        
        if ($isReset) {
            // Reset temporary answers
            $progress->temp_answers = null;
            $progress->save();
            
            // Log reset operation
            \Log::info('Quiz answers reset', [
                'user_id' => $user->id,
                'material_id' => $material->id,
                'timestamp' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Answers reset successfully'
            ]);
        }
        
        // Store the temporary answers in the database
        $progress->temp_answers = $answers;
        $saved = $progress->save();
        
        // Log save operation
        \Log::info('Quiz answers saved', [
            'user_id' => $user->id,
            'material_id' => $material->id,
            'answers' => $answers,
            'saved_successfully' => $saved,
            'timestamp' => now()
        ]);
        
        return response()->json([
            'success' => $saved,
            'message' => $saved ? 'Answers saved successfully' : 'Failed to save answers',
            'answers' => $answers
        ]);
    }
    
    /**
     * Get temporary quiz answers
     * 
     * @param Request $request
     * @param int $materialId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQuizAnswers(Request $request, $materialId)
    {
        $user = Auth::user();
        $material = LearningMaterial::findOrFail($materialId);
        $progress = $material->getProgressForUser($user->id);
        
        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'No progress record found',
                'answers' => []
            ]);
        }
        
        // Get the temporary answers
        $answers = $progress->temp_answers ?? [];
        
        // Log the retrieval operation
        \Log::info('Quiz answers retrieved', [
            'user_id' => $user->id,
            'material_id' => $material->id,
            'answers' => $answers,
            'timestamp' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Answers retrieved successfully',
            'answers' => $answers
        ]);
    }

    /**
     * Show quiz history for the current user.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function quizHistory()
    {
        $user = Auth::user();
        $language = session('language', 'id');
        
        // Get all user progress records with quiz attempts
        $quizAttempts = UserMaterialProgress::where('user_id', $user->id)
            ->whereNotNull('quiz_answers')
            ->where('quiz_attempts', '>', 0)
            ->with(['learningMaterial' => function($query) use ($language) {
                $query->where('language', $language);
            }])
            ->whereHas('learningMaterial', function($query) use ($language) {
                $query->where('language', $language);
            })
            ->orderBy('updated_at', 'desc')
            ->get();
        
        return view('learning.history.index', compact('quizAttempts', 'language'));
    }

    /**
     * Show details of a specific quiz attempt.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function quizHistoryDetail($id)
    {
        $user = Auth::user();
        
        // Get the quiz attempt record
        $progress = UserMaterialProgress::where('id', $id)
            ->where('user_id', $user->id)
            ->whereNotNull('quiz_answers')
            ->with('learningMaterial')
            ->firstOrFail();
        
        // Get the quiz
        $material = $progress->learningMaterial;
        $quiz = $material->quiz;
        
        if (!$quiz) {
            return redirect()->route('quiz.history.index')
                ->with('error', 'Quiz not found.');
        }
        
        // Format quiz questions with correct answers
        $quizQuestions = $quiz->questions;
        
        // Get user's answers
        $userAnswers = $progress->quiz_answers ?? [];
        
        // Generate question results
        $questionResults = [];
        foreach ($quizQuestions as $index => $question) {
            $userAnswer = $userAnswers[$index] ?? null;
            $isCorrect = $quiz->checkAnswer($index, $userAnswer);
            
            $userAnswerText = '';
            $correctAnswerText = '';
            
            if ($question['type'] === 'multiple_choice' && isset($question['options'])) {
                // For multiple choice, get the text of the selected option
                if ($userAnswer !== null && isset($question['options'][$userAnswer])) {
                    $userAnswerText = $question['options'][$userAnswer];
                }
                
                // Get the text of the correct option
                if (isset($question['correct_answer']) && isset($question['options'][$question['correct_answer']])) {
                    $correctAnswerText = $question['options'][$question['correct_answer']];
                }
            }
            
            $questionResults[$index] = [
                'question_text' => $question['text'],
                'user_answer' => $userAnswer,
                'user_answer_text' => $userAnswerText,
                'correct_answer' => $question['correct_answer'] ?? null,
                'correct_answer_text' => $correctAnswerText,
                'is_correct' => $isCorrect,
                'type' => $question['type'] ?? 'multiple_choice'
            ];
        }
        
        return view('learning.history.detail', compact('progress', 'material', 'quiz', 'questionResults'));
    }
}
