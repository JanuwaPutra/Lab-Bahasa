#!/usr/bin/env python3
import speech_recognition as sr
import sys
import json
import os
import argparse
import jiwer
import tempfile
from difflib import SequenceMatcher

def recognize_speech_from_file(audio_file, language='id'):
    """
    Recognize speech from uploaded audio file
    """
    recognizer = sr.Recognizer()
    
    try:
        # Debug information about the audio file
        print(f"Processing audio file: {audio_file}", file=sys.stderr)
        print(f"File exists: {os.path.exists(audio_file)}, Size: {os.path.getsize(audio_file) if os.path.exists(audio_file) else 'N/A'} bytes", file=sys.stderr)
        
        with sr.AudioFile(audio_file) as source:
            # Adjust recognition for ambient noise
            recognizer.adjust_for_ambient_noise(source, duration=0.5)
            
            # Increase energy threshold for better detection
            recognizer.energy_threshold = 300
            
            try:
                # Record with dynamic energy adjustment
                print(f"Recording audio data from file", file=sys.stderr)
                audio_data = recognizer.record(source)
                print(f"Audio data recorded successfully, duration: {audio_data.duration if hasattr(audio_data, 'duration') else 'unknown'} seconds", file=sys.stderr)
            except Exception as e:
                print(f"Error recording audio data: {str(e)}", file=sys.stderr)
                return f"Error reading audio: {str(e)}"
            
            # Map language codes
            lang_codes = {
                "id": "id-ID",
                "en": "en-US",
                "en-GB": "en-GB",
                "ja": "ja-JP",
                "ko": "ko-KR",
                "ar": "ar-EG",
                "es": "es-ES",
                "zh": "zh-CN",
                "fr": "fr-FR",
                "de": "de-DE",
                "ru": "ru-RU"
            }
            
            # For English, try multiple variants
            if language == "en":
                try:
                    print(f"Attempting recognition with en-US", file=sys.stderr)
                    result = recognizer.recognize_google(audio_data, language="en-US")
                    print(f"Recognition result: {result}", file=sys.stderr)
                    return result
                except sr.UnknownValueError:
                    print(f"Could not understand audio (en-US)", file=sys.stderr)
                    try:
                        print(f"Attempting recognition with en-GB", file=sys.stderr)
                        result = recognizer.recognize_google(audio_data, language="en-GB")
                        print(f"Recognition result: {result}", file=sys.stderr)
                        return result
                    except sr.UnknownValueError:
                        print(f"Could not understand audio (en-GB)", file=sys.stderr)
                        try:
                            print(f"Attempting recognition with en", file=sys.stderr)
                            result = recognizer.recognize_google(audio_data, language="en")
                            print(f"Recognition result: {result}", file=sys.stderr)
                            return result
                        except sr.UnknownValueError:
                            print("All recognition attempts failed - speech not understood", file=sys.stderr)
                            return "Could not recognize audio - speech unclear or too quiet"
                        except sr.RequestError as e:
                            print(f"Request error: {str(e)}", file=sys.stderr)
                            return f"Could not request results from Google Speech Recognition service: {str(e)}"
                except sr.RequestError as e:
                    print(f"Request error: {str(e)}", file=sys.stderr)
                    return f"Could not request results from Google Speech Recognition service: {str(e)}"
                except Exception as e:
                    print(f"Other error: {str(e)}", file=sys.stderr)
                    return f"Error in speech recognition: {str(e)}"
            else:
                # Use the appropriate language code
                lang_code = lang_codes.get(language, "id-ID")
                try:
                    print(f"Attempting recognition with {lang_code}", file=sys.stderr)
                    result = recognizer.recognize_google(audio_data, language=lang_code)
                    print(f"Recognition result: {result}", file=sys.stderr)
                    return result
                except sr.UnknownValueError:
                    print(f"Could not understand audio ({lang_code})", file=sys.stderr)
                    error_msg = "Tidak dapat mengenali ucapan - suara tidak jelas atau terlalu pelan" if language == "id" else "Could not recognize speech - unclear or too quiet"
                    return error_msg
                except sr.RequestError as e:
                    print(f"Request error: {str(e)}", file=sys.stderr)
                    error_msg = f"Tidak dapat terhubung ke layanan Google Speech: {str(e)}" if language == "id" else f"Could not request results from Google Speech Recognition service: {str(e)}"
                    return error_msg
                except Exception as e:
                    print(f"Recognition failed with other error: {str(e)}", file=sys.stderr)
                    error_msg = f"Kesalahan dalam pengenalan ucapan: {str(e)}" if language == "id" else f"Error in speech recognition: {str(e)}"
                    return error_msg
                
    except Exception as e:
        print(f"Error in audio processing: {str(e)}", file=sys.stderr)
        error_msg = f"Kesalahan dalam pemrosesan audio: {str(e)}" if language == "id" else f"Error in audio processing: {str(e)}"
        return error_msg

