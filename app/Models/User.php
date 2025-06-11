<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the assessments for the user
     */
    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Get the test results for the user
     */
    public function testResults()
    {
        return $this->hasMany(TestResult::class);
    }

    /**
     * Get the virtual tutor conversations for the user
     */
    public function virtualTutorConversations()
    {
        return $this->hasMany(VirtualTutorConversation::class);
    }

    /**
     * Check if user has completed a specific assessment type
     */
    public function hasCompletedAssessment($type, $language = null)
    {
        $query = $this->assessments()->where('type', $type);
        
        if ($language) {
            $query->where('language', $language);
        }
        
        return $query->exists();
    }

    /**
     * Check if user has completed a pretest
     */
    public function hasCompletedPretest($language = null)
    {
        if ($language) {
            return $this->hasCompletedAssessment('pretest', $language);
        } else {
            // First check for Indonesian language
            if ($this->hasCompletedAssessment('pretest', 'id')) {
                return true;
            }
            // Then check for any language
            return $this->assessments()
                ->where('type', 'pretest')
                ->exists();
        }
    }

    /**
     * Get user's current level in a specific language
     */
    public function getCurrentLevel($language = 'id')
    {
        $latestAssessment = $this->assessments()
            ->where('language', $language)
            ->whereIn('type', ['pretest', 'post_test', 'placement', 'level_change'])
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $latestAssessment ? $latestAssessment->level : 1;
    }
    
    /**
     * Set user's current level for a specific language
     */
    public function setCurrentLevel($level, $language = 'en')
    {
        // Debug logging
        \Log::info('Setting user level', [
            'user_id' => $this->id,
            'user_name' => $this->name,
            'old_level' => $this->getCurrentLevel($language),
            'new_level' => $level,
            'language' => $language
        ]);
        
        // Create a new assessment to record the level change
        return $this->assessments()->create([
            'type' => 'level_change',
            'level' => $level,
            'language' => $language,
            'score' => 0,
            'percentage' => 0,
            'passed' => true,
            'answers' => []
        ]);
    }
    
    /**
     * Get the date when the user completed their pretest
     */
    public function pretestDate($language = 'id')
    {
        $pretest = $this->assessments()
            ->where('type', 'pretest')
            ->where('language', $language)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if (!$pretest || !$pretest->created_at) {
            return 'tanggal tidak tersedia';
        }
        
        return $pretest->created_at->format('d M Y');
    }

    /**
     * Check if user has a specific role
     * 
     * @param string|array $roles Role or roles to check
     * @return bool
     */
    public function hasRole($roles)
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        
        return $this->role === $roles;
    }

    /**
     * Get the evaluation settings for the user.
     */
    public function evaluationSettings()
    {
        return $this->hasOne(EvaluationSetting::class);
    }
    
    /**
     * Get evaluation settings managed by this teacher.
     */
    public function managedEvaluationSettings()
    {
        return $this->hasMany(EvaluationSetting::class, 'teacher_id');
    }

    /**
     * Get the material progress records for the user.
     */
    public function materialProgress()
    {
        return $this->hasMany(UserMaterialProgress::class);
    }
    
    /**
     * Check if user can take post-test.
     */
    public function canTakePostTest($language = 'id')
    {
        // Get user's current level
        $level = $this->getCurrentLevel($language);
        
        // Check if all materials in this level have been completed
        return LearningMaterial::hasCompletedAllInLevel($this->id, $level, $language);
    }
}
