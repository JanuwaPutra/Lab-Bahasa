<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningMaterial extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'content',
        'type',
        'url',
        'level',
        'language',
        'metadata',
        'active',
        'order'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'json',
        'active' => 'boolean',
        'level' => 'integer',
        'order' => 'integer'
    ];

    /**
     * Get the exercises from metadata.
     *
     * @return array|null
     */
    public function getExercisesAttribute()
    {
        return $this->metadata['exercises'] ?? null;
    }

    /**
     * Get the video URL from metadata or url field.
     *
     * @return string|null
     */
    public function getVideoUrlAttribute()
    {
        if ($this->type === 'video') {
            return $this->url;
        }
        
        return $this->metadata['video_url'] ?? null;
    }

    /**
     * Get the duration of the learning material.
     *
     * @return int
     */
    public function getDurationAttribute()
    {
        return $this->metadata['duration'] ?? 10; // Default to 10 minutes
    }

    /**
     * Scope a query to only include active learning materials.
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
     * Scope a query to filter by language.
     */
    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Get the quiz associated with the learning material.
     */
    public function quiz()
    {
        return $this->hasOne(MaterialQuiz::class);
    }
    
    /**
     * Get all user progress records for this material.
     */
    public function userProgress()
    {
        return $this->hasMany(UserMaterialProgress::class);
    }
    
    /**
     * Get the next material in sequence.
     */
    public function getNextMaterial()
    {
        return static::where('language', $this->language)
            ->where('level', $this->level)
            ->where('order', '>', $this->order)
            ->orderBy('order', 'asc')
            ->first();
    }
    
    /**
     * Get the previous material in sequence.
     */
    public function getPreviousMaterial()
    {
        return static::where('language', $this->language)
            ->where('level', $this->level)
            ->where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();
    }
    
    /**
     * Check if this material is the first in a level.
     */
    public function isFirstInLevel()
    {
        return !static::where('language', $this->language)
            ->where('level', $this->level)
            ->where('order', '<', $this->order)
            ->exists();
    }
    
    /**
     * Check if this material is the last in a level.
     */
    public function isLastInLevel()
    {
        return !static::where('language', $this->language)
            ->where('level', $this->level)
            ->where('order', '>', $this->order)
            ->exists();
    }
    
    /**
     * Get user's progress for this material.
     */
    public function getProgressForUser($userId)
    {
        return $this->userProgress()->where('user_id', $userId)->first();
    }
    
    /**
     * Check if user can access this material.
     */
    public function canUserAccess($userId)
    {
        // First material is always accessible
        if ($this->isFirstInLevel()) {
            return true;
        }
        
        // Get previous material
        $prevMaterial = $this->getPreviousMaterial();
        if (!$prevMaterial) {
            return true;
        }
        
        // Check if user has completed and passed previous material
        $prevProgress = $prevMaterial->getProgressForUser($userId);
        if (!$prevProgress) {
            return false;
        }
        
        return $prevProgress->canProceedToNext();
    }
    
    /**
     * Check if all materials in this level have been completed by the user.
     */
    public static function hasCompletedAllInLevel($userId, $level, $language)
    {
        // Get all materials in this level
        $materials = static::where('level', $level)
            ->where('language', $language)
            ->orderBy('order')
            ->get();
        
        if ($materials->isEmpty()) {
            return false;
        }
        
        foreach ($materials as $material) {
            $progress = $material->getProgressForUser($userId);
            
            // If no progress or can't proceed to next, not completed
            if (!$progress || !$progress->canProceedToNext()) {
                return false;
            }
        }
        
        return true;
    }
}
