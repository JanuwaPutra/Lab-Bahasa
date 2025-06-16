#!/usr/bin/env python3
import sys
import json
import os
import argparse
import tempfile
import subprocess
from difflib import SequenceMatcher

def check_ffmpeg():
    """
    Check if ffmpeg is available and return its path
    """
    # Common ffmpeg paths
    ffmpeg_paths = [
        '/opt/homebrew/bin/ffmpeg',  # Homebrew on Apple Silicon
        '/usr/local/bin/ffmpeg',     # Homebrew on Intel
        '/usr/bin/ffmpeg',           # System
        'ffmpeg'                     # PATH
    ]
    
    for path in ffmpeg_paths:
        try:
            result = subprocess.run([path, '-version'], capture_output=True, text=True)
            if result.returncode == 0:
                print(f"Found ffmpeg at: {path}", file=sys.stderr)
                return path
        except Exception:
            continue
    
    print("Error: ffmpeg not found. Please install ffmpeg first.", file=sys.stderr)
    return None

# Get ffmpeg path at module level
FFMPEG_PATH = check_ffmpeg()

try:
    import speech_recognition as sr
    SPEECH_RECOGNITION_AVAILABLE = True
except ImportError:
    SPEECH_RECOGNITION_AVAILABLE = False
    print("Warning: speech_recognition module not available", file=sys.stderr)

try:
    import jiwer
    JIWER_AVAILABLE = True
except ImportError:
    JIWER_AVAILABLE = False
    print("Warning: jiwer module not available", file=sys.stderr)

try:
    import wave
    import numpy as np
    import soundfile as sf
    AUDIO_PROCESSING_AVAILABLE = True
except ImportError:
    AUDIO_PROCESSING_AVAILABLE = False
    print("Warning: audio processing modules not available", file=sys.stderr)

def preprocess_audio(input_file, output_file):
    """
    Preprocess audio file to ensure it's in the correct format
    """
    if not FFMPEG_PATH:
        print("Error: ffmpeg not available for preprocessing", file=sys.stderr)
        return False
        
    try:
        print(f"Preprocessing audio file: {input_file}", file=sys.stderr)
        
        # Read audio file
        data, samplerate = sf.read(input_file)
        print(f"Original audio: samplerate={samplerate}, shape={data.shape}", file=sys.stderr)
        
        # Convert to mono if stereo
        if len(data.shape) > 1:
            data = np.mean(data, axis=1)
            print("Converted stereo to mono", file=sys.stderr)
        
        # Resample to 16kHz if needed
        if samplerate != 16000:
            print(f"Resampling from {samplerate}Hz to 16000Hz", file=sys.stderr)
            from scipy import signal
            samples = len(data)
            new_samples = int(samples * 16000 / samplerate)
            data = signal.resample(data, new_samples)
            samplerate = 16000
        
        # Normalize audio
        data = data / np.max(np.abs(data))
        
        # Save as WAV
        sf.write(output_file, data, samplerate, subtype='PCM_16')
        print(f"Saved preprocessed audio to: {output_file}", file=sys.stderr)
        
        return True
    except Exception as e:
        print(f"Error preprocessing audio: {str(e)}", file=sys.stderr)
        return False

