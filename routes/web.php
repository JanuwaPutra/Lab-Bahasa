<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GrammarController;
use App\Http\Controllers\SpeechController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\VirtualTutorController;
use App\Http\Controllers\YouTubeTranscriptionController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TeacherMaterialController;
use App\Http\Controllers\MaterialQuizController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ToolsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/dashboardd', [HomeController::class, 'dashboard'])->name('dashboard');




// Language-specific post-test routes - all require authentication
Route::middleware(['auth'])->group(function () {
    Route::get('/post-test', [AssessmentController::class, 'postTest'])->name('post-test');
    Route::get('/post-test/{language}', [AssessmentController::class, 'postTest'])->name('post-test.language');
    Route::post('/post-test', [AssessmentController::class, 'evaluatePostTest'])->name('post-test.evaluate');
    Route::post('/post-test/save-answers', [AssessmentController::class, 'savePostTestAnswers'])->name('post-test.save-answers');
    Route::get('/post-test/get-answers', [AssessmentController::class, 'getPostTestAnswers'])->name('post-test.get-answers');
    Route::get('/post-test/get-time', [AssessmentController::class, 'getPostTestTime'])->name('post-test.get-time');
    // Evaluation route
Route::get('/evaluation', [EvaluationController::class, 'index'])->name('evaluation');

// Grammar routes
Route::get('/grammar', [GrammarController::class, 'index'])->name('grammar');
Route::post('/grammar', [GrammarController::class, 'correct'])->name('grammar.correct');

// Speech routes
Route::get('/speech', [SpeechController::class, 'index'])->name('speech');
Route::post('/speech', [SpeechController::class, 'recognize'])->name('speech.recognize');
Route::get('/speech/test-system', [SpeechController::class, 'testSystem'])->name('speech.test-system');

// Assessment routes
Route::get('/placement-test', [AssessmentController::class, 'placementTest'])->name('placement-test');
Route::post('/placement-test', [AssessmentController::class, 'evaluatePlacementTest'])->name('placement-test.evaluate');

// Grammar test route
Route::get('/grammar-test', [AssessmentController::class, 'grammarTest'])->name('grammar-test');
Route::post('/grammar-test', [AssessmentController::class, 'evaluateGrammarTest'])->name('grammar-test.evaluate');

// Language-specific pretest routes
Route::get('/pretest', [AssessmentController::class, 'pretest'])->name('pretest');
Route::get('/pretest/{language}', [AssessmentController::class, 'pretest'])->name('pretest.language');
Route::post('/pretest', [AssessmentController::class, 'evaluatePretest'])->name('pretest.evaluate');
Route::post('/pretest/save-answers', [AssessmentController::class, 'savePretestAnswers'])->name('pretest.save-answers')->middleware(['web', 'auth']);
Route::get('/pretest/get-answers', [AssessmentController::class, 'getPretestAnswers'])->name('pretest.get-answers')->middleware(['web', 'auth']);
Route::get('/pretest/get-time', [AssessmentController::class, 'getPretestTime'])->name('pretest.get-time')->middleware(['web', 'auth']);
});

Route::get('/listening-test', [AssessmentController::class, 'listeningTest'])->name('listening-test');
Route::post('/listening-test', [AssessmentController::class, 'evaluateListeningTest'])->name('listening-test.evaluate');

Route::get('/reading-test', [AssessmentController::class, 'readingTest'])->name('reading-test');
Route::post('/reading-test', [AssessmentController::class, 'evaluateReadingTest'])->name('reading-test.evaluate');

Route::get('/speaking-test', [AssessmentController::class, 'speakingTest'])->name('speaking-test');
Route::post('/speaking-test', [AssessmentController::class, 'evaluateSpeakingTest'])->name('speaking-test.evaluate');

Route::get('/progress-report', [AssessmentController::class, 'progressReport'])->name('progress-report');

// Learning routes
Route::get('/learning', [LearningController::class, 'index'])->name('learning');
Route::get('/learning-materials', [LearningController::class, 'materials'])->name('learning.materials');
Route::get('/learning-material/{id}', [LearningController::class, 'showMaterial'])->name('learning.material.show');
Route::post('/learning-material/{id}/complete', [LearningController::class, 'markCompleted'])->name('learning.material.complete');
Route::get('/learning-material/{id}/quiz', [LearningController::class, 'showQuiz'])->name('learning.material.quiz.show');
Route::post('/learning-material/{id}/quiz', [LearningController::class, 'submitQuiz'])->name('learning.material.quiz');
Route::get('/learning-check-post-test', [LearningController::class, 'checkPostTestEligibility'])->name('learning.check-post-test');
Route::post('/learning-evaluate', [LearningController::class, 'evaluateAnswer'])->name('learning.evaluate');

