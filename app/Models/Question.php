<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'text',
        'type',
        'options',
        'option_scores',
        'correct_answer',
        'level',
        'assessment_type',
        'min_words',
        'points',
        'active',
        'language'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'options' => 'json',
        'option_scores' => 'json',
        'active' => 'boolean',
        'level' => 'integer',
        'min_words' => 'integer',
        'points' => 'integer'
    ];

    /**
     * Get the options attribute.
     *
     * @param  string|null  $value
     * @return array
     */
    public function getOptionsAttribute($value)
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
     * Set the options attribute.
     *
     * @param  array|string  $value
     * @return void
     */
    public function setOptionsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['options'] = json_encode($value);
        } else {
            $this->attributes['options'] = $value;
        }
    }

    /**
     * Get the option_scores attribute.
     *
     * @param  string|null  $value
     * @return array
     */
    public function getOptionScoresAttribute($value)
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
     * Set the option_scores attribute.
     *
     * @param  array|string  $value
     * @return void
     */
    public function setOptionScoresAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['option_scores'] = json_encode($value);
        } else {
            $this->attributes['option_scores'] = $value;
        }
    }

    /**
     * Scope a query to only include active questions.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to filter by level.
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope a query to filter by assessment type.
     */
    public function scopeByAssessmentType($query, $type)
    {
        return $query->where('assessment_type', $type);
    }

    /**
     * Scope a query to filter by language.
     */
    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Get the correct answer for display purposes.
     */
    public function getCorrectAnswerTextAttribute()
    {
        if ($this->type == 'multiple_choice' && is_numeric($this->correct_answer)) {
            $options = $this->options;
            $index = (int) $this->correct_answer;
            return isset($options[$index]) ? $options[$index] : 'Invalid option';
        }
        
        return $this->correct_answer;
    }
}
