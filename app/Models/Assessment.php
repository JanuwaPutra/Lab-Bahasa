<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'level',
        'score',
        'total_points',
        'percentage',
        'passed',
        'answers',
        'results',
        'language',
        'details',
        'feedback',
        'correct_count',
        'total_questions',
        'time_limit'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'answers' => 'json',
        'results' => 'json',
        'details' => 'json',
        'passed' => 'boolean',
        'score' => 'float',
        'total_points' => 'float',
        'percentage' => 'float',
        'level' => 'integer',
        'correct_count' => 'integer',
        'total_questions' => 'integer',
        'time_limit' => 'integer'
    ];

    /**
     * Get the answers attribute.
     *
     * @param  mixed  $value
     * @return array
     */
    public function getAnswersAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        return json_decode($value, true) ?? [];
    }

    /**
     * Get the details attribute.
     *
     * @param  mixed  $value
     * @return array
     */
    public function getDetailsAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        return json_decode($value, true) ?? [];
    }

    /**
     * Set the answers attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setAnswersAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['answers'] = json_encode($value);
        } else if (is_string($value) && !empty($value)) {
            // Verifikasi jika string ini valid JSON
            try {
                json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->attributes['answers'] = $value;
                } else {
                    $this->attributes['answers'] = json_encode($value);
                }
            } catch (\Exception $e) {
                $this->attributes['answers'] = json_encode($value);
            }
        } else {
            $this->attributes['answers'] = $value;
        }
    }

    /**
     * Set the details attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setDetailsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['details'] = json_encode($value);
        } else if (is_string($value) && !empty($value)) {
            // Verifikasi jika string ini valid JSON
            try {
                json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->attributes['details'] = $value;
                } else {
                    $this->attributes['details'] = json_encode($value);
                }
            } catch (\Exception $e) {
                $this->attributes['details'] = json_encode($value);
            }
        } else {
            $this->attributes['details'] = $value;
        }
    }

    /**
     * Get the user that owns the assessment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the test results associated with the assessment
     */
    public function testResults()
    {
        return $this->hasMany(TestResult::class);
    }
}
