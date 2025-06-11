<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSettings extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'test_type',
        'time_limit',
        'language',
        'additional_settings'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'time_limit' => 'integer',
        'additional_settings' => 'json'
    ];

    /**
     * Get the time limit for a specific test type and language
     *
     * @param string $testType
     * @param string $language
     * @return int
     */
    public static function getTimeLimit($testType, $language = 'id')
    {
        $settings = self::where('test_type', $testType)
            ->where('language', $language)
            ->first();

        return $settings ? $settings->time_limit : 0;
    }
}
