<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Speaking Test') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-bold mb-6">Language Speaking Test</h1>
                    
                    <p class="mb-6">
                        Record yourself speaking in response to the prompts below to test your speaking skills.
                    </p>

                    <form action="{{ route('speaking-test.evaluate') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-6">
                            <label for="language" class="block text-sm font-medium text-gray-700 mb-1">Select Language</label>
                            <select id="language" name="language" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="en">English</option>
                                <option value="id">Indonesian</option>
                                <option value="es">Spanish</option>
                                <option value="fr">French</option>
                                <option value="de">German</option>
                                <option value="ja">Japanese</option>
                                <option value="zh">Chinese</option>
                            </select>
                        </div>
                        
                        <!-- Speaking Prompts -->
                        <div class="space-y-8">
                            <div class="bg-gray-50 p-4 rounded-md">
                                <h3 class="font-medium mb-2">Speaking Task 1:</h3>
                                
                                <p class="mb-4">Introduce yourself and talk about your hobbies. Your response should be about 1-2 minutes long.</p>
                                
                                <div class="mb-6">
                                    <div id="recorder-controls-1" class="flex items-center space-x-2">
                                        <button type="button" class="start-recording px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            Start Recording
                                        </button>
                                        <button type="button" class="stop-recording px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" disabled>
                                            Stop Recording
                                        </button>
                                        <span class="recording-status text-sm text-gray-500">Not recording</span>
                                    </div>
                                    
                                    <div class="audio-player mt-3 hidden">
                                        <audio controls class="w-full"></audio>
                                    </div>
                                    
                                    <input type="hidden" name="audio[]" class="audio-data">
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-md">
                                <h3 class="font-medium mb-2">Speaking Task 2:</h3>
                                
                                <p class="mb-4">Describe a memorable trip or vacation you took. Include where you went, when you went there, who you went with, and why it was memorable. Your response should be about 1-2 minutes long.</p>
                                
                                <div class="mb-6">
                                    <div id="recorder-controls-2" class="flex items-center space-x-2">
                                        <button type="button" class="start-recording px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            Start Recording
                                        </button>
                                        <button type="button" class="stop-recording px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" disabled>
                                            Stop Recording
                                        </button>
                                        <span class="recording-status text-sm text-gray-500">Not recording</span>
                                    </div>
                                    
                                    <div class="audio-player mt-3 hidden">
                                        <audio controls class="w-full"></audio>
                                    </div>
                                    
                                    <input type="hidden" name="audio[]" class="audio-data">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-8">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Submit Recordings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Find all recorder controls
            document.querySelectorAll('.start-recording').forEach(function(startButton, index) {
                const controlsId = startButton.closest('[id^="recorder-controls-"]').id;
                const stopButton = document.querySelector(`#${controlsId} .stop-recording`);
                const statusElement = document.querySelector(`#${controlsId} .recording-status`);
                const audioPlayer = startButton.closest('.bg-gray-50').querySelector('.audio-player');
                const audioElement = audioPlayer.querySelector('audio');
                const audioInput = startButton.closest('.bg-gray-50').querySelector('.audio-data');
                
                let mediaRecorder;
                let audioChunks = [];
                
                // Start recording
                startButton.addEventListener('click', function() {
                    audioChunks = [];
                    
                    navigator.mediaDevices.getUserMedia({ audio: true })
                        .then(stream => {
                            mediaRecorder = new MediaRecorder(stream);
                            mediaRecorder.start();
                            
                            statusElement.textContent = 'Recording...';
                            startButton.disabled = true;
                            stopButton.disabled = false;
                            
                            mediaRecorder.addEventListener("dataavailable", event => {
                                audioChunks.push(event.data);
                            });
                            
                            mediaRecorder.addEventListener("stop", () => {
                                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                                const audioUrl = URL.createObjectURL(audioBlob);
                                audioElement.src = audioUrl;
                                
                                // Show the audio player
                                audioPlayer.classList.remove('hidden');
                                
                                // Convert to base64 for submission
                                const reader = new FileReader();
                                reader.readAsDataURL(audioBlob);
                                reader.onloadend = function() {
                                    const base64data = reader.result;
                                    audioInput.value = base64data;
                                };
                                
                                // Stop all audio tracks
                                stream.getTracks().forEach(track => track.stop());
                            });
                        })
                        .catch(error => {
                            console.error('Error accessing microphone:', error);
                            statusElement.textContent = 'Error: ' + error.message;
                        });
                });
                
                // Stop recording
                stopButton.addEventListener('click', function() {
                    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                        mediaRecorder.stop();
                        startButton.disabled = false;
                        stopButton.disabled = true;
                        statusElement.textContent = 'Recording complete';
                    }
                });
            });
        });
    </script>
</x-app-layout> 