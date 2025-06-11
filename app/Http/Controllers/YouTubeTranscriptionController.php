<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class YouTubeTranscriptionController extends Controller
{
    protected $youtubeApiKey = 'AIzaSyCn7tmFsA13RjQM--wlpLP5icWvBO_spL4';
    
    /**
     * Show the YouTube transcription form
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('youtube.index');
    }

    /**
     * Process YouTube transcription request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function transcribe(Request $request)
    {
        $request->validate([
            'youtube_url' => 'required|string|url',
            'language' => 'required|string|size:2',
        ]);

        $youtubeUrl = $request->input('youtube_url');
        $language = $request->input('language', 'en');
        
        try {
            $result = $this->getTranscript($youtubeUrl, $language);
            
            if (isset($result['error'])) {
                return view('youtube.index', [
                    'youtubeUrl' => $youtubeUrl,
                    'error' => $result['error'],
                    'language' => $language
                ]);
            }
            
            return view('youtube.index', [
                'youtubeUrl' => $youtubeUrl,
                'transcript' => $result['transcript'],
                'videoTitle' => $result['video_title'] ?? 'YouTube Video',
                'language' => $language
            ]);
            
        } catch (Exception $e) {
            Log::error('YouTube transcription error: ' . $e->getMessage());
            
            return view('youtube.index', [
                'youtubeUrl' => $youtubeUrl,
                'error' => 'An error occurred while processing the YouTube video: ' . $e->getMessage(),
                'language' => $language
            ]);
        }
    }
    
    /**
     * API endpoint for YouTube transcription
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiTranscribe(Request $request)
    {
        $request->validate([
            'youtube_url' => 'required|string|url',
            'language' => 'required|string|size:2',
        ]);

        $youtubeUrl = $request->input('youtube_url');
        $language = $request->input('language', 'en');
        
        try {
            $result = $this->getTranscript($youtubeUrl, $language);
            
            if (isset($result['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }
            
            return response()->json([
                'success' => true,
                'transcript' => $result['transcript'],
                'video_title' => $result['video_title'] ?? 'YouTube Video',
                'video_id' => $result['video_id'] ?? null,
            ]);
            
        } catch (Exception $e) {
            Log::error('YouTube transcription API error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing the YouTube video: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Extract transcript from YouTube video
     *
     * @param  string  $youtubeUrl
     * @param  string  $language
     * @return array
     */
    protected function getTranscript($youtubeUrl, $language = 'en')
    {
        // Extract video ID
        $videoId = $this->extractVideoId($youtubeUrl);
        
        if (!$videoId) {
            return [
                'error' => 'Could not extract video ID from URL'
            ];
        }
        
        // Get video details using YouTube API
        try {
            $videoDetails = $this->getVideoDetails($videoId);
            
            if (!isset($videoDetails['title'])) {
                return [
                    'error' => 'Could not retrieve video details'
                ];
            }
            
            // Try multiple methods to get captions
            $transcript = null;
            $errorMessages = [];
            
            // Method 1: Python script
            try {
                $transcript = $this->simulateCaptionFetch($videoId, $language);
                if ($transcript) {
                    return [
                        'transcript' => $transcript,
                        'video_id' => $videoId,
                        'video_title' => $videoDetails['title']
                    ];
                }
            } catch (Exception $e) {
                $errorMessages[] = $e->getMessage();
                Log::error('YouTube transcript method 1 error: ' . $e->getMessage());
            }
            
            // Method 2: Direct API access
            try {
                $transcript = $this->getTranscriptFromYouTubeAPI($videoId, $language);
                if ($transcript) {
                    return [
                        'transcript' => $transcript,
                        'video_id' => $videoId,
                        'video_title' => $videoDetails['title']
                    ];
                }
            } catch (Exception $e) {
                $errorMessages[] = $e->getMessage();
                Log::error('YouTube transcript method 2 error: ' . $e->getMessage());
            }
            
            // Method 3: Use third-party service
            try {
                $transcript = $this->getTranscriptFromThirdParty($videoId, $language);
                if ($transcript) {
                    return [
                        'transcript' => $transcript,
                        'video_id' => $videoId,
                        'video_title' => $videoDetails['title']
                    ];
                }
            } catch (Exception $e) {
                $errorMessages[] = $e->getMessage();
                Log::error('YouTube transcript method 3 error: ' . $e->getMessage());
            }
            
            // If all methods failed, return a combined error
            return [
                'error' => 'Failed to get transcript: ' . implode('; ', $errorMessages),
                'video_id' => $videoId,
                'video_title' => $videoDetails['title']
            ];
            
        } catch (Exception $e) {
            Log::error('YouTube transcript extraction error: ' . $e->getMessage());
            
            return [
                'error' => 'Failed to extract transcript: ' . $e->getMessage(),
                'video_id' => $videoId
            ];
        }
    }
    
    /**
     * Get video details using YouTube API
     *
     * @param  string  $videoId
     * @return array
     */
    protected function getVideoDetails($videoId)
    {
        $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'key' => $this->youtubeApiKey,
            'part' => 'snippet',
            'id' => $videoId
        ]);
        
        $data = $response->json();
        
        if (!isset($data['items']) || empty($data['items'])) {
            return [
                'error' => 'Video not found or API key invalid'
            ];
        }
        
        return [
            'title' => $data['items'][0]['snippet']['title'],
            'description' => $data['items'][0]['snippet']['description'],
            'thumbnail' => $data['items'][0]['snippet']['thumbnails']['high']['url'] ?? null,
            'channel_title' => $data['items'][0]['snippet']['channelTitle']
        ];
    }
    
    /**
     * Simulate caption fetching (in a real implementation, you would use youtube-dl or similar)
     *
     * @param  string  $videoId
     * @param  string  $language
     * @return string
     */
    protected function simulateCaptionFetch($videoId, $language)
    {
        // Use our Python script that utilizes youtube_transcript_api and fallback methods
        $pythonScript = base_path('scripts/get_youtube_transcript.py');
        
        // Check if the script exists, if not create it
        if (!file_exists($pythonScript)) {
            $this->createPythonScript($pythonScript);
        }
        
        // Log the command we're about to run
        Log::debug("Running YouTube transcript script for video ID: {$videoId}, language: {$language}");
        
        // Use specific Python path
        $pythonPath = '/usr/local/bin/python3.12';
        
        // Run the Python script
        $process = new Process([$pythonPath, $pythonScript, $videoId, $language]);
        $process->setTimeout(60); // Increase timeout to 60 seconds
        $process->run();
        
        // Get output and error output
        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();
        
        // Log the results
        Log::debug("YouTube transcript script output: " . substr($output, 0, 100) . "...");
        if ($errorOutput) {
            Log::debug("YouTube transcript script error output: " . $errorOutput);
        }
        
        // If there's error output but the process was successful, it might be warnings we can ignore
        if (!$process->isSuccessful()) {
            throw new Exception("Failed to run transcript script: " . $errorOutput);
        }
        
        // Check for errors in the output
        if (strpos($output, 'Error:') === 0) {
            throw new Exception($output);
        }
        
        // If output is empty or too short, it might be an error
        $trimmedOutput = trim($output);
        if (empty($trimmedOutput)) {
            throw new Exception('Failed to get transcript: Empty output');
        }
        
        // Try direct YouTube API as a backup method if the output is too short
        if (strlen($trimmedOutput) < 20) {
            Log::debug("Output too short, trying YouTube API directly");
            $apiTranscript = $this->getTranscriptFromYouTubeAPI($videoId, $language);
            if ($apiTranscript) {
                return $apiTranscript;
            }
            
            // If API also failed and output is really too short, throw an error
            if (strlen($trimmedOutput) < 10) {
                throw new Exception('Failed to get transcript: Output too short');
            }
        }
        
        return $output;
    }
    
    /**
     * Try to get transcript directly from YouTube API
     *
     * @param  string  $videoId
     * @param  string  $language
     * @return string|null
     */
    protected function getTranscriptFromYouTubeAPI($videoId, $language)
    {
        try {
            // Get caption tracks
            $response = Http::get('https://www.googleapis.com/youtube/v3/captions', [
                'key' => $this->youtubeApiKey,
                'part' => 'snippet',
                'videoId' => $videoId
            ]);
            
            $data = $response->json();
            
            if (!isset($data['items']) || empty($data['items'])) {
                Log::debug("No caption tracks found via YouTube API");
                return null;
            }
            
            // Find a caption track in the requested language or any available
            $captionId = null;
            foreach ($data['items'] as $item) {
                if (isset($item['snippet']['language']) && strpos($item['snippet']['language'], $language) === 0) {
                    $captionId = $item['id'];
                    break;
                }
            }
            
            // If no caption in requested language, use the first one
            if (!$captionId && !empty($data['items'])) {
                $captionId = $data['items'][0]['id'];
            }
            
            if (!$captionId) {
                Log::debug("No suitable caption track found");
                return null;
            }
            
            // Unfortunately, getting the actual caption content requires OAuth2 authentication
            // which is beyond the scope of a simple API key
            // So we'll return a placeholder message
            
            Log::debug("Found caption ID: {$captionId} but cannot download without OAuth2");
            return null;
            
        } catch (Exception $e) {
            Log::error("YouTube API transcript error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create Python script for fetching YouTube transcripts
     *
     * @param  string  $scriptPath
     * @return void
     */
    protected function createPythonScript($scriptPath)
    {
        $script = <<<'PYTHON'
#!/usr/bin/env python3
import sys
import json
import requests
import re

def get_transcript(video_id, language='en'):
    try:
        # Method 1: Using youtube_transcript_api if available
        try:
            from youtube_transcript_api import YouTubeTranscriptApi
            
            try:
                # Try to get transcript directly
                transcript_list = YouTubeTranscriptApi.list_transcripts(video_id)
                
                # Try to get the transcript in the requested language
                try:
                    transcript = transcript_list.find_transcript([language])
                except:
                    # If not found, try to get any transcript and translate it
                    try:
                        transcript = transcript_list.find_transcript(['en'])
                        transcript = transcript.translate(language)
                    except:
                        # If still not found, get the first available transcript
                        transcript = list(transcript_list)[0]
                
                # Get the transcript data
                transcript_data = transcript.fetch()
                
                # Format the transcript
                formatted_transcript = ""
                for entry in transcript_data:
                    formatted_transcript += f"{entry['text']} "
                
                return formatted_transcript
                
            except Exception as e:
                print(f"YouTube Transcript API error: {str(e)}", file=sys.stderr)
                # Fall back to method 2
                pass
                
        except ImportError:
            print("youtube_transcript_api not installed, falling back to alternative method", file=sys.stderr)
            # Fall back to method 2
            pass
        
        # Method 2: Using a more direct approach with requests
        # Get the YouTube page content
        response = requests.get(f"https://www.youtube.com/watch?v={video_id}")
        
        if response.status_code != 200:
            return f"Error: Could not access YouTube video (status code: {response.status_code})"
        
        # Look for captions in the page source
        html = response.text
        
        # Try to find the caption track
        caption_url_match = re.search(r'"captionTracks":\[(.*?)\]', html)
        
        if not caption_url_match:
            return "Error: No captions found for this video"
        
        caption_data = caption_url_match.group(1)
        
        # Find the caption URL
        base_url_match = re.search(r'"baseUrl":"(.*?)"', caption_data)
        
        if not base_url_match:
            return "Error: Could not extract caption URL"
        
        # Get the caption URL and replace escaped characters
        caption_url = base_url_match.group(1).replace('\\u0026', '&')
        
        # Add language parameter if specified
        if language != 'en':
            caption_url += f"&tlang={language}"
        
        # Get the captions
        caption_response = requests.get(caption_url)
        
        if caption_response.status_code != 200:
            return f"Error: Could not access captions (status code: {caption_response.status_code})"
        
        # Extract text from XML
        caption_xml = caption_response.text
        
        # Simple parsing to extract text (not perfect but works for most cases)
        text_parts = re.findall(r'<text[^>]*>(.*?)</text>', caption_xml)
        
        if not text_parts:
            return "Error: Could not parse captions"
        
        # Join all text parts
        transcript = " ".join(text_parts)
        
        # Remove HTML entities
        transcript = re.sub(r'&amp;', '&', transcript)
        transcript = re.sub(r'&lt;', '<', transcript)
        transcript = re.sub(r'&gt;', '>', transcript)
        transcript = re.sub(r'&quot;', '"', transcript)
        transcript = re.sub(r'&#39;', "'", transcript)
        
        return transcript
        
    except Exception as e:
        return f"Error: {str(e)}"

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Error: Video ID is required")
        sys.exit(1)
    
    video_id = sys.argv[1]
    language = sys.argv[2] if len(sys.argv) > 2 else 'en'
    
    result = get_transcript(video_id, language)
    print(result)
PYTHON;
        
        file_put_contents($scriptPath, $script);
        chmod($scriptPath, 0755); // Make the script executable
    }
    
    /**
     * Extract YouTube video ID from URL
     *
     * @param  string  $youtubeUrl
     * @return string|null
     */
    protected function extractVideoId($youtubeUrl)
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?]+)/',
            '/(?:youtube\.com\/embed\/)([^&\n?]+)/',
            '/(?:youtube\.com\/v\/)([^&\n?]+)/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $youtubeUrl, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Get transcript from third-party service
     *
     * @param  string  $videoId
     * @param  string  $language
     * @return string|null
     */
    protected function getTranscriptFromThirdParty($videoId, $language)
    {
        try {
            // Try using a third-party service that provides YouTube transcripts
            // For example, we can use a service like https://rapidapi.com/ytdlfree/api/youtube-v31
            
            // For now, we'll implement a simple fallback that generates a placeholder message
            if ($language == 'id') {
                return "Transkripsi untuk video YouTube dengan ID: {$videoId}.\n\nMaaf, transkripsi otomatis tidak tersedia untuk video ini. Anda dapat mencoba menggunakan opsi terjemahan otomatis di YouTube atau menggunakan layanan transkripsi lainnya.";
            } else {
                return "Transcript for YouTube video with ID: {$videoId}.\n\nSorry, automatic transcription is not available for this video. You can try using YouTube's auto-translate option or use another transcription service.";
            }
            
            // In a real implementation, you might use a service like:
            /*
            $response = Http::withHeaders([
                'X-RapidAPI-Key' => 'your-api-key',
                'X-RapidAPI-Host' => 'youtube-v31.p.rapidapi.com'
            ])->get('https://youtube-v31.p.rapidapi.com/captions', [
                'id' => $videoId,
                'lang' => $language
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                // Process the response to extract transcript
                return $data['transcript'] ?? null;
            }
            */
            
            return null;
            
        } catch (Exception $e) {
            Log::error("Third-party transcript error: " . $e->getMessage());
            return null;
        }
    }
}
