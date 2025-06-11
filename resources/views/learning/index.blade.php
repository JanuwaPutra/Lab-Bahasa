<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Learning Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-bold mb-6">Learning Materials (Level {{ $level }})</h1>
                    
                    <div class="mb-6">
                        <label for="language" class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                        <select id="language_selector" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="en" {{ $language == 'en' ? 'selected' : '' }}>English</option>
                            <option value="id" {{ $language == 'id' ? 'selected' : '' }}>Indonesian</option>
                            <option value="es" {{ $language == 'es' ? 'selected' : '' }}>Spanish</option>
                            <option value="fr" {{ $language == 'fr' ? 'selected' : '' }}>French</option>
                            <option value="de" {{ $language == 'de' ? 'selected' : '' }}>German</option>
                            <option value="ja" {{ $language == 'ja' ? 'selected' : '' }}>Japanese</option>
                            <option value="zh" {{ $language == 'zh' ? 'selected' : '' }}>Chinese</option>
                        </select>
                    </div>
                    
                    @if(count($materials) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($materials as $material)
                                <div class="bg-gray-50 p-4 rounded-lg shadow-md">
                                    <h2 class="text-xl font-semibold mb-2">{{ $material->title }}</h2>
                                    <p class="text-gray-600 mb-4">{{ $material->description }}</p>
                                    
                                    @if($material->type == 'text')
                                        <div class="bg-white p-3 rounded border border-gray-200 mb-4 max-h-40 overflow-y-auto">
                                            <p class="text-sm">{{ Str::limit($material->content, 200) }}</p>
                                        </div>
                                    @elseif($material->type == 'video' && $material->url)
                                        <div class="aspect-w-16 aspect-h-9 mb-4">
                                            <iframe src="{{ $material->url }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full rounded"></iframe>
                                        </div>
                                    @elseif($material->type == 'audio' && $material->url)
                                        <div class="mb-4">
                                            <audio controls class="w-full">
                                                <source src="{{ $material->url }}" type="audio/mpeg">
                                                Your browser does not support the audio element.
                                            </audio>
                                        </div>
                                    @endif
                                    
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="text-sm text-gray-500">Type: {{ ucfirst($material->type) }}</span>
                                        <button type="button" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            View Full Material
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        No learning materials found for your current level and language selection.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle language change
            document.getElementById('language_selector').addEventListener('change', function() {
                const language = this.value;
                
                // Redirect to the same page with language parameter
                window.location.href = "{{ route('learning') }}?language=" + language;
            });
        });
    </script>
</x-app-layout> 