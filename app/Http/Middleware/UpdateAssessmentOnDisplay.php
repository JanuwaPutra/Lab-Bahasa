<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Assessment;
use App\Models\Question;
use App\Models\TestSettings;

class UpdateAssessmentOnDisplay
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the response first
        $response = $next($request);
        
        // Check if we're on the assessment detail page
        $routeName = $request->route()->getName();
        
        if ($routeName === 'teacher.test.result.detail') {
            $assessmentId = $request->route('id');
            $assessment = Assessment::find($assessmentId);
            
            if (!$assessment) {
                return $response;
            }
            
            // Fix missing duration or incorrect duration
            if (empty($assessment->duration) || $assessment->duration <= 0 || 
                $assessment->duration == 0.0 || 
                ($assessment->created_at && $assessment->updated_at && $assessment->duration < 0.1)) {
                
                // Hitung durasi dari timestamp
                if ($assessment->created_at && $assessment->updated_at) {
                    // Pastikan created_at lebih awal dari updated_at
                    if ($assessment->created_at->gt($assessment->updated_at)) {
                        // Jika created_at lebih besar dari updated_at, tukar nilainya untuk perhitungan
                        $seconds = $assessment->created_at->diffInSeconds($assessment->updated_at);
                    } else {
                        $seconds = $assessment->updated_at->diffInSeconds($assessment->created_at);
                    }
                    
                    // Pastikan nilai positif
                    $seconds = abs($seconds);
                    
                    // Jika waktu terlalu singkat, tetapkan minimal 3 detik
                    if ($seconds < 3) {
                        $seconds = 3;
                    }
                    
                    // Simpan durasi dalam menit dengan 1 desimal
                    $assessment->duration = round($seconds / 60, 1);
                    $assessment->save();
                } else {
                    // Jika tidak ada timestamp, gunakan default
                    $defaultDurations = [
                        'pretest' => 5,
                        'post_test' => 15,
                        'placement' => 10,
                        'listening' => 7,
                        'reading' => 7,
                        'speaking' => 5,
                        'grammar' => 5,
                        'default' => 7
                    ];
                    
                    $assessment->duration = $defaultDurations[$assessment->type] ?? $defaultDurations['default'];
                    $assessment->save();
                }
            }
            
            // Fix missing time_limit
            if (empty($assessment->time_limit) || $assessment->time_limit == 0) {
                $assessment->time_limit = TestSettings::getTimeLimit($assessment->type, $assessment->language) ?: 30;
                $assessment->save();
            }
            
            // Fix missing details
            $detailsNeedFix = false;
            
            if ($assessment->details === null || $assessment->details === '[]' || $assessment->details === '' || empty($assessment->details)) {
                $detailsNeedFix = true;
            } else {
                // Check if details is already decoded
                $details = $assessment->details;
                if (is_string($details)) {
                    try {
                        $details = json_decode($details, true);
                    } catch (\Exception $e) {
                        $detailsNeedFix = true;
                    }
                }
                
                // Check if details is empty or invalid array
                if (!is_array($details) || count($details) === 0) {
                    $detailsNeedFix = true;
                }
                
                // Check if at least the first item has required fields
                if (is_array($details) && count($details) > 0) {
                    $firstItem = $details[0];
                    if (!isset($firstItem['question']) || !isset($firstItem['user_answer'])) {
                        $detailsNeedFix = true;
                    }
                }
            }
            
            // Only fix details if needed and answers exist
            if ($detailsNeedFix && $assessment->answers) {
                // Get answers data
                $answers = $assessment->answers;
                
                if (empty($answers) || !is_array($answers)) {
                    return $response;
                }
                
                // Fetch questions for this assessment type
                $questions = Question::where('assessment_type', $this->getAssessmentType($assessment->type))
                    ->when($assessment->language, function ($query) use ($assessment) {
                        // Filter by language if available
                        return $query->where('language', $assessment->language);
                    })
                    ->when($assessment->type == 'post_test' && $assessment->level, function ($query) use ($assessment) {
                        // Filter by level for post tests
                        return $query->where('level', $assessment->level);
                    })
                    ->get();
                
                if ($questions->isEmpty()) {
                    return $response;
                }
                
                // Map questions by ID for easy lookup
                $questionsById = $questions->keyBy('id');
                
                // Build details array
                $details = [];
                $correctCount = 0;
                
                foreach ($answers as $questionId => $userAnswer) {
                    // Convert questionId to integer if it's a string
                    if (is_string($questionId)) {
                        $questionId = intval($questionId);
                    }
                    
                    $question = $questionsById->get($questionId);
                    if (!$question) {
                        continue;
                    }
                    
                    $isCorrect = false;
                    
                    // Check if answer is correct
                    if ($question->type == 'multiple_choice') {
                        $isCorrect = (int)$userAnswer === (int)$question->correct_answer;
                    } elseif ($question->type == 'true_false' || $question->type == 'fill_blank') {
                        $isCorrect = $userAnswer == $question->correct_answer;
                    } elseif ($question->type == 'essay') {
                        // For essay, just check if word count meets minimum requirement
                        $wordCount = str_word_count($userAnswer);
                        $isCorrect = $wordCount >= ($question->min_words ?? 10);
                    }
                    
                    if ($isCorrect) {
                        $correctCount++;
                    }
                    
                    // Add to details
                    $details[] = [
                        'question_id' => $questionId,
                        'question' => $question->text,
                        'user_answer' => $userAnswer,
                        'correct_answer' => $question->correct_answer,
                        'is_correct' => $isCorrect,
                        'type' => $question->type
                    ];
                }
                
                // Save details to assessment
                $assessment->details = $details;
                
                // Update correct_count and total_questions if needed
                if (empty($assessment->correct_count) || $assessment->correct_count == 0) {
                    $assessment->correct_count = $correctCount;
                }
                
                if (empty($assessment->total_questions) || $assessment->total_questions == 0) {
                    $assessment->total_questions = count($details);
                }
                
                $assessment->save();
            }
        }
        
        return $response;
    }
    
    /**
     * Convert assessment type to question assessment type.
     */
    private function getAssessmentType(string $type): string
    {
        $map = [
            'pretest' => 'pretest',
            'post_test' => 'post_test',
            'placement' => 'placement',
            'listening' => 'listening',
            'reading' => 'reading',
            'speaking' => 'speaking',
            'grammar' => 'grammar'
        ];
        
        return $map[$type] ?? 'pretest';
    }
}
