<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualTutorConversation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'language',
        'level',
        'exercise_type',
        'conversation_history',
        'last_message',
        'last_response',
        'feedback'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'conversation_history' => 'json',
    ];

    /**
     * Get the user that owns the conversation
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Add a new message to the conversation history
     */
    public function addMessage($role, $content)
    {
        $history = $this->conversation_history ?? [];
        
        $history[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => now()->toIso8601String()
        ];
        
        $this->conversation_history = $history;
        
        if ($role === 'user') {
            $this->last_message = $content;
        } else {
            $this->last_response = $content;
        }
        
        return $this->save();
    }
}