// Learning material routes
Route::get('/learning-material/{material}/sync-timer', [LearningController::class, 'syncQuizTimer'])->name('learning.material.sync-timer');
Route::get('/learning-material/{material}/check-status', [LearningController::class, 'checkQuizStatus'])->name('learning.material.check-status');
Route::post('/learning-material/{material}/save-answers', [LearningController::class, 'saveQuizAnswers'])->name('learning.material.save-answers');
Route::get('/learning-material/{material}/get-answers', [LearningController::class, 'getQuizAnswers'])->name('learning.material.get-answers');

// Virtual Tutor routes
Route::middleware(['auth'])->group(function () {
    Route::get('/virtual-tutor', [VirtualTutorController::class, 'index'])->name('virtual-tutor');
    Route::post('/virtual-tutor/chat', [VirtualTutorController::class, 'chat'])->name('virtual-tutor.chat');
    Route::post('/virtual-tutor/speaking', [VirtualTutorController::class, 'speaking'])->name('virtual-tutor.speaking');
    Route::get('/virtual-tutor/languages', [VirtualTutorController::class, 'getLanguages'])->name('virtual-tutor.languages');
    Route::get('/virtual-tutor/topics', [VirtualTutorController::class, 'getTopics'])->name('virtual-tutor.topics');
    Route::get('/virtual-tutor/history', [VirtualTutorController::class, 'getConversationHistory'])->name('virtual-tutor.history');
    Route::post('/virtual-tutor/reset', [VirtualTutorController::class, 'resetConversation'])->name('virtual-tutor.reset');
});

// YouTube Transcription routes
Route::middleware(['auth'])->group(function () {
    Route::get('/youtube-transcription', [YouTubeTranscriptionController::class, 'index'])->name('youtube-transcription');
    Route::post('/youtube-transcription', [YouTubeTranscriptionController::class, 'transcribe'])->name('youtube-transcription.transcribe');
});

Route::get('/profile', [HomeController::class, 'profile'])->name('profile');
Route::get('/reset-data', [HomeController::class, 'resetData'])->name('reset-data');
Route::post('/set-language', [HomeController::class, 'setLanguage'])->name('set-language');

// Question Management routes (for teachers)
Route::middleware(['auth'])->group(function () {
    Route::post('/questions/bulk-delete', [QuestionController::class, 'bulkDelete'])->name('questions.bulk-delete');
    Route::post('/questions/update-settings', [QuestionController::class, 'updateSettings'])->name('questions.update.settings');
    Route::resource('questions', QuestionController::class);
});

// Teacher routes
Route::prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/students', [TeacherController::class, 'students'])->name('students');
    Route::get('/students/{id}', [TeacherController::class, 'studentDetail'])->name('student.detail');
    Route::get('/test-results', [TeacherController::class, 'testResults'])->name('test.results');
    Route::get('/test-results/{id}', [TeacherController::class, 'testResultDetail'])->name('test.result.detail');
    
    // Learning Materials Management
    Route::get('/materials', [TeacherController::class, 'materials'])->name('materials');
    Route::get('/materials/create', [TeacherController::class, 'createMaterial'])->name('materials.create');
    Route::post('/materials', [TeacherController::class, 'storeMaterial'])->name('materials.store');
    Route::get('/materials/{id}/edit', [TeacherController::class, 'editMaterial'])->name('materials.edit');
    Route::put('/materials/{id}', [TeacherController::class, 'updateMaterial'])->name('materials.update');
    Route::delete('/materials/{id}', [TeacherController::class, 'destroyMaterial'])->name('materials.destroy');
});

