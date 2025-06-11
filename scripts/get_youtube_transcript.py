#!/usr/bin/env python3
import sys
import json
import requests
import re
import traceback

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
                    print(f"Found transcript in {language}", file=sys.stderr)
                except Exception as e:
                    print(f"No transcript in {language}, trying English: {str(e)}", file=sys.stderr)
                    # If not found, try to get any transcript and translate it
                    try:
                        transcript = transcript_list.find_transcript(['en'])
                        transcript = transcript.translate(language)
                        print(f"Using translated transcript from English to {language}", file=sys.stderr)
                    except Exception as e:
                        print(f"Translation failed, using any available transcript: {str(e)}", file=sys.stderr)
                        # If still not found, get the first available transcript
                        transcript = list(transcript_list)[0]
                
                # Get the transcript data
                transcript_data = transcript.fetch()
                
                # Format the transcript
                formatted_transcript = ""
                for entry in transcript_data:
                    formatted_transcript += f"{entry['text']} "
                
                if formatted_transcript.strip():
                    return formatted_transcript
                else:
                    print("Empty transcript from YouTubeTranscriptApi", file=sys.stderr)
                    # Fall back to method 2
                    pass
                
            except Exception as e:
                print(f"YouTube Transcript API error: {str(e)}", file=sys.stderr)
                traceback.print_exc(file=sys.stderr)
                # Fall back to method 2
                pass
                
        except ImportError:
            print("youtube_transcript_api not installed, falling back to alternative method", file=sys.stderr)
            # Fall back to method 2
            pass
        
        print("Trying alternative method with direct requests", file=sys.stderr)
        
        # Method 2: Using a more direct approach with requests
        # Get the YouTube page content
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        response = requests.get(f"https://www.youtube.com/watch?v={video_id}", headers=headers)
        
        if response.status_code != 200:
            return f"Error: Could not access YouTube video (status code: {response.status_code})"
        
        # Look for captions in the page source
        html = response.text
        
        # Try to find the caption track
        caption_url_match = re.search(r'"captionTracks":\[(.*?)\]', html)
        
        if not caption_url_match:
            print("No caption tracks found in HTML", file=sys.stderr)
            # Try another pattern
            caption_url_match = re.search(r'"captions":\s*{.*?"playerCaptionsTracklistRenderer":\s*{.*?"captionTracks":\s*\[(.*?)\]', html, re.DOTALL)
            if not caption_url_match:
                return "Error: No captions found for this video"
        
        caption_data = caption_url_match.group(1)
        print(f"Found caption data: {caption_data[:100]}...", file=sys.stderr)
        
        # Find the caption URL
        base_url_match = re.search(r'"baseUrl":"(.*?)"', caption_data)
        
        if not base_url_match:
            return "Error: Could not extract caption URL"
        
        # Get the caption URL and replace escaped characters
        caption_url = base_url_match.group(1).replace('\\u0026', '&')
        print(f"Caption URL: {caption_url[:100]}...", file=sys.stderr)
        
        # Add language parameter if specified
        if language != 'en':
            caption_url += f"&tlang={language}"
        
        # Get the captions
        caption_response = requests.get(caption_url)
        
        if caption_response.status_code != 200:
            return f"Error: Could not access captions (status code: {caption_response.status_code})"
        
        # Extract text from XML
        caption_xml = caption_response.text
        print(f"Caption XML length: {len(caption_xml)}", file=sys.stderr)
        
        # Simple parsing to extract text (not perfect but works for most cases)
        text_parts = re.findall(r'<text[^>]*>(.*?)</text>', caption_xml)
        
        if not text_parts:
            print("No text parts found in XML", file=sys.stderr)
            print(f"First 500 chars of XML: {caption_xml[:500]}", file=sys.stderr)
            return "Error: Could not parse captions"
        
        print(f"Found {len(text_parts)} text segments", file=sys.stderr)
        
        # Join all text parts
        transcript = " ".join(text_parts)
        
        # Remove HTML entities
        transcript = re.sub(r'&amp;', '&', transcript)
        transcript = re.sub(r'&lt;', '<', transcript)
        transcript = re.sub(r'&gt;', '>', transcript)
        transcript = re.sub(r'&quot;', '"', transcript)
        transcript = re.sub(r'&#39;', "'", transcript)
        
        if not transcript.strip():
            return "Error: Empty transcript after processing"
        
        return transcript
        
    except Exception as e:
        print(f"Exception in get_transcript: {str(e)}", file=sys.stderr)
        traceback.print_exc(file=sys.stderr)
        return f"Error: {str(e)}"

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Error: Video ID is required")
        sys.exit(1)
    
    video_id = sys.argv[1]
    language = sys.argv[2] if len(sys.argv) > 2 else 'en'
    
    print(f"Processing video {video_id} in language {language}", file=sys.stderr)
    
    result = get_transcript(video_id, language)
    print(result)