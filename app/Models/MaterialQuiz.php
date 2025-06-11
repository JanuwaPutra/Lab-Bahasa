<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialQuiz extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'learning_material_id',
        'title',
        'description',
        'passing_score',
        'time_limit',
        'questions',
        'must_pass',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'questions' => 'json',
        'passing_score' => 'integer',
        'time_limit' => 'integer',
        'must_pass' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Get the learning material that owns the quiz.
     */
    public function learningMaterial()
    {
        return $this->belongsTo(LearningMaterial::class);
    }
    
    /**
     * Get the total number of questions.
     */
    public function getQuestionCountAttribute()
    {
        if (!$this->questions) {
            return 0;
        }
        
        return count($this->questions);
    }
    
    /**
     * Format questions for display.
     */
    public function formatQuestionsForDisplay()
    {
        if (!$this->questions) {
            return [];
        }
        
        $formattedQuestions = [];
        
        foreach ($this->questions as $index => $question) {
            $questionData = [
                'id' => $index,
                'text' => $question['text'],
                'type' => $question['type'] ?? 'multiple_choice',
                'options' => $question['options'] ?? [],
                'points' => $question['points'] ?? 1,
            ];
            
            // Don't include the correct answer for frontend display
            $formattedQuestions[] = $questionData;
        }
        
        return $formattedQuestions;
    }
    
    /**
     * Check if an answer is correct.
     */
    public function checkAnswer($questionId, $answer)
    {
        if (!isset($this->questions[$questionId])) {
            return false;
        }
        
        $question = $this->questions[$questionId];
        
        if ($question['type'] === 'multiple_choice') {
            return isset($question['correct_answer']) && $answer == $question['correct_answer'];
        } elseif ($question['type'] === 'text_input') {
            $correctAnswer = strtolower(trim($question['correct_answer']));
            $userAnswer = strtolower(trim($answer));
            
            // Check for exact match or allowed alternatives
            if ($userAnswer === $correctAnswer) {
                return true;
            }
            
            // Check alternatives if defined
            if (isset($question['alternatives']) && is_array($question['alternatives'])) {
                foreach ($question['alternatives'] as $alternative) {
                    if (strtolower(trim($alternative)) === $userAnswer) {
                        return true;
                    }
                }
            }
            
            return false;
        }
        
        return false;
    }
    
    /**
     * Calculate score based on answers.
     */
    public function calculateScore($answers)
    {
        $totalPoints = 0;
        $earnedPoints = 0;
        $debugInfo = [];
        
        \Log::info('Calculating quiz score', [
            'quiz_id' => $this->id,
            'answers' => $answers,
            'questions_count' => count($this->questions ?? [])
        ]);
        
        if (!is_array($this->questions) || empty($this->questions)) {
            \Log::warning('No questions found for quiz', ['quiz_id' => $this->id]);
            return 0;
        }
        
        foreach ($this->questions as $index => $question) {
            $points = $question['points'] ?? 1;
            $totalPoints += $points;
            
            $answerGiven = isset($answers[$index]);
            $answerValue = $answerGiven ? $answers[$index] : null;
            $isCorrect = $answerGiven && $this->checkAnswer($index, $answerValue);
            
            $debugInfo[] = [
                'question_index' => $index,
                'question_text' => substr($question['text'] ?? 'Unknown', 0, 50),
                'answer_given' => $answerGiven,
                'answer_value' => $answerValue,
                'is_correct' => $isCorrect,
                'points' => $points
            ];
            
            if ($isCorrect) {
                $earnedPoints += $points;
            }
        }
        
        $score = ($totalPoints === 0) ? 0 : round(($earnedPoints / $totalPoints) * 100);
        
        \Log::info('Quiz score calculated', [
            'quiz_id' => $this->id,
            'total_points' => $totalPoints,
            'earned_points' => $earnedPoints,
            'score_percentage' => $score,
            'question_details' => $debugInfo
        ]);
        
        return $score;
    }
    
    /**
     * Check if a score passes the quiz.
     */
    public function isPassing($score)
    {
        return $score >= $this->passing_score;
    }
} 