// Teacher Material Management Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/teacher/materials', [TeacherMaterialController::class, 'index'])->name('teacher.materials');
    Route::get('/teacher/materials/create', [TeacherMaterialController::class, 'create'])->name('teacher.materials.create');
    Route::post('/teacher/materials', [TeacherMaterialController::class, 'store'])->name('teacher.materials.store');
    Route::get('/teacher/materials/{id}/edit', [TeacherMaterialController::class, 'edit'])->name('teacher.materials.edit');
    Route::put('/teacher/materials/{id}', [TeacherMaterialController::class, 'update'])->name('teacher.materials.update');
    Route::delete('/teacher/materials/{id}', [TeacherMaterialController::class, 'destroy'])->name('teacher.materials.destroy');
    
    // Material Quiz Management Routes
    Route::get('/teacher/materials/{id}/quiz/create', [MaterialQuizController::class, 'create'])->name('teacher.materials.quiz.create');
    Route::post('/teacher/materials/{id}/quiz', [MaterialQuizController::class, 'store'])->name('teacher.materials.quiz.store');
    Route::get('/teacher/materials/{id}/quiz/edit', [MaterialQuizController::class, 'edit'])->name('teacher.materials.quiz.edit');
    Route::put('/teacher/materials/{id}/quiz', [MaterialQuizController::class, 'update'])->name('teacher.materials.quiz.update');
    Route::delete('/teacher/materials/{id}/quiz', [MaterialQuizController::class, 'destroy'])->name('teacher.materials.quiz.destroy');
});

// Admin routes
Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Only admin users can access these routes (checked in the controller)
    // Admin role management
    Route::get('/role-management', [AdminController::class, 'roleManagement'])->name('admin.role.management');
    Route::post('/update-role/{id}', [AdminController::class, 'updateRole'])->name('admin.role.update');
    
    // Teacher and language settings
    Route::get('/teacher-language-settings', [AdminController::class, 'teacherLanguageSettings'])->name('admin.teacher-language.settings');
    Route::post('/teacher-language-settings', [AdminController::class, 'updateTeacherLanguageSettings'])->name('admin.teacher-language.update');
    
    // Post-test monitoring page
    Route::get('/post-test-monitoring', [AdminController::class, 'postTestMonitoring'])->name('admin.post-test.monitoring');
});

// Post-test monitoring data endpoint - separate to avoid auth issues with AJAX
Route::middleware(['auth'])->get('/admin/post-test-monitoring/data', [AdminController::class, 'getPostTestData'])->name('admin.post-test.data');

// Auth routes (Laravel's built-in authentication)
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// CSRF token refresh route
Route::get('/refresh-csrf', function() {
    return response()->json(['token' => csrf_token()]);
})->name('refresh-csrf');

// Add a new route that matches the one used in post_test.blade.php
Route::get('/refresh-csrf-token', function() {
    return response()->json(['token' => csrf_token()]);
})->name('refresh-csrf-token');

// Debug route
Route::get('/debug-user', function () {
    if (!Auth::check()) {
        return "Please login first";
    }
    
    $user = Auth::user();
    $assessments = \App\Models\Assessment::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();
        
    $data = [
        'user_id' => $user->id,
        'user_name' => $user->name,
        'assessment_count' => $assessments->count(),
        'pretest_exists' => $user->hasCompletedPretest('id'),
        'current_level' => $user->getCurrentLevel('id'),
        'pretest_date' => $user->pretestDate('id'),
        'assessments' => $assessments->map(function($assessment) {
            return [
                'id' => $assessment->id,
                'type' => $assessment->type,
                'level' => $assessment->level,
                'language' => $assessment->language,
                'created_at' => $assessment->created_at->format('Y-m-d H:i:s')
            ];
        })
    ];
    
    return response()->json($data);
});

// Teacher Evaluation Settings Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/teacher/evaluation-settings', [EvaluationController::class, 'showSettingsPage'])->name('teacher.evaluation.settings');
    Route::get('/teacher/evaluation-settings/{student}', [EvaluationController::class, 'showStudentSettings'])->name('teacher.evaluation.student');
    Route::post('/teacher/evaluation-settings/{student}', [EvaluationController::class, 'updateStudentSettings'])->name('teacher.evaluation.update');
});

// Quiz history routes
Route::middleware('auth')->prefix('quiz-history')->name('quiz.history.')->group(function () {
    Route::get('/', [App\Http\Controllers\LearningController::class, 'quizHistory'])->name('index');
    Route::get('/{id}', [App\Http\Controllers\LearningController::class, 'quizHistoryDetail'])->name('detail');
});

// Question import/export routes
Route::get('/questions/export', [QuestionController::class, 'export'])->name('questions.export');
Route::get('/questions/import/template', [QuestionController::class, 'downloadTemplate'])->name('questions.template.download');
Route::get('/questions/import/form', [QuestionController::class, 'importForm'])->name('questions.import.form');
Route::post('/questions/import', [QuestionController::class, 'import'])->name('questions.import');
