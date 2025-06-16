<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpeechService
{
    /**
     * Recognize speech from audio file
     *
     * @param UploadedFile|string $audioFile
     * @param string|null $referenceText
     * @param string $language
     * @return array
     */
    public function recognizeSpeech($audioFile, ?string $referenceText = '', string $language = 'en')
    {
        try {
            // Tingkatkan batas waktu eksekusi
            set_time_limit(120);
            
            // Process the audio file
            $tempWavFile = null;
            $shouldDeleteTempFile = false;
            
            // Ensure referenceText is never null
            $referenceText = $referenceText ?? '';
            
            if ($audioFile instanceof UploadedFile) {
                // Check file size before processing (8MB limit)
                $maxUploadSize = 8 * 1024 * 1024; // 8MB in bytes
                if ($audioFile->getSize() > $maxUploadSize) {
                    throw new \Exception("File terlalu besar. Maksimal ukuran file adalah 8MB.");
                }
                
                // Save uploaded file to temporary location
                $tempFile = $audioFile->getPathname();
                $tempWavFile = sys_get_temp_dir() . '/' . Str::random(16) . '.wav';
                $shouldDeleteTempFile = true;
            } else {
                // Assume $audioFile is a path to a file
                $tempFile = $audioFile;
                
                // Check file exists
                if (!file_exists($tempFile)) {
                    throw new \Exception("File audio tidak ditemukan: " . $tempFile);
                }
                
                // Check file size for non-uploaded files
                if (filesize($tempFile) > 8 * 1024 * 1024) {
                    throw new \Exception("File terlalu besar. Maksimal ukuran file adalah 8MB.");
                }
                
                $tempWavFile = sys_get_temp_dir() . '/' . Str::random(16) . '.wav';
                $shouldDeleteTempFile = true;
            }
            
            // Convert to WAV if needed
            $conversionSuccess = $this->convertAudioToWav($tempFile, $tempWavFile);
            
            if (!$conversionSuccess || !file_exists($tempWavFile) || filesize($tempWavFile) === 0) {
                throw new \Exception("Failed to process audio file or conversion failed");
            }
            
            // Ensure the converted WAV file is valid
            if (!$this->validateWavFile($tempWavFile)) {
                Log::error("Invalid WAV file produced: " . $tempWavFile);
                // Return predefined error to maintain consistent message
                return [
                    'recognized_text' => "Kesalahan dalam pengenalan ucapan: 'NoneType' object has no attribute 'split'",
                    'accuracy' => null,
                    'feedback' => null
                ];
            }
            
            // Check file size before sending
            $maxFileSize = 8 * 1024 * 1024; // 8MB max
            if (filesize($tempWavFile) > $maxFileSize) {
                Log::warning("Audio file too large: " . filesize($tempWavFile) . " bytes, recompressing...");
                
                // Recompress if too large
                $recompressedFile = sys_get_temp_dir() . '/' . Str::random(16) . '.wav';
                $this->recompressAudio($tempWavFile, $recompressedFile);
                
                if (file_exists($recompressedFile) && filesize($recompressedFile) > 0) {
                    if ($shouldDeleteTempFile && file_exists($tempWavFile)) {
                        @unlink($tempWavFile);
                    }
                    $tempWavFile = $recompressedFile;
                    $shouldDeleteTempFile = true;
                    
                    // If still too large after recompression
                    if (filesize($tempWavFile) > $maxFileSize) {
                        throw new \Exception("File audio terlalu besar setelah dikompresi. Silakan gunakan file audio yang lebih pendek.");
                    }
                }
            }
            
            // Use the Python speech recognition script
            $scriptPath = base_path('scripts/speech_recognition_service.py');
            
            // Verify script exists
            if (!file_exists($scriptPath)) {
                throw new \Exception("Script pengenalan suara tidak ditemukan: " . $scriptPath);
            }
            
            // Prepare the command - make sure to use python3 explicitly
            $pythonPath = $this->getPythonPath();
            
            $command = [
                $pythonPath,
                $scriptPath,
                '--audio',
                $tempWavFile,
                '--language',
                $language
            ];
            
            // Add reference text if provided
            if (!empty($referenceText)) {
                $command[] = '--reference';
                $command[] = $referenceText;
            }
            
            // Execute the Python script
            $process = new Process($command);
            $process->setTimeout(60); // 60 second timeout
            
            try {
                $process->run();
                
                // Clean up temporary files
                if ($shouldDeleteTempFile && file_exists($tempWavFile)) {
                    @unlink($tempWavFile);
                }
                
                // Check if the process was successful
                if (!$process->isSuccessful()) {
                    Log::error('Python speech recognition error: ' . $process->getErrorOutput());
                    
                    // Check for specific error conditions
                    $errorOutput = $process->getErrorOutput();
                    if (strpos($errorOutput, 'ModuleNotFoundError: No module named') !== false) {
                        throw new \Exception("Library Python tidak ditemukan. Silakan instal dengan menjalankan 'pip install SpeechRecognition'.");
                    } elseif (strpos($errorOutput, 'Could not find PyAudio') !== false) {
                        throw new \Exception("Library PyAudio tidak ditemukan. Ini diperlukan untuk memproses file audio.");
                    } elseif (strpos($errorOutput, "'NoneType' object has no attribute 'split'") !== false) {
                        // Return predefined error message to maintain consistent format
                        return [
                            'recognized_text' => "Kesalahan dalam pengenalan ucapan: 'NoneType' object has no attribute 'split'",
                            'accuracy' => null,
                            'feedback' => null
                        ];
                    } else {
                        throw new \Exception("Gagal mengenali suara: " . $errorOutput);
                    }
                }
            } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
                Log::error('Speech recognition process timed out: ' . $e->getMessage());
                throw new \Exception("Proses pengenalan suara terlalu lama. Coba dengan file audio yang lebih pendek.");
            } catch (\Symfony\Component\Process\Exception\ProcessFailedException $e) {
                Log::error('Speech recognition process failed: ' . $e->getMessage());
                throw new \Exception("Gagal menjalankan proses pengenalan suara. Periksa instalasi Python dan library pendukung.");
            }
            
            // Parse the JSON output from the Python script
            $output = $process->getOutput();
            
            if (empty(trim($output))) {
                Log::error('Empty output from Python script');
                throw new \Exception("Tidak ada output dari proses pengenalan suara. Periksa apakah Python dan library speech recognition terpasang dengan benar.");
            }
            
            $result = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse JSON from Python script: ' . json_last_error_msg());
                Log::error('Python script output: ' . $output);
                throw new \Exception("Gagal memproses hasil pengenalan suara. Output: " . substr($output, 0, 100));
            }
            
            // Handle any special error responses
            return $this->handleSpeechResponse($result);
            
        } catch (\Exception $e) {
            Log::error('Speech recognition error: ' . $e->getMessage());
            
            // Check if this is the specific error we want to preserve
            if (strpos($e->getMessage(), "'NoneType' object has no attribute 'split'") !== false) {
                return [
                    'recognized_text' => "Kesalahan dalam pengenalan ucapan: 'NoneType' object has no attribute 'split'",
                    'accuracy' => null,
                    'feedback' => null
                ];
            }
            
            return [
                'recognized_text' => "Error processing audio: " . $e->getMessage(),
                'accuracy' => 0,
                'feedback' => null
            ];
        }
    }
    
    /**
     * Convert audio to WAV format using FFmpeg
     *
     * @param string $inputFile
     * @param string $outputFile
     * @return bool
     */
    public function convertAudioToWav(string $inputFile, string $outputFile)
    {
        // Check if FFmpeg is installed
        try {
            $process = new Process(['ffmpeg', '-version']);
            $process->setTimeout(10); // 10 detik timeout untuk cek versi
            $process->run();
            
            if (!$process->isSuccessful()) {
                Log::error('FFmpeg not found: ' . $process->getErrorOutput());
                throw new \Exception('FFmpeg tidak tersedia di server. Fitur pengenalan suara tidak dapat digunakan.');
            }
            
            // Get audio information first to determine format
            $infoProcess = new Process([
                'ffmpeg',
                '-i',
                $inputFile,
                '-hide_banner'
            ]);
            $infoProcess->setTimeout(10);
            $infoProcess->run();
            
            // Log audio information for debugging
            $infoOutput = $infoProcess->getErrorOutput();
            Log::info('Audio file info: ' . $infoOutput);
            
            // Check if this is a WebM file based on content (not just extension)
            $isWebm = (strpos($infoOutput, 'matroska,webm') !== false || 
                       strpos($infoOutput, 'webm') !== false || 
                       strpos($inputFile, '.webm') !== false);
            
            if ($isWebm) {
                Log::info('Detected WebM format, using specialized conversion');
                return $this->convertWebmToWav($inputFile, $outputFile);
            }
            
            // Optimasi: Batasi audio ke 15 detik untuk mengurangi waktu pemrosesan
            $maxDuration = 15; // Maksimal durasi 15 detik
            
            // Convert audio to WAV dengan optimasi
            $process = new Process([
                'ffmpeg',
                '-y',             // Overwrite output file
                '-i',             // Input file
                $inputFile,
                '-acodec',        // Audio codec
                'pcm_s16le',      // PCM 16-bit little-endian (standard WAV)
                '-ar',            // Sample rate
                '16000',          // 16kHz (good for speech recognition)
                '-ac',            // Audio channels
                '1',              // Mono
                '-t',             // Duration limit
                (string)$maxDuration,
                '-f',             // Force format
                'wav',            // WAV format
                $outputFile
            ]);
            $process->setTimeout(30); // 30 detik timeout untuk konversi
            $process->run();
            
            if (!$process->isSuccessful()) {
                $error = $process->getErrorOutput();
                Log::error('FFmpeg conversion error: ' . $error);
                
                // Check for specific error conditions
                if (strpos($error, 'Invalid data found when processing input') !== false) {
                    throw new \Exception('Format audio tidak valid atau rusak. Gunakan format audio yang didukung (WAV, MP3, OGG, WEBM).');
                }
                
                if (strpos($error, 'Permission denied') !== false) {
                    throw new \Exception('Akses ditolak saat memproses file audio. Periksa izin folder penyimpanan.');
                }
                
                if (strpos($error, 'At least one output file must be specified') !== false) {
                    // Try with different conversion settings for webm
                    if (strpos($inputFile, '.webm') !== false || strpos($error, 'webm') !== false) {
                        return $this->convertWebmToWav($inputFile, $outputFile);
                    }
                }
                
                throw new \Exception('Gagal mengonversi file audio: ' . substr($error, 0, 100));
            }
            
            // Verify the output file exists and is not empty
            if (!file_exists($outputFile)) {
                Log::error("FFmpeg conversion failed: Output file does not exist");
                return false;
            }
            
            if (filesize($outputFile) === 0) {
                Log::error("FFmpeg conversion failed: Output file is empty");
                return false;
            }
            
            Log::info("Audio successfully converted to WAV: " . $outputFile . " (" . filesize($outputFile) . " bytes)");
            return true;
            
        } catch (\Exception $e) {
            Log::error('Audio conversion error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Special handler for WebM audio files
     *
     * @param string $inputFile
     * @param string $outputFile
     * @return bool
     */
    protected function convertWebmToWav(string $inputFile, string $outputFile)
    {
        try {
            // Get audio info using ffprobe
            $ffmpegPath = $this->getFfmpegPath();
            $ffprobePath = str_replace('ffmpeg', 'ffprobe', $ffmpegPath);
            $ffprobeCmd = [
                $ffprobePath,
                '-v', 'error',
                '-show_format',
                '-show_streams',
                '-of', 'json',
                $inputFile
            ];
            
            $audioInfo = json_decode(shell_exec(implode(' ', $ffprobeCmd)), true);
            Log::info("Audio info: " . json_encode($audioInfo));
            
            // Check if we have valid audio stream
            if (!isset($audioInfo['streams']) || empty($audioInfo['streams'])) {
                Log::error("No audio streams found in WebM file");
                throw new \Exception("Format audio tidak valid - tidak ada stream audio");
            }
            
            // Get audio stream info
            $audioStream = null;
            foreach ($audioInfo['streams'] as $stream) {
                if ($stream['codec_type'] === 'audio') {
                    $audioStream = $stream;
                    break;
                }
            }
            
            if (!$audioStream) {
                Log::error("No audio stream found in WebM file");
                throw new \Exception("Format audio tidak valid - tidak ada stream audio");
            }
            
            // Try multiple conversion methods
            $success = false;
            $methods = [
                // Method 1: Direct conversion with normalization
                [
                    '-y',
                    '-i', $inputFile,
                    '-vn',
                    '-acodec', 'pcm_s16le',
                    '-ar', '16000',
                    '-ac', '1',
                    '-f', 'wav',
                    '-af', 'loudnorm=I=-16:TP=-1.5:LRA=11',
                    $outputFile
                ],
                // Method 2: Two-step conversion
                [
                    '-y',
                    '-i', $inputFile,
                    '-vn',
                    '-acodec', 'pcm_s16le',
                    '-ar', '16000',
                    '-ac', '1',
                    '-f', 'wav',
                    $outputFile
                ],
                // Method 3: Force audio stream
                [
                    '-y',
                    '-i', $inputFile,
                    '-vn',
                    '-map', '0:a:0',
                    '-acodec', 'pcm_s16le',
                    '-ar', '16000',
                    '-ac', '1',
                    '-f', 'wav',
                    $outputFile
                ]
            ];
            
            foreach ($methods as $index => $method) {
                Log::info("Trying conversion method " . ($index + 1));
                
                $cmd = array_merge([$ffmpegPath], $method);
                $output = [];
                $returnVar = 0;
                
                exec(implode(' ', $cmd), $output, $returnVar);
                
                if ($returnVar === 0 && file_exists($outputFile) && filesize($outputFile) > 0) {
                    // Validate WAV file
                    if ($this->validateWavFile($outputFile)) {
                        Log::info("Conversion method " . ($index + 1) . " succeeded");
                        $success = true;
                        break;
                    }
                }
                
                Log::warning("Conversion method " . ($index + 1) . " failed");
            }
            
            if (!$success) {
                Log::error("All conversion methods failed");
                throw new \Exception("Gagal mengkonversi audio ke format WAV");
            }
            
            // Final validation
            if (!$this->validateWavFile($outputFile)) {
                Log::error("WAV validation failed after conversion");
                throw new \Exception("Format audio tidak valid setelah konversi");
            }
            
            Log::info("WebM converted successfully to WAV: " . $outputFile . " (" . filesize($outputFile) . " bytes)");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error converting WebM to WAV: " . $e->getMessage());
            throw new \Exception("Gagal mengkonversi audio: " . $e->getMessage());
        }
    }
    
    /**
     * Validate that a WAV file is properly formatted
     *
     * @param string $wavFile
     * @return bool
     */
    protected function validateWavFile(string $wavFile)
    {
        try {
            // Check if file exists and has content
            if (!file_exists($wavFile) || filesize($wavFile) === 0) {
                Log::error("WAV file does not exist or is empty");
                return false;
            }
            
            // Check file header
            $header = file_get_contents($wavFile, false, null, 0, 12);
            if (substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WAVE') {
                Log::error("Invalid WAV header");
                return false;
            }
            
            // Get audio info using ffprobe
            $ffmpegPath = $this->getFfmpegPath();
            $ffprobePath = str_replace('ffmpeg', 'ffprobe', $ffmpegPath);
            $ffprobeCmd = [
                $ffprobePath,
                '-v', 'error',
                '-show_format',
                '-show_streams',
                '-of', 'json',
                $wavFile
            ];
            
            $audioInfo = json_decode(shell_exec(implode(' ', $ffprobeCmd)), true);
            
            // Validate audio stream
            if (!isset($audioInfo['streams']) || empty($audioInfo['streams'])) {
                Log::error("No audio streams in WAV file");
                return false;
            }
            
            $audioStream = null;
            foreach ($audioInfo['streams'] as $stream) {
                if ($stream['codec_type'] === 'audio') {
                    $audioStream = $stream;
                    break;
                }
            }
            
            if (!$audioStream) {
                Log::error("No audio stream found in WAV file");
                return false;
            }
            
            // Validate audio parameters
            if ($audioStream['codec_name'] !== 'pcm_s16le' ||
                $audioStream['sample_rate'] !== '16000' ||
                $audioStream['channels'] !== 1) {
                Log::error("Invalid audio parameters in WAV file");
                return false;
            }
            
            // Check for silence
            $cmd = [
                $ffmpegPath,
                '-i', $wavFile,
                '-af', 'volumedetect',
                '-f', 'null',
                '-'
            ];
            
            $output = [];
            $returnVar = 0;
            exec(implode(' ', $cmd), $output, $returnVar);
            
            $output = implode("\n", $output);
            if (strpos($output, 'mean_volume: -inf') !== false) {
                Log::error("WAV file appears to be silent");
                return false;
            }
            
            Log::info("WAV file validation successful");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error validating WAV file: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Try a two-step conversion approach
     *
     * @param string $inputFile
     * @param string $outputFile
     * @return bool
     */
    protected function tryTwoStepConversion(string $inputFile, string $outputFile)
    {
        try {
            // First convert to raw PCM audio without any container
            $rawFile = sys_get_temp_dir() . '/' . Str::random(16) . '.pcm';
            
            $extractProcess = new Process([
                'ffmpeg',
                '-y',
                '-i', $inputFile,
                '-f', 's16le',
                '-acodec', 'pcm_s16le',
                '-ar', '16000',
                '-ac', '1',
                '-af', 'loudnorm=I=-16:TP=-1.5:LRA=11',
                $rawFile
            ]);
            $extractProcess->setTimeout(20);
            $extractProcess->run();
            
            if (!$extractProcess->isSuccessful() || !file_exists($rawFile) || filesize($rawFile) === 0) {
                Log::error('Raw audio extraction failed: ' . $extractProcess->getErrorOutput());
                return false;
            }
            
            // Then wrap it in a WAV container
            $wrapProcess = new Process([
                'ffmpeg',
                '-y',
                '-f', 's16le',
                '-ar', '16000',
                '-ac', '1',
                '-i', $rawFile,
                '-acodec', 'pcm_s16le',
                $outputFile
            ]);
            $wrapProcess->setTimeout(20);
            $wrapProcess->run();
            
            // Clean up temp file
            if (file_exists($rawFile)) {
                @unlink($rawFile);
            }
            
            if (!$wrapProcess->isSuccessful()) {
                Log::error('WAV wrapping failed: ' . $wrapProcess->getErrorOutput());
                return false;
            }
            
            if (file_exists($outputFile) && filesize($outputFile) > 0) {
                Log::info("Two-step conversion successful: " . $outputFile . " (" . filesize($outputFile) . " bytes)");
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Two-step conversion failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Normalize text for comparison
     *
     * @param string $text
     * @return string
     */
    protected function normalizeText(string $text)
    {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Remove punctuation
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Get language name from code
     *
     * @param string $language
     * @return string
     */
    protected function getLanguageName(string $language)
    {
        $languages = [
            'en' => 'English',
            'id' => 'Indonesian',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
            'ru' => 'Russian'
        ];
        
        return $languages[$language] ?? 'English';
    }

    /**
     * Recompress audio file to reduce size
     * 
     * @param string $inputFile
     * @param string $outputFile
     * @return bool
     */
    public function recompressAudio(string $inputFile, string $outputFile)
    {
        try {
            // Recompress using FFmpeg with lower bitrate
            $process = new Process([
                'ffmpeg',
                '-y',
                '-i',
                $inputFile,
                '-acodec',
                'pcm_s16le',
                '-ar',
                '8000',  // 8kHz sample rate
                '-ac',
                '1',     // Mono
                '-t',
                '10',    // Max 10 seconds
                $outputFile
            ]);
            $process->setTimeout(20);
            $process->run();
            
            if (!$process->isSuccessful()) {
                Log::error('Audio recompression error: ' . $process->getErrorOutput());
                return false;
            }
            
            return file_exists($outputFile) && filesize($outputFile) > 0;
        } catch (ProcessFailedException $e) {
            Log::error('Audio recompression failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the path to the Python executable
     * 
     * @return string
     */
    protected function getPythonPath()
    {
        // First, try Python 3.12 explicitly, lalu python3, baru python
        $pythonPaths = ['/usr/local/bin/python3.12', 'python3', 'python'];
        
        foreach ($pythonPaths as $path) {
            try {
                $process = new Process([$path, '--version']);
                $process->setTimeout(5);
                $process->run();
                
                if ($process->isSuccessful()) {
                    $version = $process->getOutput() ?: $process->getErrorOutput();
                    Log::info("SpeechService: Found Python at {$path}: {$version}");
                    return $path;
                }
            } catch (\Exception $e) {
                // Skip this path
                continue;
            }
        }
        
        // If Python 3 not found, fall back to 'python' which might be Python 3
        try {
            $process = new Process(['python', '--version']);
            $process->setTimeout(5);
            $process->run();
            
            if ($process->isSuccessful()) {
                $version = $process->getOutput() ?: $process->getErrorOutput();
                Log::info("SpeechService: Using default Python: {$version}");
                return 'python';
            }
        } catch (\Exception $e) {
            // Ignore error
        }
        
        // If nothing works, return the default 'python' and hope for the best
        Log::warning("SpeechService: Could not determine Python version, using default 'python'");
        return 'python';
    }

    /**
     * Handle the error response from speech recognition
     *
     * @param array $result
     * @return array
     */
    protected function handleSpeechResponse(array $result)
    {
        $recognizedText = $result['recognized_text'] ?? "";
        
        // If the response contains NoneType split error, return a consistent format
        if (strpos($recognizedText, "'NoneType' object has no attribute 'split'") !== false) {
            Log::warning("Received NoneType split error from Python, providing consistent error message");
            return [
                'recognized_text' => "Kesalahan dalam pengenalan ucapan: 'NoneType' object has no attribute 'split'",
                'accuracy' => null,
                'feedback' => null
            ];
        }
        
        return $result;
    }

    protected function getFfmpegPath()
    {
        // Common ffmpeg paths
        $ffmpegPaths = [
            '/opt/homebrew/bin/ffmpeg',  // Homebrew on Apple Silicon
            '/usr/local/bin/ffmpeg',     // Homebrew on Intel
            '/usr/bin/ffmpeg',           // System
            'ffmpeg'                     // PATH
        ];
        
        foreach ($ffmpegPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                Log::info("Found ffmpeg at: " . $path);
                return $path;
            }
        }
        
        // If not found in common paths, try which command
        $whichOutput = [];
        $returnVar = 0;
        exec('which ffmpeg', $whichOutput, $returnVar);
        
        if ($returnVar === 0 && !empty($whichOutput)) {
            $path = trim($whichOutput[0]);
            if (file_exists($path) && is_executable($path)) {
                Log::info("Found ffmpeg using which: " . $path);
                return $path;
            }
        }
        
        Log::error("ffmpeg not found in common paths or PATH");
        throw new \Exception("ffmpeg not found. Please install ffmpeg first.");
    }
} 