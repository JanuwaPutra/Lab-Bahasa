<?php

namespace App\Http\Controllers;

use App\Services\SpeechService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\TestResult;

class SpeechController extends Controller
{
    protected $speechService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\SpeechService  $speechService
     * @return void
     */
    public function __construct(SpeechService $speechService)
    {
        $this->speechService = $speechService;
    }

    /**
     * Show the speech recognition form
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Check PHP upload settings
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        
        // Convert to bytes
        $uploadMaxBytes = $this->returnBytes($uploadMaxFilesize);
        $postMaxBytes = $this->returnBytes($postMaxSize);
        
        // Our desired limit is 8MB
        $desiredLimit = 8 * 1024 * 1024;
        
        $phpConfigWarning = null;
        if ($uploadMaxBytes < $desiredLimit || $postMaxBytes < $desiredLimit) {
            $phpConfigWarning = "Peringatan: Konfigurasi server PHP membatasi unggahan file ke " . 
                min($uploadMaxFilesize, $postMaxSize) . 
                ". Untuk mengunggah file hingga 8MB, hubungi administrator server.";
            
            Log::warning("PHP configuration limits file uploads: upload_max_filesize=$uploadMaxFilesize, post_max_size=$postMaxSize");
        }
        
        // Return the view without any result variables
        // This ensures no speech recognition error messages appear before submission
        return view('speech.index', [
            'phpConfigWarning' => $phpConfigWarning
            // No recognizedText, accuracy, or feedback variables are passed
        ]);
    }

    /**
     * Convert PHP size values (like 2M, 8M) to bytes
     */
    private function returnBytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        
        return $val;
    }

    /**
     * Process speech recognition request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function recognize(Request $request)
    {
        // Validate request
        $request->validate([
            'audio' => 'nullable|file|mimes:wav,mp3,ogg,webm|max:8192', // 8MB max (8192KB)
        ]);
        
        $referenceText = $request->input('reference_text') ?? '';
        $language = $request->input('language', 'en');
        $inputType = $request->input('input_type', '');
        
        Log::info("Speech recognition request - Language: {$language}, Input type: {$inputType}");
        
        // Initialize variables with default values to prevent undefined variable errors
        $recognizedText = null;
        $accuracy = null;
        $feedback = null;
        $error = null;
        
        try {
            if ($request->hasFile('audio') && $request->file('audio')->isValid()) {
                $audioFile = $request->file('audio');
                Log::info("Processing uploaded audio file: {$audioFile->getClientOriginalName()}");
                
                // Process the audio file
                $result = $this->speechService->recognizeSpeech($audioFile, $referenceText, $language);
                $recognizedText = $result['recognized_text'];
                $accuracy = $result['accuracy'];
                $feedback = $result['feedback'];
                
            } elseif ($request->has('recorded_audio') && !empty($request->input('recorded_audio'))) {
                // Handle base64 encoded audio from browser recording
                Log::info("Processing browser-recorded audio");
                
                $audioData = $request->input('recorded_audio');
                $mimeType = null;
                
                // Extract base64 data and MIME type
                if (strpos($audioData, 'base64,') !== false) {
                    list($header, $audioData) = explode('base64,', $audioData, 2);
                    
                    // Extract MIME type from header
                    if (preg_match('/data:(audio\/[^;]+)/', $header, $matches)) {
                        $mimeType = $matches[1];
                        Log::info("Audio MIME type from browser: " . $mimeType);
                    }
                }
                
                // Validasi data audio
                if (empty($audioData)) {
                    throw new \Exception('Received empty audio data');
                }
                
                // Create a temporary file with appropriate extension
                $ext = '.bin';  // Default extension
                if ($mimeType) {
                    switch ($mimeType) {
                        case 'audio/wav':
                        case 'audio/wave':
                        case 'audio/x-wav':
                            $ext = '.wav';
                            break;
                        case 'audio/webm':
                            $ext = '.webm';
                            break;
                        case 'audio/ogg':
                            $ext = '.ogg';
                            break;
                        case 'audio/mp3':
                        case 'audio/mpeg':
                            $ext = '.mp3';
                            break;
                    }
                }
                
                $tempFile = tempnam(sys_get_temp_dir(), 'speech_');
                $tempFileWithExt = $tempFile . $ext;
                rename($tempFile, $tempFileWithExt);
                $tempFile = $tempFileWithExt;
                
                Log::info("Created temporary file with extension: " . $tempFile);
                
                $decodedAudio = base64_decode($audioData);
                
                // Validasi ukuran audio
                $minSize = 1024; // Minimal 1KB
                $audioSize = strlen($decodedAudio);
                if ($audioSize < $minSize) {
                    throw new \Exception("Audio terlalu pendek atau kosong (ukuran: " . $audioSize . " bytes)");
                }
                
                // Save decoded audio to file
                if (file_put_contents($tempFile, $decodedAudio) === false) {
                    throw new \Exception("Gagal menyimpan file audio sementara");
                }
                
                // Verifikasi file telah dibuat dengan benar
                if (!file_exists($tempFile) || filesize($tempFile) < $minSize) {
                    throw new \Exception("Gagal membuat file audio temporary");
                }
                
                // Debug info
                Log::info("Audio temp file created: " . $tempFile . " (size: " . filesize($tempFile) . " bytes)");
                
                // Process the audio
                $result = $this->speechService->recognizeSpeech($tempFile, $referenceText, $language);
                $recognizedText = $result['recognized_text'];
                $accuracy = $result['accuracy'];
                $feedback = $result['feedback'];
                
                // Add detailed logging for debugging
                if (str_contains($recognizedText, 'Tidak dapat mengenali audio') || str_contains($recognizedText, 'Could not recognize audio')) {
                    Log::error("Speech recognition failed for language: {$language}");
                    Log::error("Audio file details: " . (file_exists($tempFile) ? "Size: " . filesize($tempFile) . " bytes, Path: {$tempFile}" : "File not found"));
                    Log::error("Result: " . json_encode($result));
                }
                
                // Clean up
                @unlink($tempFile);
            } else {
                // No valid audio input provided - return the form without processing
                return view('speech.index', [
                    'language' => $language
                ]);
            }
            
            // Save the test result if authenticated
            if (Auth::check() && $recognizedText) {
                TestResult::create([
                    'user_id' => Auth::id(),
                    'test_type' => 'speech',
                    'recognized_text' => $recognizedText,
                    'reference_text' => $referenceText,
                    'accuracy' => $accuracy,
                    'feedback' => $feedback,
                    'language' => $language
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error("Error in speech recognition: " . $e->getMessage());
            $error = "An error occurred during speech processing: " . $e->getMessage();
        }
        
        return view('speech.index', [
            'recognizedText' => $recognizedText,
            'referenceText' => $referenceText,
            'accuracy' => $accuracy,
            'feedback' => $feedback,
            'language' => $language,
            'error' => $error
        ]);
    }

    /**
     * API endpoint for speech recognition
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiRecognize(Request $request)
    {
        try {
            if (!$request->hasFile('audio')) {
                return response()->json(['error' => 'No audio file provided'], 400);
            }
            
            $audioFile = $request->file('audio');
            $referenceText = $request->input('reference_text') ?? '';
            $language = $request->input('language', 'en');
            
            $result = $this->speechService->recognizeSpeech($audioFile, $referenceText, $language);
            
            return response()->json([
                'recognized_text' => $result['recognized_text'],
                'reference_text' => $referenceText,
                'accuracy' => $result['accuracy'],
                'feedback' => $result['feedback']
            ]);
            
        } catch (\Exception $e) {
            Log::error("API speech recognition error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * API endpoint for direct speech recognition (browser-based)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiDirectRecognize(Request $request)
    {
        try {
            $data = $request->json()->all();
            
            if (!isset($data['audio']) || empty($data['audio'])) {
                return response()->json(['error' => 'No audio data provided'], 400);
            }
            
            $audioData = $data['audio'];
            $referenceText = isset($data['reference_text']) ? $data['reference_text'] : '';
            $language = $data['language'] ?? 'en';
            $mimeType = null;
            
            // Extract base64 data and MIME type
            if (strpos($audioData, 'base64,') !== false) {
                list($header, $audioData) = explode('base64,', $audioData, 2);
                
                // Extract MIME type from header
                if (preg_match('/data:(audio\/[^;]+)/', $header, $matches)) {
                    $mimeType = $matches[1];
                    Log::info("API: Audio MIME type from browser: " . $mimeType);
                }
            }
            
            // Create a temporary file with appropriate extension
            $ext = '.bin';  // Default extension
            if ($mimeType) {
                switch ($mimeType) {
                    case 'audio/wav':
                    case 'audio/wave':
                    case 'audio/x-wav':
                        $ext = '.wav';
                        break;
                    case 'audio/webm':
                        $ext = '.webm';
                        break;
                    case 'audio/ogg':
                        $ext = '.ogg';
                        break;
                    case 'audio/mp3':
                    case 'audio/mpeg':
                        $ext = '.mp3';
                        break;
                }
            }
            
            $tempFile = tempnam(sys_get_temp_dir(), 'speech_');
            $tempFileWithExt = $tempFile . $ext;
            rename($tempFile, $tempFileWithExt);
            $tempFile = $tempFileWithExt;
            
            Log::info("API: Created temporary file with extension: " . $tempFile);
            
            $decodedAudio = base64_decode($audioData);
            
            // Validasi ukuran audio
            $minSize = 1024; // Minimal 1KB
            $audioSize = strlen($decodedAudio);
            if ($audioSize < $minSize) {
                throw new \Exception("Audio terlalu pendek atau kosong (ukuran: " . $audioSize . " bytes)");
            }
            
            // Save decoded audio to file
            if (file_put_contents($tempFile, $decodedAudio) === false) {
                throw new \Exception("Gagal menyimpan file audio sementara");
            }
            
            // Verifikasi file telah dibuat dengan benar
            if (!file_exists($tempFile) || filesize($tempFile) < $minSize) {
                throw new \Exception("Gagal membuat file audio temporary");
            }
            
            // Debug info
            Log::info("API: Audio temp file created: " . $tempFile . " (size: " . filesize($tempFile) . " bytes)");
            
            // Process the audio
            $result = $this->speechService->recognizeSpeech($tempFile, $referenceText, $language);
            $recognizedText = $result['recognized_text'];
            $accuracy = $result['accuracy'];
            $feedback = $result['feedback'];
            
            // Add detailed logging for debugging
            if (str_contains($recognizedText, 'Tidak dapat mengenali audio') || str_contains($recognizedText, 'Could not recognize audio')) {
                Log::error("Speech recognition failed for language: {$language}");
                Log::error("Audio file details: " . (file_exists($tempFile) ? "Size: " . filesize($tempFile) . " bytes, Path: {$tempFile}" : "File not found"));
                Log::error("Result: " . json_encode($result));
            }
            
            // Clean up
            @unlink($tempFile);
            
            return response()->json([
                'recognized_text' => $recognizedText,
                'reference_text' => $referenceText,
                'accuracy' => $accuracy,
                'feedback' => $feedback
            ]);
            
        } catch (\Exception $e) {
            Log::error("API direct speech recognition error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Test if the speech recognition system is working
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testSystem()
    {
        try {
            // Define Python path
            $pythonPath = '/usr/local/bin/python3.12'; // Use specific Python version
            
            // Check if Python is available
            $pythonVersion = exec("$pythonPath --version 2>&1", $pythonOutput, $pythonReturnCode);
            $pythonWorking = $pythonReturnCode === 0;
            
            // Check if the script file exists
            $scriptPath = base_path('scripts/speech_recognition_service.py');
            $scriptExists = file_exists($scriptPath);
            
            // Try to run the script with --version flag
            $scriptOutput = [];
            $scriptReturnCode = 0;
            if ($scriptExists) {
                exec("$pythonPath $scriptPath --version 2>&1", $scriptOutput, $scriptReturnCode);
            }
            
            // Check if required Python packages are installed
            $packagesCheck = [];
            if ($pythonWorking) {
                $requiredPackages = ['speech_recognition', 'jiwer'];
                foreach ($requiredPackages as $package) {
                    $cmd = "$pythonPath -c \"import {$package}; print('OK')\" 2>&1";
                    $output = [];
                    exec($cmd, $output, $returnCode);
                    $packagesCheck[$package] = ($returnCode === 0) ? true : false;
                }
            }
            
            return response()->json([
                'system_status' => [
                    'python_available' => $pythonWorking,
                    'python_version' => $pythonOutput[0] ?? 'Unknown',
                    'script_exists' => $scriptExists,
                    'script_executable' => $scriptReturnCode === 0,
                    'script_output' => implode("\n", $scriptOutput),
                    'required_packages' => $packagesCheck,
                    'temp_dir_writable' => is_writable(sys_get_temp_dir()),
                    'logs_dir_writable' => is_writable(storage_path('logs')),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error in speech system test: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
