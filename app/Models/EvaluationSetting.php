<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'teacher_id',
        'show_placement_test',
        'show_listening_test',
        'show_reading_test',
        'show_speaking_test',
        'show_grammar_test',
        'custom_settings',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'show_placement_test' => 'boolean',
        'show_listening_test' => 'boolean',
        'show_reading_test' => 'boolean',
        'show_speaking_test' => 'boolean',
        'show_grammar_test' => 'boolean',
        'custom_settings' => 'json',
    ];

    /**
     * Get the user that owns the evaluation setting.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the teacher that manages this evaluation setting.
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get settings for a specific user or create default if not exists
     */
    public static function getForUser($userId)
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'show_placement_test' => true,
                'show_listening_test' => true,
                'show_reading_test' => true,
                'show_speaking_test' => true,
                'show_grammar_test' => true,
            ]
        );
    }
} 