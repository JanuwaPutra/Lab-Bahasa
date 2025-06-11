<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Speech Recognition') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <table style="width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 10px;">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <!-- Left column - Recording form -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900">
                                <h1 class="text-2xl font-bold mb-3">Rekam Suara Anda</h1>
                                
                                @if(isset($phpConfigWarning))
                                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <span class="block sm:inline">{{ $phpConfigWarning }}</span>
                                    </div>
                                @endif
                                
                                @if(isset($error))
                                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <strong class="font-bold">Error!</strong>
                                        <span class="block sm:inline">{{ $error }}</span>
                                    </div>
                                @endif
                                
                                @if(isset($error) && str_contains($error, 'FFmpeg tidak tersedia'))
                                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <h5 class="font-bold">Alternatif tanpa FFmpeg:</h5>
                                        <p>Jika Anda tidak bisa menginstal FFmpeg, Anda masih bisa menggunakan fitur unggah file audio (.wav):</p>
                                        <ol class="list-decimal pl-5 mt-2">
                                            <li>Rekam suara Anda menggunakan aplikasi rekaman bawaan Windows (Voice Recorder)</li>
                                            <li>Simpan sebagai file .wav</li>
                                            <li>Unggah file tersebut menggunakan opsi "Unggah File Audio" di bawah</li>
                                        </ol>
                                    </div>
                                @endif
                                
                                <form action="{{ route('speech.recognize') }}" method="POST" enctype="multipart/form-data" id="speech-form">
                                    @csrf
                                    <div class="mb-4">
                                        <label for="reference_text" class="block text-sm font-medium text-gray-700 mb-1">Teks Referensi (untuk dibaca)</label>
                                        <textarea id="reference_text" name="reference_text" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md">{{ $referenceText ?? 'Saya sedang belajar bahasa menggunakan teknologi kecerdasan buatan.' }}</textarea>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="language" class="block text-sm font-medium text-gray-700 mb-1">Bahasa</label>
                                        <select id="language" name="language" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                            <option value="id" {{ isset($language) && $language == 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
                                            <option value="en" {{ isset($language) && $language == 'en' ? 'selected' : '' }}>Bahasa Inggris (US)</option>
                                            <option value="en-GB" {{ isset($language) && $language == 'en-GB' ? 'selected' : '' }}>Bahasa Inggris (UK)</option>
                                            <option value="ja" {{ isset($language) && $language == 'ja' ? 'selected' : '' }}>Bahasa Jepang</option>
                                            <option value="ko" {{ isset($language) && $language == 'ko' ? 'selected' : '' }}>Bahasa Korea</option>
                                            <option value="ar" {{ isset($language) && $language == 'ar' ? 'selected' : '' }}>Bahasa Arab</option>
                                            <option value="es" {{ isset($language) && $language == 'es' ? 'selected' : '' }}>Bahasa Spanyol</option>
                                            <option value="zh" {{ isset($language) && $language == 'zh' ? 'selected' : '' }}>Bahasa Mandarin</option>
                                            <option value="fr" {{ isset($language) && $language == 'fr' ? 'selected' : '' }}>Bahasa Perancis</option>
                                            <option value="de" {{ isset($language) && $language == 'de' ? 'selected' : '' }}>Bahasa Jerman</option>
                                            <option value="ru" {{ isset($language) && $language == 'ru' ? 'selected' : '' }}>Bahasa Rusia</option>
                                        </select>
                                    </div>

                                    
                                    <div class="mb-4">
                                        <div class="border-b border-gray-200 mb-4">
                                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                                <button type="button" id="record-tab" class="border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                                    Rekam Suara
                                                </button>
                                                <button type="button" id="upload-tab" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                                    Unggah Audio
                                                </button>
                                            </nav>
                                        </div>
                                        
                                        <!-- Tab rekam suara -->
                                        <div id="record-content" class="tab-content">
                                            <div class="flex items-center">
                                                <button type="button" id="record-button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 mr-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd" />
                                                    </svg>
                                                    Mulai Rekam
                                                </button>
                                                <span id="recording-status" class="text-sm">Tidak merekam</span>
                                                <span id="recording-timer" class="ml-2 text-sm"></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Tab unggah audio -->
                                        <div id="upload-content" class="tab-content hidden">
                                            <input type="file" name="audio" id="audio_file" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="audio/wav,audio/mp3,audio/webm,audio/ogg">
                                            <p class="mt-1 text-xs text-gray-500">Format yang didukung: WAV, MP3, WEBM, OGG (maksimal 8MB)</p>
                                            <div id="file_size_warning" class="hidden mt-2 text-sm text-red-600"></div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" id="recorded_audio" name="recorded_audio">
                                    <input type="hidden" id="input_type" name="input_type" value="record">
                                    
                                    <div>
                                        <button type="submit" id="submit-button" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            Proses Suara
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                    
                    <td style="width: 50%; vertical-align: top;">
                        <!-- Right column - Results -->
                        @if(isset($recognizedText) && $recognizedText !== null)
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="p-6 text-gray-900">
                                    <h2 class="text-xl font-semibold mb-3">Hasil Pengenalan Suara</h2>
                                    
                                    <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-4">
                                        <h3 class="text-md font-medium mb-2">Teks Referensi:</h3>
                                        <p class="whitespace-pre-wrap">{{ $referenceText }}</p>
                                    </div>
                                    
                                    <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-4">
                                        <h3 class="text-md font-medium mb-2">Teks yang Dikenali:</h3>
                                        <p class="whitespace-pre-wrap">{{ $recognizedText }}</p>
                                    
                                    </div>
                                    
                                    @if(isset($accuracy))
                                        <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-4">
                                            <h3 class="text-md font-medium mb-2">Evaluasi Pengucapan:</h3>
                                            <div class="flex flex-wrap">
                                                <div class="w-full md:w-1/3 mb-4 md:mb-0">
                                                    <h4 class="text-sm font-medium mb-1 text-center">Akurasi</h4>
                                                    <div class="w-full bg-gray-200 rounded-full h-6">
                                                        <div class="h-6 rounded-full flex items-center justify-center text-xs font-medium text-white
                                                            {{ $accuracy > 75 ? 'bg-green-600' : ($accuracy > 50 ? 'bg-yellow-500' : 'bg-red-600') }}"
                                                            style="width: {{ $accuracy }}%">
                                                            {{ $accuracy }}%
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="w-full md:w-2/3 md:pl-4">
                                                    <h4 class="text-sm font-medium mb-1">Umpan Balik:</h4>
                                                    <p>{{ $feedback ?? 'Tidak ada feedback tersedia.' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="p-6 text-gray-900">
                                    <h2 class="text-xl font-semibold mb-3">Hasil Pengenalan Suara</h2>
                                    <p class="text-center text-gray-500 py-8">Hasil pengenalan suara akan ditampilkan di sini setelah Anda merekam atau mengunggah audio.</p>
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <script>
        // Audio recording functionality
        document.addEventListener('DOMContentLoaded', function() {
            let mediaRecorder;
            let audioChunks = [];
            let isRecording = false;
            let startTime;
            let timerInterval;
            let audioBlobReady = false;
            let audioBase64Data = '';
            
            // Elements
            const recordButton = document.getElementById('record-button');
            const recordingStatus = document.getElementById('recording-status');
            const recordingTimer = document.getElementById('recording-timer');
            const audioDataInput = document.getElementById('recorded_audio');
            const inputTypeField = document.getElementById('input_type');
            const recordTab = document.getElementById('record-tab');
            const uploadTab = document.getElementById('upload-tab');
            const recordContent = document.getElementById('record-content');
            const uploadContent = document.getElementById('upload-content');
            const speechForm = document.getElementById('speech-form');
            const submitButton = document.getElementById('submit-button');
            const audioFileInput = document.getElementById('audio_file');
            const fileSizeWarning = document.getElementById('file_size_warning');
            const diagnosticButton = document.getElementById('diagnostic-button');
            const diagnosticResult = document.getElementById('diagnostic-result');
            
            // Max file size in bytes (8MB)
            const MAX_FILE_SIZE = 8 * 1024 * 1024;
            
            // File size validation
            if (audioFileInput) {
                audioFileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        if (file.size > MAX_FILE_SIZE) {
                            fileSizeWarning.textContent = `File terlalu besar (${(file.size / (1024 * 1024)).toFixed(2)}MB). Maksimal ukuran file adalah 8MB.`;
                            fileSizeWarning.classList.remove('hidden');
                            submitButton.disabled = true;
                            submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                        } else {
                            fileSizeWarning.classList.add('hidden');
                            submitButton.disabled = false;
                            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    }
                });
            }
            
            // Tab switching
            recordTab.addEventListener('click', function() {
                // Activate record tab
                recordTab.classList.add('border-indigo-500', 'text-indigo-600');
                recordTab.classList.remove('border-transparent', 'text-gray-500');
                uploadTab.classList.remove('border-indigo-500', 'text-indigo-600');
                uploadTab.classList.add('border-transparent', 'text-gray-500');
                
                // Show/hide content
                recordContent.classList.remove('hidden');
                uploadContent.classList.add('hidden');
                
                // Update input type
                inputTypeField.value = 'record';
            });
            
            uploadTab.addEventListener('click', function() {
                // Activate upload tab
                uploadTab.classList.add('border-indigo-500', 'text-indigo-600');
                uploadTab.classList.remove('border-transparent', 'text-gray-500');
                recordTab.classList.remove('border-indigo-500', 'text-indigo-600');
                recordTab.classList.add('border-transparent', 'text-gray-500');
                
                // Show/hide content
                uploadContent.classList.remove('hidden');
                recordContent.classList.add('hidden');
                
                // Update input type
                inputTypeField.value = 'upload';
            });
            
            // Start recording
            async function startRecording() {
                try {
                    // Reset audio state
                    audioBlobReady = false;
                    audioBase64Data = '';
                    audioDataInput.value = '';
                    
                    // Check if mediaDevices API is supported
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        throw new Error('Browser Anda tidak mendukung akses mikrofon. Coba gunakan Chrome, Firefox, atau Edge versi terbaru.');
                    }
                    
                    // Secure context check
                    if (window.isSecureContext === false) {
                        throw new Error('Fitur rekaman mikrofon membutuhkan HTTPS. Coba akses aplikasi melalui HTTPS atau localhost.');
                    }
                    
                    // Request audio with high quality
                    const stream = await navigator.mediaDevices.getUserMedia({ 
                        audio: {
                            echoCancellation: true,
                            noiseSuppression: true,
                            sampleRate: 44100,
                            channelCount: 1
                        }
                    });
                    
                    // Try to use WAV format directly if possible
                    let mimeType = '';
                    
                    // Try to check support for different mime-types (in priority order)
                    const supportedTypes = [
                        'audio/wav',
                        'audio/wave',
                        'audio/x-wav',
                        'audio/webm;codecs=opus',
                        'audio/webm',
                        'audio/ogg;codecs=opus',
                        'audio/mp4',
                        'audio/mpeg'
                    ];
                    
                    for (const type of supportedTypes) {
                        if (MediaRecorder.isTypeSupported(type)) {
                            mimeType = type;
                            console.log('Using audio format:', type);
                            break;
                        }
                    }
                    
                    const options = {
                        audioBitsPerSecond: 128000 // 128 kbps
                    };
                    
                    if (mimeType) {
                        options.mimeType = mimeType;
                    }
                    
                    mediaRecorder = new MediaRecorder(stream, options);
                    audioChunks = [];
                    
                    mediaRecorder.addEventListener('dataavailable', event => {
                        audioChunks.push(event.data);
                        console.log("Data chunk audio tersedia: ", event.data.type);
                    });
                    
                    mediaRecorder.addEventListener('stop', () => {
                        const audioBlob = new Blob(audioChunks);
                        
                        // Check if the recording exceeds size limit
                        if (audioBlob.size > MAX_FILE_SIZE) {
                            recordingStatus.textContent = "Rekaman terlalu besar (melebihi 8MB). Silakan rekam lebih singkat.";
                            recordingStatus.classList.add("text-red-600");
                            
                            // Enable record button for retry
                            recordButton.disabled = false;
                            submitButton.disabled = true;
                            submitButton.classList.add("opacity-50", "cursor-not-allowed");
                            
                            // Stop all audio tracks
                            stream.getTracks().forEach(track => track.stop());
                            return;
                        }
                        
                        // Convert Blob to base64 to send to server
                        const reader = new FileReader();
                        reader.readAsDataURL(audioBlob);
                        reader.onloadend = function() {
                            audioBase64Data = reader.result;
                            audioDataInput.value = audioBase64Data;
                            audioBlobReady = true;
                            console.log("Audio data berhasil direkam", audioBase64Data.substring(0, 50) + "...");
                            
                            // Enable submit button again
                            submitButton.disabled = false;
                            submitButton.textContent = "Proses Suara";
                            submitButton.classList.remove("bg-gray-400", "opacity-50", "cursor-not-allowed");
                            submitButton.classList.add("bg-blue-600", "hover:bg-blue-700");
                            recordingStatus.textContent = "Audio siap diproses";
                            recordingStatus.classList.remove("text-red-600");
                        }
                        
                        // Stop all audio tracks
                        stream.getTracks().forEach(track => track.stop());
                    });
                    
                    mediaRecorder.start();
                    isRecording = true;
                    
                    recordButton.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd" />
                        </svg>
                        Berhenti Rekam
                    `;
                    
                    recordButton.classList.remove('bg-red-600', 'hover:bg-red-700');
                    recordButton.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
                    recordingStatus.textContent = 'Sedang merekam...';
                    
                    // Start timer
                    startTime = Date.now();
                    timerInterval = setInterval(updateTimer, 1000);
                    updateTimer();
                    
                } catch (error) {
                    console.error('Error accessing microphone:', error);
                    alert('Gagal mengakses mikrofon. Pastikan Anda mengizinkan akses mikrofon pada browser Anda.');
                }
            }
            
            // Stop recording
            function stopRecording() {
                if (mediaRecorder && isRecording) {
                    mediaRecorder.stop();
                    isRecording = false;
                    
                    recordButton.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd" />
                        </svg>
                        Mulai Rekam
                    `;
                    
                    recordButton.classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
                    recordButton.classList.add('bg-red-600', 'hover:bg-red-700');
                    recordingStatus.textContent = 'Memproses rekaman...';
                    
                    // Disable submit button until audio is processed
                    submitButton.disabled = true;
                    submitButton.textContent = "Memproses Audio...";
                    submitButton.classList.remove("bg-blue-600", "hover:bg-blue-700");
                    submitButton.classList.add("bg-gray-400");
                    
                    // Stop timer
                    clearInterval(timerInterval);
                }
            }
            
            // Update timer function
            function updateTimer() {
                const elapsedTime = Math.floor((Date.now() - startTime) / 1000);
                const minutes = Math.floor(elapsedTime / 60).toString().padStart(2, '0');
                const seconds = (elapsedTime % 60).toString().padStart(2, '0');
                recordingTimer.textContent = `${minutes}:${seconds}`;
            }
            
            // Record button click handler
            recordButton.addEventListener('click', () => {
                if (isRecording) {
                    stopRecording();
                } else {
                    startRecording();
                }
            });
            
            // Form submit handler
            speechForm.addEventListener('submit', function(event) {
                // Jika sedang rekam, hentikan dulu
                if (isRecording) {
                    event.preventDefault();
                    stopRecording();
                    return false;
                }
                
                // Jika metode rekam tapi belum ada data
                if (inputTypeField.value === 'record') {
                    // Cek apakah ada rekaman audio
                    if (!audioBlobReady || !audioDataInput.value) {
                        event.preventDefault();
                        alert('Anda belum merekam audio. Silakan rekam audio terlebih dahulu atau pilih opsi Unggah Audio.');
                        return false;
                    }
                    
                    console.log("Mengirimkan form dengan audio...");
                    console.log("Audio data tersedia, panjang:", audioDataInput.value.length);
                    return true; // Lanjutkan submit
                } else {
                    // Mode upload file, cek apakah ada file
                    const fileInput = document.getElementById('audio_file');
                    if (!fileInput.files || fileInput.files.length === 0) {
                        event.preventDefault();
                        alert('Anda belum memilih file audio. Silakan pilih file audio terlebih dahulu.');
                        return false;
                    }
                    
                    console.log("Mengirimkan form dengan file upload...");
                    return true; // Lanjutkan submit
                }
            });
            

        });
    </script>
</x-app-layout> 