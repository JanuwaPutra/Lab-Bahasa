<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    protected $apiKey;
    protected $baseUrl;
    protected $defaultModel;

    public function __construct()
    {
        $this->apiKey = env('OPENROUTER_API_KEY');
        $this->baseUrl = env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1');
        $this->defaultModel = env('OPENROUTER_MODEL', 'meta-llama/llama-4-maverick');
    }

    /**
     * Send a message to the OpenRouter API
     *
     * @param string $prompt
     * @param array $options
     * @return array|null
     */
    public function generateCompletion(string $prompt, array $options = [])
    {
        $model = $options['model'] ?? $this->defaultModel;
        $maxTokens = $options['max_tokens'] ?? 1000;
        $temperature = $options['temperature'] ?? 0.7;
        
        // If we're provided with messages, use those directly,
        // otherwise create a simple user message with the prompt
        $messages = $options['messages'] ?? [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ];
            
            // For debug purposes
            Log::debug('OpenRouter API Request: ' . json_encode($payload, JSON_PRETTY_PRINT));
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name')
            ])->post("{$this->baseUrl}/chat/completions", $payload);
            
            Log::debug('OpenRouter API Response: ' . $response->body());
            
            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('OpenRouter API error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('OpenRouter API exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract text from the API response
     *
     * @param array|null $response
     * @return string|null
     */
    public function extractText($response)
    {
        if (!$response || !isset($response['choices']) || empty($response['choices'])) {
            return null;
        }
        
        return $response['choices'][0]['message']['content'] ?? null;
    }
} 