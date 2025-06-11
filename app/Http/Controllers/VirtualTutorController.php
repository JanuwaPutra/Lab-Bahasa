<?php

namespace App\Http\Controllers;

use App\Services\VirtualTutorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\VirtualTutorConversation;

class VirtualTutorController extends Controller
{
    protected $tutorService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\VirtualTutorService  $tutorService
     * @return void
     */
    public function __construct(VirtualTutorService $tutorService)
    {
        $this->tutorService = $tutorService;
    }

    /**
     * Show the virtual tutor interface
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $languages = $this->tutorService->getSupportedLanguages();
        
        // Default values for user without conversation history
        $userData = [
            'has_history' => false,
            'conversation' => null,
            'session_id' => null
        ];
        
        // If user is authenticated, check for conversation history
        if (Auth::check()) {
            $conversation = VirtualTutorConversation::where('user_id', Auth::id())
                ->latest()
                ->first();
                
            if ($conversation) {
                $userData = [
                    'has_history' => true,
                    'conversation' => $conversation,
                    'session_id' => $conversation->session_id
                ];
            }
        }
        
        return view('virtual_tutor.index', compact('languages', 'userData'));
    }

    /**
     * Process chat message
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'language' => 'required|string',
            'level' => 'required|string|in:beginner,intermediate,advanced',
            'exercise_type' => 'required|string|in:free_conversation,writing_exercise,speaking_practice,grammar_focus',
        ]);

        $message = $request->input('message');
        $language = $request->input('language');
        $level = $request->input('level');
        $exerciseType = $request->input('exercise_type');
        $history = $request->input('history', []);
        
        // Generate response
        $result = $this->tutorService->generateChatResponse($message, $language, $level, $history, $exerciseType);
        
        // Save conversation history if user is authenticated
        if (Auth::check()) {
            $sessionId = $request->input('session_id');
            
            $conversation = VirtualTutorConversation::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'session_id' => $sessionId ?: uniqid('conv_')
                ],
                [
                    'language' => $language,
                    'level' => $level,
                    'exercise_type' => $exerciseType,
                    'conversation_history' => []
                ]
            );
            
            // Update last conversation history
            if (!$result['error']) {
                $conversation->addMessage('user', $message);
                $conversation->addMessage('assistant', $result['response']);
                
                // Save writing feedback if this is a writing exercise
                if ($exerciseType === 'writing_exercise' && isset($result['analysis'])) {
                    $conversation->feedback = $result['analysis'];
                    $conversation->save();
                }
                
                $result['session_id'] = $conversation->session_id;
            }
        }
        
        return response()->json($result);
    }

    /**
     * Process speaking practice
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function speech(Request $request)
    {
        try {
            $request->validate([
                'audio' => 'required|string',
                'language' => 'required|string',
                'level' => 'required|string|in:beginner,intermediate,advanced',
            ]);
            
            $audioData = $request->input('audio');
            $language = $request->input('language');
            $level = $request->input('level');
            
            // Extract base64 data
            if (strpos($audioData, 'base64,') !== false) {
                list($header, $audioData) = explode('base64,', $audioData, 2);
            }
            
            // Create a temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'speech_');
            file_put_contents($tempFile, base64_decode($audioData));
            
            // Here you would normally use a speech-to-text service
            // For now, we'll use a placeholder transcription
            // In production, integrate with a service like AWS Transcribe, Google Speech-to-Text, etc.
            $transcription = "This is a placeholder transcription. In a real implementation, this would be the text recognized from the audio file.";
            
            // For Indonesian language, provide a different placeholder
            if ($language === 'id') {
                $transcription = "Ini adalah transkripsi sementara. Dalam implementasi nyata, ini akan menjadi teks yang dikenali dari file audio.";
            }
            
            // Get feedback
            $result = $this->tutorService->generateSpeakingFeedback($transcription, $language, $level);
            $result['transcription'] = $transcription;
            
            // Clean up
            @unlink($tempFile);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error("Virtual tutor speech error: " . $e->getMessage());
            $language = $request->input('language', 'en');
            $errorMessage = $language === 'id' 
                ? 'Terjadi kesalahan saat memproses audio: ' . $e->getMessage()
                : 'Error processing audio: ' . $e->getMessage();
                
            return response()->json([
                'feedback' => $errorMessage,
                'error' => true
            ]);
        }
    }

    /**
     * Get topics based on language, level, and exercise type
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTopics(Request $request)
    {
        $language = $request->input('language', 'en');
        $level = $request->input('level', 'beginner');
        $exerciseType = $request->input('exercise_type', 'free_conversation');
        
        $topics = [];
        
        if ($exerciseType === 'free_conversation') {
            $topics = $this->tutorService->getConversationTopics($language, $level);
        } elseif ($exerciseType === 'writing_exercise') {
            $topics = $this->tutorService->getWritingPrompts($language, $level);
        } elseif ($exerciseType === 'speaking_practice') {
            $topics = $this->tutorService->getSpeakingTopics($language, $level);
        }
        
        return response()->json($topics);
    }

    /**
     * Get the conversation history for authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversationHistory(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
                'history' => [],
                'session_id' => null
            ]);
        }
        
        $sessionId = $request->input('session_id');
        
        // If session ID is provided, get that specific conversation
        if ($sessionId) {
            $conversation = VirtualTutorConversation::where('user_id', Auth::id())
                ->where('session_id', $sessionId)
                ->first();
        } else {
            // Otherwise get the most recent conversation
            $conversation = VirtualTutorConversation::where('user_id', Auth::id())
                ->latest()
                ->first();
        }
        
        if (!$conversation) {
            return response()->json([
                'success' => true,
                'message' => 'No conversation history found',
                'history' => [],
                'session_id' => null
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Conversation history retrieved',
            'history' => $conversation->conversation_history ?? [],
            'session_id' => $conversation->session_id,
            'language' => $conversation->language,
            'level' => $conversation->level,
            'exercise_type' => $conversation->exercise_type,
            'feedback' => $conversation->feedback
        ]);
    }
    
    /**
     * Reset conversation history
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetConversation(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
        }
        
        $sessionId = $request->input('session_id');
        
        if ($sessionId) {
            // Find and delete the specific conversation
            VirtualTutorConversation::where('user_id', Auth::id())
                ->where('session_id', $sessionId)
                ->delete();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Conversation reset successfully',
            'new_session_id' => uniqid('conv_')
        ]);
    }
}
