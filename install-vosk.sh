#!/bin/bash

echo "Installing Vosk Speech Recognition..."

# Create directories
mkdir -p vosk/models
cd vosk

# Install Python dependencies if needed
echo "Installing Python dependencies..."
pip install vosk soundfile

# Download Indonesian model
echo "Downloading Indonesian language model..."
curl -L https://alphacephei.com/vosk/models/vosk-model-small-id-0.4.zip -o indonesian.zip
unzip indonesian.zip -d models/
mv models/vosk-model-small-id-0.4 models/indonesian
rm indonesian.zip

# Download English model
echo "Downloading English language model..."
curl -L https://alphacephei.com/vosk/models/vosk-model-small-en-us-0.15.zip -o english-us.zip
unzip english-us.zip -d models/
mv models/vosk-model-small-en-us-0.15 models/english-us
rm english-us.zip

# Create vosk-transcriber script
echo "Creating vosk-transcriber script..."
cat > ../vosk-transcriber <<'EOF'
#!/usr/bin/env python3

import sys
import os
import argparse
import json
import wave
import contextlib
from vosk import Model, KaldiRecognizer, SetLogLevel

def transcribe(audio_file, model_path):
    if not os.path.exists(model_path):
        print(f"Model path does not exist: {model_path}")
        return ""
        
    # Get audio duration
    with contextlib.closing(wave.open(audio_file, 'r')) as wf:
        frames = wf.getnframes()
        rate = wf.getframerate()
        duration = frames / float(rate)
        
        # Initialize model
        model = Model(model_path)
        rec = KaldiRecognizer(model, rate)
        rec.SetWords(True)
        
        # Process audio in chunks
        wf.rewind()
        result = ""
        
        while True:
            data = wf.readframes(4000)
            if len(data) == 0:
                break
            if rec.AcceptWaveform(data):
                part_result = json.loads(rec.Result())
                result += part_result.get('text', '') + " "
        
        # Get final result
        final = json.loads(rec.FinalResult())
        result += final.get('text', '')
        
        return result.strip()

if __name__ == "__main__":
    SetLogLevel(-1)  # Suppress logging
    
    parser = argparse.ArgumentParser(description='Transcribe audio using Vosk')
    parser.add_argument('--model-path', required=True, help='Path to Vosk model')
    parser.add_argument('--input', required=True, help='Input audio file (WAV format)')
    args = parser.parse_args()
    
    if not os.path.exists(args.input):
        print("Input file does not exist")
        sys.exit(1)
        
    text = transcribe(args.input, args.model_path)
    print(json.dumps({"text": text}))
EOF

# Make script executable
chmod +x ../vosk-transcriber

echo "Installation completed!" 