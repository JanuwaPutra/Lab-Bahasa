<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherLanguage extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'teacher_id',
        'language',
        'level',
    ];
    
    /**
     * Get the teacher that owns the language setting.
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
} 