<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GrammarController;
use App\Http\Controllers\SpeechController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\VirtualTutorController;
use App\Http\Controllers\YouTubeTranscriptionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Grammar API routes
Route::post('/grammar', [GrammarController::class, 'apiCorrect']);

// Speech API routes
Route::post('/speech', [SpeechController::class, 'apiRecognize']);
Route::post('/direct-speech', [SpeechController::class, 'apiDirectRecognize']);

// Assessment API routes
Route::get('/pretest', [AssessmentController::class, 'apiPretest']);
Route::post('/evaluate-pretest', [AssessmentController::class, 'apiEvaluatePretest']);

Route::get('/post-test', [AssessmentController::class, 'apiPostTest']);
Route::post('/evaluate-post-test', [AssessmentController::class, 'apiEvaluatePostTest']);

Route::get('/placement-test', [AssessmentController::class, 'apiPlacementTest']);
Route::post('/evaluate-placement', [AssessmentController::class, 'apiEvaluatePlacement']);

Route::get('/listening-test', [AssessmentController::class, 'apiListeningTest']);
Route::get('/reading-test', [AssessmentController::class, 'apiReadingTest']);
Route::get('/speaking-test', [AssessmentController::class, 'apiSpeakingTest']);

Route::get('/progress-report', [AssessmentController::class, 'apiProgressReport']);

// Learning API routes
Route::get('/questions', [LearningController::class, 'apiQuestions']);
Route::get('/learning-materials', [LearningController::class, 'apiMaterials']);

// Virtual Tutor API routes
Route::post('/virtual-tutor/chat', [VirtualTutorController::class, 'chat']);
Route::post('/virtual-tutor/speech', [VirtualTutorController::class, 'speaking']);
Route::get('/virtual-tutor/languages', [VirtualTutorController::class, 'getLanguages']);
Route::get('/virtual-tutor/topics', [VirtualTutorController::class, 'getTopics']);

// YouTube Transcription API routes
Route::post('/youtube-transcription', [YouTubeTranscriptionController::class, 'apiTranscribe']); 