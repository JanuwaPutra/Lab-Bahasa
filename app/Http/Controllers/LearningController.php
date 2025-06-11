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
        
        // Format quiz questions for display
        $quizQuestions = $quiz->formatQuestionsForDisplay();
        
        return view('learning.quiz', compact('material', 'quiz', 'quizQuestions', 'progress'));
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
            $progress = $material->getProgressForUser($user->id);
            
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
                'score' => $score,
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
                ->with('quiz_result', $response);
                
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
}
