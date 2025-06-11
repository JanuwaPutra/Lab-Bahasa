<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Grammar Correction') }}
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
                                <h1 class="text-2xl font-bold mb-6">AI Grammar Correction</h1>
                                
                                <form action="{{ route('grammar.correct') }}" method="POST" class="mb-8">
                                    @csrf
                                    <div class="mb-4">
                                        <label for="language" class="block text-sm font-medium text-gray-700 mb-1">Select Language</label>
                                        <select id="language" name="language" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                            <option value="en" {{ isset($language) && $language == 'en' ? 'selected' : '' }}>English</option>
                                            <option value="id" {{ isset($language) && $language == 'id' ? 'selected' : '' }}>Indonesian</option>
                                            <option value="es" {{ isset($language) && $language == 'es' ? 'selected' : '' }}>Spanish</option>
                                            <option value="fr" {{ isset($language) && $language == 'fr' ? 'selected' : '' }}>French</option>
                                            <option value="de" {{ isset($language) && $language == 'de' ? 'selected' : '' }}>German</option>
                                            <option value="ja" {{ isset($language) && $language == 'ja' ? 'selected' : '' }}>Japanese</option>
                                            <option value="zh" {{ isset($language) && $language == 'zh' ? 'selected' : '' }}>Chinese</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="text" class="block text-sm font-medium text-gray-700 mb-1">Enter Text</label>
                                        <textarea id="text" name="text" rows="10" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md" placeholder="Enter your text here for grammar correction..." oninput="countWords(this)">{{ $originalText ?? '' }}</textarea>
                                        
                                        <div class="mt-1 flex justify-between">
                                            <span id="word-count" class="text-xs text-gray-500">0 words</span>
                                            
                                            @error('text')
                                                <p class="text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            Correct Grammar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                    
                    <td style="width: 50%; vertical-align: top;">
                        <!-- Right column - Results -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900">
                                @if(isset($correctedText))
                                    <div>
                                        @if(isset($paraphraseTitle) && !empty($paraphraseTitle))
                                            <h2 class="text-xl font-semibold mb-3">{{ $paraphraseTitle }}</h2>
                                        @else
                                            <h2 class="text-xl font-semibold mb-3">Corrected Text</h2>
                                        @endif
                                        
                                        @if(isset($wordCount))
                                            <p class="text-sm text-gray-500 mb-2">Word count: {{ $wordCount }}</p>
                                        @endif
                                        
                                        <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-4">
                                            <div class="flex justify-between items-start mb-2">
                                                <p class="whitespace-pre-wrap">{{ $correctedText }}</p>
                                                <button type="button" class="copy-button ml-2 p-1 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded" onclick="copyToClipboard('{{ addslashes($correctedText) }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                    </svg>
                                                </button>
                                            </div>
                                            
                                            @if(isset($originalText) && trim($correctedText) == trim($originalText))
                                                <div class="mt-3 text-sm text-gray-500 italic">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    No grammar errors were found in the original text.
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <h2 class="text-xl font-semibold mb-3">Corrected Text</h2>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="mt-2 text-gray-500">Enter text on the left to see grammar corrections here</p>
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

        function countWords(textarea) {
            const text = textarea.value.trim();
            const wordCount = text ? text.split(/\s+/).length : 0;
            document.getElementById('word-count').textContent = wordCount + ' words';
        }

        // Initialize word count on page load
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('text');
            if (textarea) {
                countWords(textarea);
            }
        });
    </script>
</x-app-layout> 