def recognize_speech_from_file(audio_file, language='id'):
    """
    Recognize speech from uploaded audio file
    """
    if not FFMPEG_PATH:
        return "Error: ffmpeg not found. Please install ffmpeg first."
        
    if not SPEECH_RECOGNITION_AVAILABLE:
        return "Error: speech_recognition module not installed. Install with 'pip install SpeechRecognition'"
    
    recognizer = sr.Recognizer()
    
    try:
        # Debug information about the audio file
        print(f"Processing audio file: {audio_file}", file=sys.stderr)
        print(f"File exists: {os.path.exists(audio_file)}, Size: {os.path.getsize(audio_file) if os.path.exists(audio_file) else 'N/A'} bytes", file=sys.stderr)
        
        # Convert to WAV first with enhanced settings
        temp_wav = tempfile.NamedTemporaryFile(suffix='.wav', delete=False)
        temp_wav.close()
        
        convert_cmd = [
            FFMPEG_PATH,
            '-y',
            '-i', audio_file,
            '-vn',
            '-acodec', 'pcm_s16le',
            '-ar', '16000',
            '-ac', '1',
            '-f', 'wav',
            '-af', 'loudnorm=I=-16:TP=-1.5:LRA=11',  # Normalize audio
            temp_wav.name
        ]
        
        print(f"Converting audio with command: {' '.join(convert_cmd)}", file=sys.stderr)
        result = subprocess.run(convert_cmd, capture_output=True, text=True)
        
        if result.returncode != 0:
            print(f"FFmpeg error: {result.stderr}", file=sys.stderr)
            return "Tidak dapat mengenali ucapan - format audio tidak valid" if language == "id" else "Could not recognize speech - invalid audio format"
        
        if not os.path.exists(temp_wav.name) or os.path.getsize(temp_wav.name) == 0:
            print("Audio conversion failed", file=sys.stderr)
            return "Tidak dapat mengenali ucapan - format audio tidak valid" if language == "id" else "Could not recognize speech - invalid audio format"
        
        print(f"Successfully converted audio to WAV: {temp_wav.name}", file=sys.stderr)
        
        # Validate WAV file
        try:
            with wave.open(temp_wav.name, 'rb') as wav_file:
                # Check audio parameters
                channels = wav_file.getnchannels()
                sample_width = wav_file.getsampwidth()
                frame_rate = wav_file.getframerate()
                n_frames = wav_file.getnframes()
                duration = n_frames / float(frame_rate)
                
                print(f"WAV file info: channels={channels}, sample_width={sample_width}, frame_rate={frame_rate}, duration={duration}s", file=sys.stderr)
                
                if duration < 0.5:
                    return "Tidak dapat mengenali ucapan - audio terlalu pendek (minimal 0.5 detik)" if language == "id" else "Could not recognize speech - audio too short (minimum 0.5 seconds)"
                
                if duration > 30:
                    return "Tidak dapat mengenali ucapan - audio terlalu panjang (maksimal 30 detik)" if language == "id" else "Could not recognize speech - audio too long (maximum 30 seconds)"
                
                # Read audio data and check for silence
                audio_data = wav_file.readframes(n_frames)
                audio_array = np.frombuffer(audio_data, dtype=np.int16)
                rms = np.sqrt(np.mean(np.square(audio_array.astype(np.float32))))
                
                print(f"Audio RMS level: {rms}", file=sys.stderr)
                if rms < 100:  # Threshold for silence
                    return "Tidak dapat mengenali ucapan - suara terlalu pelan" if language == "id" else "Could not recognize speech - audio too quiet"
                
        except Exception as e:
            print(f"Error validating WAV file: {str(e)}", file=sys.stderr)
            return "Tidak dapat mengenali ucapan - format audio tidak valid" if language == "id" else "Could not recognize speech - invalid audio format"
        
        # Try to recognize speech
        try:
            print(f"Opening audio file for recognition: {temp_wav.name}", file=sys.stderr)
            with sr.AudioFile(temp_wav.name) as source:
                # Adjust recognition for ambient noise
                print("Adjusting for ambient noise...", file=sys.stderr)
                try:
                    recognizer.adjust_for_ambient_noise(source, duration=1.0)
                    print("Successfully adjusted for ambient noise", file=sys.stderr)
                except Exception as e:
                    print(f"Warning: Could not adjust for ambient noise: {str(e)}", file=sys.stderr)
                
                # Record audio
                print("Recording audio...", file=sys.stderr)
                audio = recognizer.record(source)
                print("Successfully recorded audio", file=sys.stderr)
                
                # Recognize speech
                print(f"Recognizing speech with language: {language}", file=sys.stderr)
                text = recognizer.recognize_google(audio, language=language)
                print(f"Recognition result: {text}", file=sys.stderr)
                
                return text
                
        except sr.UnknownValueError:
            print("Speech recognition could not understand audio", file=sys.stderr)
            return "Tidak dapat mengenali ucapan - suara tidak jelas" if language == "id" else "Could not recognize speech - unclear audio"
        except sr.RequestError as e:
            print(f"Speech recognition service error: {str(e)}", file=sys.stderr)
            return "Tidak dapat mengenali ucapan - layanan tidak tersedia" if language == "id" else "Could not recognize speech - service unavailable"
        except Exception as e:
            print(f"Error during speech recognition: {str(e)}", file=sys.stderr)
            return "Tidak dapat mengenali ucapan - format audio tidak valid" if language == "id" else "Could not recognize speech - invalid audio format"
            
    except Exception as e:
        print(f"Unexpected error: {str(e)}", file=sys.stderr)
        return "Tidak dapat mengenali ucapan - format audio tidak valid" if language == "id" else "Could not recognize speech - invalid audio format"
    finally:
        # Clean up temporary files
        try:
            if os.path.exists(temp_wav.name):
                os.unlink(temp_wav.name)
                print(f"Temporary file deleted: {temp_wav.name}", file=sys.stderr)
        except Exception as e:
            print(f"Error cleaning up temporary file: {str(e)}", file=sys.stderr)

def calculate_pronunciation_accuracy(recognized_text, reference_text):
    """
    Calculate how accurately the recognized speech matches the reference text
    """
    if not recognized_text or not reference_text:
        return 0
        
    try:
        # Remove capitalization and punctuation for comparison
        recognized_clean = ''.join(e.lower() for e in recognized_text if e.isalnum() or e.isspace())
        reference_clean = ''.join(e.lower() for e in reference_text if e.isalnum() or e.isspace())
        
        # Calculate Word Error Rate (WER)
        if JIWER_AVAILABLE:
            wer = jiwer.wer(reference_clean, recognized_clean)
            accuracy = max(0, 100 - wer * 100)
            return round(accuracy, 1)
        else:
            # Fallback to sequence matcher if jiwer not available
            matcher = SequenceMatcher(None, recognized_clean, reference_clean)
            accuracy = round(matcher.ratio() * 100, 1)
            return accuracy
            
    except Exception as e:
        print(f"Error calculating accuracy: {str(e)}", file=sys.stderr)
        return 0

