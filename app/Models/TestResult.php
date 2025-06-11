<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestResult extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'assessment_id',
        'test_type',
        'original_text',
        'corrected_text',
        'recognized_text',
        'reference_text',
        'accuracy',
        'feedback',
        'word_count',
        'language'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'accuracy' => 'float',
        'word_count' => 'integer'
    ];

    /**
     * Get the user that owns the test result
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the assessment associated with the test result
     */
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
}
