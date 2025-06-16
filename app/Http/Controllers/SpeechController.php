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
                
                // Check for NoneType error and provide a more user-friendly message
                if (is_string($recognizedText) && strpos($recognizedText, "'NoneType' object has no attribute 'split'") !== false) {
                    Log::warning("Converting NoneType split error to user-friendly message");
                    $error = ($language === 'id') 
                        ? "Tidak dapat mengenali ucapan. Pastikan audio Anda jelas dan terdapat suara yang dapat dikenali."
                        : "Could not recognize speech. Please ensure your audio is clear and contains recognizable speech.";
                    $recognizedText = null;
                    $accuracy = null;
                    $feedback = null;
                }
                
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
                } else {
                    // If MIME type couldn't be detected, default to webm for browser recordings
                    Log::warning("MIME type not detected in audio data, defaulting to webm");
                    $ext = '.webm';
                }
                
                $tempFile = tempnam(sys_get_temp_dir(), 'speech_');
                $tempFileWithExt = $tempFile . $ext;
                rename($tempFile, $tempFileWithExt);
                $tempFile = $tempFileWithExt;
                
                Log::info("Created temporary file with extension: " . $tempFile);
                
                try {
                $decodedAudio = base64_decode($audioData);
                
                // Validasi ukuran audio
                $minSize = 1024; // Minimal 1KB
                $audioSize = strlen($decodedAudio);
                if ($audioSize < $minSize) {
                    throw new \Exception("Audio terlalu pendek atau kosong (ukuran: " . $audioSize . " bytes)");
                }
                    
                    // Check for audio format validity (enhanced validation)
                    if ($ext === '.webm' && !$this->isValidWebm($decodedAudio)) {
                        Log::error("Invalid WebM format detected");
                        
                        // Use language-specific message
                        if ($language === 'id') {
                            throw new \Exception("Format audio tidak valid atau tidak ada suara yang terdeteksi. Pastikan mikrofon Anda berfungsi dan rekam suara dengan jelas.");
                        } else {
                            throw new \Exception("Invalid audio format or no voice detected. Please ensure your microphone is working and record with a clear voice.");
                        }
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
                    
                    // Ensure file format is correct by checking file signature
                    $fileInfo = mime_content_type($tempFile);
                    Log::info("Detected MIME type from file: " . $fileInfo);
                    
                    // If the detected type doesn't match the extension, we can fix the extension
                    if (strpos($fileInfo, 'webm') !== false && !str_ends_with($tempFile, '.webm')) {
                        $newTempFile = $tempFile . '.webm';
                        rename($tempFile, $newTempFile);
                        $tempFile = $newTempFile;
                        Log::info("Renamed file to match webm content type: " . $tempFile);
                    }
                
                // Process the audio
                $result = $this->speechService->recognizeSpeech($tempFile, $referenceText, $language);
                    $recognizedText = $result['recognized_text'] ?? null;
                    $accuracy = $result['accuracy'] ?? 0;
                    $feedback = $result['feedback'] ?? null;
                
                    // Check for specific errors in the response
                    if (is_string($recognizedText) && (
                        str_contains($recognizedText, 'Tidak dapat mengenali ucapan') || 
                        str_contains($recognizedText, 'Could not recognize audio') ||
                        str_contains($recognizedText, 'Error processing audio') ||
                        str_contains($recognizedText, "'NoneType' object has no attribute 'split'") ||
                        str_contains($recognizedText, "Tidak ada suara terdeteksi") ||
                        str_contains($recognizedText, "No speech detected")
                    )) {
                        Log::error("Speech recognition failed: {$recognizedText}");
                    Log::error("Audio file details: " . (file_exists($tempFile) ? "Size: " . filesize($tempFile) . " bytes, Path: {$tempFile}" : "File not found"));
                    Log::error("Result: " . json_encode($result));
                        
                        // Set error message for display
                        if (str_contains($recognizedText, "'NoneType' object has no attribute 'split'")) {
                            // Provide a more user-friendly error message for the NoneType error
                            $error = ($language === 'id')
                                ? "Tidak dapat mengenali ucapan. Pastikan mikrofon Anda berfungsi dan rekam suara dengan jelas."
                                : "Could not recognize speech. Please ensure your microphone is working and record with a clear voice.";
                        } else {
                            $error = $recognizedText;
                        }
                        
                        // Clear recognition results when there's an error
                        $recognizedText = null;
                        $accuracy = null;
                        $feedback = null;
                    }
                } finally {
                    // Clean up temp file in all cases
                    if (file_exists($tempFile)) {
                @unlink($tempFile);
                        Log::info("Temporary file deleted: " . $tempFile);
                    }
                }
            } else {
                // No valid audio input provided - return the form without processing
                return view('speech.index', [
                    'language' => $language
                ]);
            }
            
            // Save the test result if authenticated and we have valid results
            if (Auth::check() && $recognizedText && !$error) {
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
            
            // Provide a user-friendly message for NoneType errors
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, "'NoneType' object has no attribute 'split'") !== false) {
                $error = ($language === 'id')
                    ? "Tidak dapat mengenali ucapan. Pastikan audio Anda jelas dan terdapat suara yang dapat dikenali."
                    : "Could not recognize speech. Please ensure your audio is clear and contains recognizable speech.";
            } else {
                $error = $errorMessage;
            }
        }
        
        return view('speech.index', [
            'recognizedText' => $error ? null : $recognizedText,
            'referenceText' => $referenceText,
            'accuracy' => $error ? null : $accuracy,
            'feedback' => $error ? null : $feedback,
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
            $language = $data['language'] ?? 'id';
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
            } else {
                // Default to webm for browser recordings if MIME type couldn't be detected
                Log::warning("API: MIME type not detected in audio data, defaulting to webm");
                $ext = '.webm';
            }
            
            $tempFile = tempnam(sys_get_temp_dir(), 'speech_');
            $tempFileWithExt = $tempFile . $ext;
            rename($tempFile, $tempFileWithExt);
            $tempFile = $tempFileWithExt;
            
            Log::info("API: Created temporary file with extension: " . $tempFile);
            
            try {
            $decodedAudio = base64_decode($audioData);
            
            // Validasi ukuran audio
            $minSize = 1024; // Minimal 1KB
            $audioSize = strlen($decodedAudio);
            if ($audioSize < $minSize) {
                throw new \Exception("Audio terlalu pendek atau kosong (ukuran: " . $audioSize . " bytes)");
            }
                
                // Check for audio format validity (more thorough validation)
                if ($ext === '.webm' && !$this->isValidWebm($decodedAudio)) {
                    Log::error("Invalid WebM format detected");
                    
                    // Return a user-friendly message based on language
                    $errorMsg = ($language === 'id') 
                        ? "Format audio tidak valid atau tidak ada suara yang terdeteksi. Pastikan mikrofon Anda berfungsi dan rekam suara dengan jelas."
                        : "Invalid audio format or no voice detected. Please ensure your microphone is working and record with a clear voice.";
                    
                    return response()->json(['error' => $errorMsg], 422);
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
                
                // Ensure file format is correct by checking file signature
                $fileInfo = mime_content_type($tempFile);
                Log::info("Detected MIME type from file: " . $fileInfo);
                
                // If the detected type doesn't match the extension, we can fix the extension
                if (strpos($fileInfo, 'webm') !== false && !str_ends_with($tempFile, '.webm')) {
                    $newTempFile = $tempFile . '.webm';
                    rename($tempFile, $newTempFile);
                    $tempFile = $newTempFile;
                    Log::info("Renamed file to match webm content type: " . $tempFile);
                }
            
            // Process the audio
            $result = $this->speechService->recognizeSpeech($tempFile, $referenceText, $language);
                $recognizedText = $result['recognized_text'] ?? '';
                $accuracy = $result['accuracy'] ?? 0;
                $feedback = $result['feedback'] ?? '';
            
                // Check for specific error patterns in the response
                if (is_string($recognizedText) && (
                    str_contains($recognizedText, 'Tidak dapat mengenali ucapan') || 
                    str_contains($recognizedText, 'Could not recognize audio') ||
                    str_contains($recognizedText, 'Error processing audio') ||
                    str_contains($recognizedText, "'NoneType' object has no attribute 'split'") ||
                    str_contains($recognizedText, "Tidak ada suara terdeteksi") ||
                    str_contains($recognizedText, "No speech detected")
                )) {
                    Log::error("API: Speech recognition failed: {$recognizedText}");
                    Log::error("API: Audio file details: " . (file_exists($tempFile) ? "Size: " . filesize($tempFile) . " bytes, Path: {$tempFile}" : "File not found"));
                    Log::error("API: Result: " . json_encode($result));
                    
                    // Provide a more user-friendly error message
                    $errorMsg = $recognizedText;
                    
                    // Handle the specific NoneType split error with a better message
                    if (str_contains($recognizedText, "'NoneType' object has no attribute 'split'")) {
                        $errorMsg = ($language === 'id') 
                            ? "Tidak dapat mengenali ucapan. Pastikan mikrofon Anda berfungsi dan rekam suara dengan jelas."
                            : "Could not recognize speech. Please ensure your microphone is working and record with a clear voice.";
                    }
                    
                    // Return the error with a 422 status code (Unprocessable Entity)
                    return response()->json(['error' => $errorMsg], 422);
                }
            
            return response()->json([
                'recognized_text' => $recognizedText,
                'reference_text' => $referenceText,
                'accuracy' => $accuracy,
                'feedback' => $feedback
            ]);
            } finally {
                // Clean up temp file in all cases
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                    Log::info("API: Temporary file deleted: " . $tempFile);
                }
            }
            
        } catch (\Exception $e) {
            Log::error("API direct speech recognition error: " . $e->getMessage());
            
            // Provide a user-friendly error message based on language
            $errorMsg = $e->getMessage();
            
            // Check for specific error patterns and provide better messages
            if (str_contains($errorMsg, "'NoneType' object has no attribute 'split'")) {
                $errorMsg = ($language ?? 'id') === 'id'
                    ? "Tidak dapat mengenali ucapan. Pastikan mikrofon Anda berfungsi dan rekam suara dengan jelas."
                    : "Could not recognize speech. Please ensure your microphone is working and record with a clear voice.";
            }
            
            return response()->json(['error' => $errorMsg], 500);
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

    /**
     * Performs basic validation on WebM format data
     * 
     * @param string $audioData The raw binary audio data
     * @return bool Whether the data appears to be valid WebM
     */
    private function isValidWebm($audioData)
    {
        // WebM files start with a specific header
        // Check for EBML header (first 4 bytes should be 0x1A, 0x45, 0xDF, 0xA3)
        if (strlen($audioData) < 4) {
            return false;
        }
        
        $header = substr($audioData, 0, 4);
        $ebmlHeader = chr(0x1A) . chr(0x45) . chr(0xDF) . chr(0xA3);
        
        // This is a basic check and might not catch all invalid WebM files
        // but should filter out completely invalid data
        if ($header !== $ebmlHeader) {
            Log::warning("WebM validation failed: invalid header");
            return false;
        }
        
        // Additional validation: check file size again
        if (strlen($audioData) < 5000) { // At least 5KB for a valid WebM with audio
            Log::warning("WebM validation failed: file too small, likely contains no audio");
            return false;
        }
        
        // Check for audio stream presence
        // We can't fully parse WebM here, but we can check for a common audio pattern
        // Look for "OpusHead" string which indicates Opus audio in WebM
        if (strpos($audioData, 'OpusHead') === false) {
            // Alternative check for vorbis audio
            if (strpos($audioData, 'vorbis') === false) {
                Log::warning("WebM validation failed: no detectable audio stream found");
                return false;
            }
        }
        
        // Additional check for very short recordings (possibly silent or cut off)
        // WebM has duration field but it's complex to parse, so we'll use a heuristic:
        // Check if the file has enough variation in byte values (silent audio has less variation)
        $sampleSize = min(8192, strlen($audioData)); // Sample up to 8KB of data
        $sample = substr($audioData, 4, $sampleSize); // Skip header
        $byteValues = array_count_values(str_split($sample));
        
        // If a few byte values dominate the sample, it might be silence
        // (Silent audio compresses to repeating patterns)
        $dominantByteCount = 0;
        foreach ($byteValues as $count) {
            if ($count > $sampleSize / 10) { // If any byte appears in >10% of the sample
                $dominantByteCount += $count;
            }
        }
        
        // If >70% of the sample is dominated by a few values, it's likely silence
        if ($dominantByteCount > $sampleSize * 0.7) {
            Log::warning("WebM validation failed: audio appears to be mostly silence");
            return false;
        }
        
        return true;
    }
}
