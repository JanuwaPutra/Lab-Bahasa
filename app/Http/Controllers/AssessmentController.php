<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        $request->validate([
            'answers' => 'required|array',
            'language' => 'required|string|in:id,en,ru',
        ]);

        $answers = $request->input('answers');
        $language = $request->input('language');
        $cheatingDetected = $request->input('cheating_detected', false);
        
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
        
        // If cheating detected, skip evaluation and set score to 0
        if ($cheatingDetected) {
            $percentage = 0;
            $level = 1;
            $passed = false;
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
    }

    /**
     * Show the post-test form
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
        
        // Get the user's current level for this language
        $user = auth()->user();
        $level = $user ? $user->getCurrentLevel($language) : 1;
        
        // Debug logging
        \Log::info('Loading post-test', [
            'user_id' => $user ? $user->id : 'guest',
            'level' => $level,
            'language' => $language
        ]);
        
        // Check if user has completed all learning materials before allowing post-test
        if ($user && !$user->canTakePostTest($language)) {
            \Log::warning('User attempted to access post-test without completing all materials', [
                'user_id' => $user->id,
                'level' => $level,
                'language' => $language
            ]);
            
            return redirect()->route('learning.materials')
                ->with('error', 'Anda harus menyelesaikan semua materi dan kuis pada level ini sebelum mengambil Post Test.');
        }
        
        // Get language-specific questions for the user's level
        $questions = \App\Models\Question::active()
            ->byAssessmentType('post_test')
            ->byLevel($level)
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
            
        return view('assessment.post_test', compact('questions', 'level', 'language', 'languageName', 'timeLimit'));
    }

    /**
     * Evaluate post-test
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function evaluatePostTest(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'language' => 'required|string|in:id,en,ru',
        ]);

        $answers = $request->input('answers');
        $language = $request->input('language');
        $cheatingDetected = $request->input('cheating_detected', false);
        $level = auth()->user()->getCurrentLevel($language) ?? 1;
        
        // Debug the incoming data
        \Log::info('Post-test evaluation request', [
            'answers' => $answers,
            'language' => $language,
            'level' => $level,
            'request_data' => $request->all()
        ]);
        
        // Get questions from database
        $questions = \App\Models\Question::active()
            ->byAssessmentType('post_test')
            ->byLevel($level)
            ->byLanguage($language)
            ->get();
            
        // Debug questions retrieved
        \Log::info('Post-test questions retrieved', [
            'question_count' => $questions->count(),
            'questions' => $questions->map(function($q) {
                return [
                    'id' => $q->id,
                    'text' => $q->text,
                    'type' => $q->type,
                    'correct_answer' => $q->correct_answer,
                    'options' => $q->options,
                    'option_scores' => $q->option_scores
                ];
            })
        ]);
        
        // Initialize score variables
        $score = 0;
        $correctCount = 0;
        $maxPossibleScore = 0;
        $totalQuestions = $questions->count();
        
        // Build detailed results
        $details = [];
        
        // If cheating detected, skip evaluation and set score to 0
        if ($cheatingDetected) {
            $percentage = 0;
            $passed = false;
            $levelUp = false;
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
            
            $passed = $percentage >= 70;
            $levelUp = false;
            
            // If passed and not at max level, level up the user
            if ($passed && $level < 3 && Auth::check()) {
                $user = Auth::user();
                $newLevel = $level + 1;
                
                // Debug logging before level change
                \Log::info('About to level up user', [
                    'user_id' => $user->id,
                    'current_level' => $level,
                    'new_level' => $newLevel,
                    'language' => $language
                ]);
                
                // Make sure to use the same language parameter
                $user->setCurrentLevel($newLevel, $language);
                
                // Verify level change
                $updatedLevel = $user->getCurrentLevel($language);
                \Log::info('After level up', [
                    'user_id' => $user->id,
                    'updated_level' => $updatedLevel,
                    'level_changed' => ($updatedLevel == $newLevel),
                    'language' => $language
                ]);
                
                $levelUp = true;
            }
        }
        
        // Log all the calculation results
        \Log::info('Post-test calculation summary', [
            'score' => $score,
            'maxPossibleScore' => $maxPossibleScore,
            'percentage' => $percentage,
            'correctCount' => $correctCount,
            'totalQuestions' => $totalQuestions,
            'level' => $level,
            'passed' => $passed,
            'levelUp' => $levelUp
        ]);
        
        // Save assessment to database
        $assessment = Assessment::create([
            'user_id' => Auth::id(),
            'type' => 'post_test',
            'level' => $level,
            'score' => $score,
            'total_points' => $maxPossibleScore,
            'percentage' => $percentage,
            'passed' => $passed,
            'answers' => $answers,
            'language' => $language,
            'correct_count' => $correctCount,
            'total_questions' => $questions->count(),
            'time_limit' => \App\Models\TestSettings::getTimeLimit('post_test', $language) ?: 45,
            'details' => $details
        ]);
        
        // Debug logging
        \Log::info('Post-test assessment created', [
            'user_id' => Auth::id(),
            'assessment_id' => $assessment->id,
            'type' => 'post_test',
            'level' => $level,
            'score' => $score,
            'percentage' => $percentage,
            'passed' => $passed,
            'language' => $language,
            'level_up' => $levelUp
        ]);

        // Return the result
        return response()->json([
            'level' => $level,
            'score' => $score,
            'total_points' => $maxPossibleScore,
            'percentage' => $percentage,
            'passed' => $passed,
            'level_up' => $levelUp,
            'correct_count' => $correctCount,
            'total_questions' => $questions->count(),
            'language' => $language
        ]);
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
}