def calculate_pronunciation_accuracy(recognized_text, reference_text):
    """
    Calculate how accurately the recognized speech matches the reference text
    """
    # Handle None values and error messages
    if not recognized_text or not reference_text or recognized_text.startswith("Error:") or recognized_text.startswith("Tidak dapat"):
        print(f"Invalid text for accuracy calculation. Recognized: {recognized_text}", file=sys.stderr)
        return 0
    
    # Remove capitalization and punctuation for comparison
    recognized_clean = ''.join(e.lower() for e in recognized_text if e.isalnum() or e.isspace())
    reference_clean = ''.join(e.lower() for e in reference_text if e.isalnum() or e.isspace())
    
    # Calculate Word Error Rate (WER)
    try:
        wer = jiwer.wer(reference_clean, recognized_clean)
        accuracy = max(0, 100 - wer * 100)
        return round(accuracy, 1)
    except:
        # Fallback to sequence matcher
        matcher = SequenceMatcher(None, recognized_clean, reference_clean)
        return round(matcher.ratio() * 100, 1)

def generate_pronunciation_feedback(recognized_text, reference_text, accuracy, language='id'):
    """
    Generate feedback based on pronunciation accuracy
    """
    # Critical fix for NoneType split error - ensure we have valid strings
    if recognized_text is None:
        recognized_text = ""
        print("Warning: recognized_text was None", file=sys.stderr)
    
    if reference_text is None:
        reference_text = ""
        print("Warning: reference_text was None", file=sys.stderr)
        
    if accuracy is None:
        accuracy = 0
        print("Warning: accuracy was None", file=sys.stderr)
    
    # Ensure we're working with strings
    recognized_text = str(recognized_text)
    reference_text = str(reference_text)
    
    # Basic feedback based on accuracy
    if accuracy > 90:
        feedback = "Sangat bagus! Pengucapan Anda sangat jelas." if language == 'id' else "Excellent! Your pronunciation is very clear."
    elif accuracy > 75:
        feedback = "Bagus! Pengucapan Anda cukup jelas." if language == 'id' else "Good! Your pronunciation is fairly clear."
    elif accuracy > 50:
        feedback = "Cukup baik. Perhatikan pengucapan beberapa kata." if language == 'id' else "Fairly good. Pay attention to the pronunciation of some words."
    else:
        feedback = "Perlu latihan lebih. Cobalah bicara lebih jelas dan perlahan." if language == 'id' else "Needs more practice. Try speaking more clearly and slowly."
    
    # Only attempt word comparison if we have valid texts with content
    if accuracy < 90 and recognized_text.strip() and reference_text.strip():
        try:
            # Split texts into words, with additional safety checks
            recognized_words = recognized_text.lower().split() if recognized_text else []
            reference_words = reference_text.lower().split() if reference_text else []
            
            # Verify we actually have words after splitting
            if not recognized_words or not reference_words:
                print("No words after splitting, skipping comparison", file=sys.stderr)
                return feedback
            
            # Find words that don't match
            mismatched_words = []
            min_len = min(len(recognized_words), len(reference_words))
            
            for i in range(min_len):
                if i < len(recognized_words) and i < len(reference_words):
                    if recognized_words[i] != reference_words[i]:
                        mismatched_words.append(reference_words[i])
            
            if mismatched_words:
                word_list = ", ".join(mismatched_words[:3])
                if language == 'id':
                    feedback += f" Perhatikan pengucapan kata-kata berikut: {word_list}."
                else:
                    feedback += f" Pay attention to the pronunciation of these words: {word_list}."
                    
        except Exception as e:
            print(f"Error in word comparison: {str(e)}", file=sys.stderr)
            # Just return the basic feedback if word comparison fails
    
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
        
        # Ensure recognized_text is never None
        if recognized_text is None:
            print("Warning: Recognition returned None", file=sys.stderr)
            recognized_text = ""
        
        # Check if recognition returned an error message
        if recognized_text.startswith("Error:") or recognized_text.startswith("Tidak dapat") or not recognized_text:
            print(f"Recognition failed or returned empty result: {recognized_text}", file=sys.stderr)
            accuracy = 0
            feedback = "Tidak dapat mengevaluasi ucapan" if language == 'id' else "Cannot evaluate pronunciation"
            return recognized_text, accuracy, feedback
        
        # Calculate accuracy if reference text was provided
        accuracy = 0
        if reference_text:
            accuracy = calculate_pronunciation_accuracy(recognized_text, reference_text)
            feedback = generate_pronunciation_feedback(recognized_text, reference_text, accuracy, language)
        else:
            # If no reference text
            feedback = "Tidak ada teks referensi untuk evaluasi" if language == 'id' else "No reference text for evaluation"
        
        return recognized_text, accuracy, feedback
        
    except Exception as e:
        print(f"Error in speech recognition process: {str(e)}", file=sys.stderr)
        error_msg = f"Kesalahan dalam proses pengenalan suara: {str(e)}" if language == 'id' else f"Error in speech recognition process: {str(e)}"
        return error_msg, 0, None

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Speech recognition service')
    parser.add_argument('--audio', required=False, help='Path to audio file')
    parser.add_argument('--reference', default='', help='Reference text')
    parser.add_argument('--language', default='id', help='Language code')
    parser.add_argument('--version', action='store_true', help='Show version and dependency info')
    
    args = parser.parse_args()
    
    try:
        if args.version:
            # Print version info for diagnostic purposes
            import platform
            print(f"Speech Recognition Service v1.0")
            print(f"Python: {platform.python_version()}")
            print(f"Platform: {platform.platform()}")
            
            # Check dependencies
            import pkg_resources
            dependencies = ['speech_recognition', 'jiwer']
            for dep in dependencies:
                try:
                    dist = pkg_resources.get_distribution(dep)
                    print(f"{dep}: {dist.version}")
                except pkg_resources.DistributionNotFound:
                    print(f"{dep}: NOT FOUND")
            
            # Check if we can access the speech recognition API
            try:
                recognizer = sr.Recognizer()
                print("SpeechRecognition initialized: OK")
            except Exception as e:
                print(f"SpeechRecognition error: {e}")
                
            sys.exit(0)
        
        # Check if audio argument is provided for normal operation
        if not args.audio:
            raise ValueError("--audio argument is required")
            
        # Check if file exists and is valid
        if not os.path.exists(args.audio):
            raise FileNotFoundError(f"File tidak ditemukan - {args.audio}")
        
        if os.path.getsize(args.audio) == 0:
            raise ValueError("File audio kosong")
            
        # Process the speech
        recognized_text, accuracy, feedback = recognize_speech(args.audio, args.reference, args.language)
        
        # Prepare JSON response
        response = {
            'recognized_text': recognized_text,
            'accuracy': accuracy,
            'feedback': feedback
        }
        
        # Output as JSON
        print(json.dumps(response))
        
    except Exception as e:
        # Return error as JSON
        error_response = {
            'recognized_text': f"Error: {str(e)}",
            'accuracy': None,
            'feedback': None
        }
        print(json.dumps(error_response)) 