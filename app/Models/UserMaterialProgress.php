<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMaterialProgress extends Model
{
    use HasFactory;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_material_progress';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'learning_material_id',
        'completed',
        'completed_at',
        'quiz_attempts',
        'quiz_passed',
        'quiz_score',
        'quiz_answers',
        'temp_answers',
        'quiz_start_time',
        'quiz_end_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'quiz_passed' => 'boolean',
        'quiz_score' => 'integer',
        'quiz_attempts' => 'integer',
        'quiz_answers' => 'json',
        'temp_answers' => 'json',
        'quiz_start_time' => 'datetime',
        'quiz_end_time' => 'datetime',
    ];

    /**
     * Get the user that owns the progress.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the learning material.
     */
    public function learningMaterial()
    {
        return $this->belongsTo(LearningMaterial::class);
    }
    
    /**
     * Mark material as completed.
     */
    public function markCompleted()
    {
        $this->completed = true;
        $this->completed_at = now();
        $this->save();
        
        return $this;
    }
    
    /**
     * Record quiz attempt.
     */
    public function recordQuizAttempt($score, $answers)
    {
        $this->quiz_attempts += 1;
        $this->quiz_score = $score;
        $this->quiz_answers = $answers;
        $this->last_quiz_attempt = now();
        
        // Get the quiz and check if passed
        $quiz = $this->learningMaterial->quiz;
        if ($quiz && $quiz->isPassing($score)) {
            $this->quiz_passed = true;
            $this->passed_at = now();
        }
        
        $this->save();
        
        return $this;
    }
    
    /**
     * Check if can proceed to next material.
     */
    public function canProceedToNext()
    {
        // If completed and no quiz exists, can proceed
        if ($this->completed && !$this->learningMaterial->quiz) {
            return true;
        }
        
        // If completed and passed quiz, can proceed
        if ($this->completed && $this->quiz_passed) {
            return true;
        }
        
        // If completed and quiz exists but doesn't require passing, can proceed
        $quiz = $this->learningMaterial->quiz;
        if ($this->completed && $quiz && !$quiz->must_pass) {
            return true;
        }
        
        return false;
    }
} 