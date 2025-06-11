<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('YouTube Transcription') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <table style="width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 10px;">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <!-- Left column - Input form -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900">
                                <h1 class="text-2xl font-bold mb-6">YouTube Video Transcription</h1>
                                <p class="text-sm text-gray-600 mb-4">Powered by Google Cloud API</p>
                                
                                @if(isset($error))
                                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <strong class="font-bold">Error!</strong>
                                        <span class="block sm:inline">{{ $error }}</span>
                                        
                                        @if(strpos($error, 'Failed to get transcript') !== false)
                                            <div class="mt-2 text-sm">
                                                <p>Possible reasons:</p>
                                                <ul class="list-disc pl-5 mt-1">
                                                    <li>The video doesn't have captions/subtitles</li>
                                                    <li>The captions are not available in the selected language</li>
                                                    <li>The video owner has disabled captions</li>
                                                    <li>YouTube API limitations</li>
                                                </ul>
                                                <p class="mt-2">Try another video or select a different language.</p>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                
                                <form action="{{ route('youtube-transcription.transcribe') }}" method="POST" class="mb-8">
                                    @csrf

                                    <div class="mb-4">
                                        <label for="youtube_url" class="block text-sm font-medium text-gray-700 mb-1">YouTube URL</label>
                                        <input type="url" id="youtube_url" name="youtube_url" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md" placeholder="https://www.youtube.com/watch?v=..." value="{{ $youtubeUrl ?? '' }}">
                                        
                                        @error('youtube_url')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <div>
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            Get Transcript
                                        </button>
                                    </div>
                                </form>
                                
                                @if(isset($youtubeUrl))
                                    <div class="mt-4">
                                        <h3 class="text-lg font-medium mb-2">Video Preview</h3>
                                        <div class="relative pb-56.25 h-0 overflow-hidden max-w-full">
                                            <iframe class="absolute top-0 left-0 w-full h-full" src="{{ str_replace('watch?v=', 'embed/', $youtubeUrl) }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    
                    <td style="width: 50%; vertical-align: top;">
                        <!-- Right column - Results -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900">
                                @if(isset($transcript))
                                    <div>
                                        <h2 class="text-xl font-semibold mb-3">{{ $videoTitle ?? 'YouTube Transcript' }}</h2>
                                        
                                        <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-4">
                                            <div class="flex justify-between items-start mb-2">
                                                <p class="whitespace-pre-wrap">{{ $transcript }}</p>
                                                <button type="button" class="copy-button ml-2 p-1 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded" onclick="copyToClipboard('{{ addslashes($transcript) }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <h2 class="text-xl font-semibold mb-3">YouTube Transcript</h2>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                        </svg>
                                        <p class="mt-2 text-gray-500">Enter a YouTube URL to see the transcript here</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <script>
        function copyToClipboard(text) {
            // Create a temporary textarea element
            const textarea = document.createElement('textarea');
            textarea.value = text.replace(/\\(.)/g, "$1"); // Remove escape slashes
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            
            // Select and copy the text
            textarea.select();
            document.execCommand('copy');
            
            // Remove the temporary element
            document.body.removeChild(textarea);
            
            // Show feedback
            const button = event.currentTarget;
            const originalHTML = button.innerHTML;
            
            button.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            `;
            
            // Reset the button after a short delay
            setTimeout(() => {
                button.innerHTML = originalHTML;
            }, 2000);
        }
    </script>
</x-app-layout> 