def generate_pronunciation_feedback(recognized_text, reference_text, accuracy, language='id'):
    """
    Generate feedback based on pronunciation accuracy
    """
    if not recognized_text or not reference_text:
        return "Tidak dapat mengevaluasi ucapan" if language == 'id' else "Cannot evaluate pronunciation"
        
    feedback = ""
    
    if accuracy > 90:
        feedback = "Sangat bagus! Pengucapan Anda sangat jelas." if language == 'id' else "Excellent! Your pronunciation is very clear."
    elif accuracy > 75:
        feedback = "Bagus! Pengucapan Anda cukup jelas." if language == 'id' else "Good! Your pronunciation is fairly clear."
    elif accuracy > 50:
        feedback = "Cukup baik. Perhatikan pengucapan beberapa kata." if language == 'id' else "Fairly good. Pay attention to the pronunciation of some words."
    else:
        feedback = "Perlu latihan lebih. Cobalah bicara lebih jelas dan perlahan." if language == 'id' else "Needs more practice. Try speaking more clearly and slowly."
    
    # Identify specific words that might be mispronounced
    if accuracy < 90:
        recognized_words = recognized_text.lower().split()
        reference_words = reference_text.lower().split()
        
        # Find words that don't match
        if len(recognized_words) > 0 and len(reference_words) > 0:
            mismatched_words = []
            
            min_len = min(len(recognized_words), len(reference_words))
            for i in range(min_len):
                if recognized_words[i] != reference_words[i]:
                    mismatched_words.append(reference_words[i])
            
            if mismatched_words:
                word_list = ", ".join(mismatched_words[:3])  # Limit to first 3 words
                if language == 'id':
                    feedback += f" Perhatikan pengucapan kata-kata berikut: {word_list}."
                else:
                    feedback += f" Pay attention to the pronunciation of these words: {word_list}."
    
    return feedback

def recognize_speech(audio_file, reference_text='', language='id'):
    """
    Main function to handle speech recognition and evaluation
    """
    # Ensure reference_text is never None
    reference_text = reference_text or ''
    
    try:
        # Recognize speech from the audio file
        recognized_text = recognize_speech_from_file(audio_file, language)
        
        # Check if recognition returned an error message
        if isinstance(recognized_text, str) and (
            recognized_text.startswith("Error:") or 
            recognized_text.startswith("Tidak dapat") or 
            "tidak terdeteksi" in recognized_text.lower() or
            "no speech detected" in recognized_text.lower() or
            not recognized_text
        ):
            print(f"Recognition failed or returned error message: {recognized_text}", file=sys.stderr)
            accuracy = 0
            feedback = "Tidak dapat mengevaluasi ucapan" if language == 'id' else "Cannot evaluate pronunciation"
            return recognized_text, accuracy, feedback
        
        # Calculate accuracy if reference text was provided
        accuracy = 0
        if reference_text:
            try:
                accuracy = calculate_pronunciation_accuracy(recognized_text, reference_text)
                feedback = generate_pronunciation_feedback(recognized_text, reference_text, accuracy, language)
            except Exception as e:
                print(f"Error calculating accuracy: {str(e)}", file=sys.stderr)
                feedback = "Tidak dapat mengevaluasi ucapan" if language == 'id' else "Cannot evaluate pronunciation"
        else:
            feedback = "Tidak ada teks referensi untuk evaluasi" if language == 'id' else "No reference text for evaluation"
        
        return recognized_text, accuracy, feedback
            
    except Exception as e:
        print(f"Error in speech recognition: {str(e)}", file=sys.stderr)
        error_msg = f"Kesalahan dalam pengenalan ucapan: {str(e)}" if language == "id" else f"Error in speech recognition: {str(e)}"
        return error_msg, 0, "Error"

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Speech Recognition Service')
    parser.add_argument('--audio', required=True, help='Path to audio file')
    parser.add_argument('--reference', help='Reference text for accuracy calculation')
    parser.add_argument('--language', default='id', help='Language code (default: id)')
    parser.add_argument('--version', action='store_true', help='Show version information')
    
    args = parser.parse_args()
    
    if args.version:
        print("Speech Recognition Service v1.0")
        sys.exit(0)
    
    result = recognize_speech(args.audio, args.reference, args.language)
    print(json.dumps({
        'recognized_text': result[0],
        'accuracy': result[1],
        'feedback': result[2]
    })) 