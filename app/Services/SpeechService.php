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
                
                // Check file size for non-uploaded files
                if (file_exists($tempFile) && filesize($tempFile) > 8 * 1024 * 1024) {
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
            
            // Prepare the command
            $command = [
                'python',
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
            
            return [
                'recognized_text' => $result['recognized_text'] ?? "Failed to transcribe audio. Please try again with clearer audio.",
                'accuracy' => $result['accuracy'] ?? null,
                'feedback' => $result['feedback'] ?? null
            ];
            
        } catch (\Exception $e) {
            Log::error('Speech recognition error: ' . $e->getMessage());
            return [
                'recognized_text' => "Error processing audio: " . $e->getMessage(),
                'accuracy' => null,
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
            
            // Optimasi: Batasi audio ke 15 detik untuk mengurangi waktu pemrosesan
            $maxDuration = 15; // Maksimal durasi 15 detik
            
            // Get audio information
            $infoProcess = new Process([
                'ffmpeg',
                '-i',
                $inputFile,
                '-hide_banner'
            ]);
            $infoProcess->setTimeout(10);
            $infoProcess->run();
            
            // Log audio information for debugging
            Log::info('Audio file info: ' . $infoProcess->getErrorOutput());
            
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
} 