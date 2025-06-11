<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Listening Test') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-bold mb-6">Language Listening Test</h1>
                    
                    <p class="mb-6">
                        Listen to the audio clips and answer the questions below to test your listening comprehension.
                    </p>

                    <form action="{{ route('listening-test.evaluate') }}" method="POST">
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
                        
                        <!-- Sample Questions - These would come from a database in a real implementation -->
                        <div class="space-y-8">
                            <div class="bg-gray-50 p-4 rounded-md">
                                <h3 class="font-medium mb-2">1. Listen to the clip and answer the question:</h3>
                                
                                <div class="mb-4">
                                    <audio controls class="w-full">
                                        <source src="#" type="audio/mpeg">
                                        Your browser does not support the audio element.
                                    </audio>
                                    <p class="text-sm text-gray-500 mt-1">Listen to the conversation and answer: What is the woman planning to do this weekend?</p>
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <input id="q1-a" name="answers[1]" type="radio" value="A" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500" required>
                                        <label for="q1-a" class="ml-2 block text-sm text-gray-700">Go to the beach</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="q1-b" name="answers[1]" type="radio" value="B" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                                        <label for="q1-b" class="ml-2 block text-sm text-gray-700">Visit her parents</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="q1-c" name="answers[1]" type="radio" value="C" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                                        <label for="q1-c" class="ml-2 block text-sm text-gray-700">Study for an exam</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="q1-d" name="answers[1]" type="radio" value="D" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                                        <label for="q1-d" class="ml-2 block text-sm text-gray-700">Watch a movie</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-md">
                                <h3 class="font-medium mb-2">2. Listen to the clip and answer the question:</h3>
                                
                                <div class="mb-4">
                                    <audio controls class="w-full">
                                        <source src="#" type="audio/mpeg">
                                        Your browser does not support the audio element.
                                    </audio>
                                    <p class="text-sm text-gray-500 mt-1">Listen to the announcement and answer: What time does the train depart?</p>
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <input id="q2-a" name="answers[2]" type="radio" value="A" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500" required>
                                        <label for="q2-a" class="ml-2 block text-sm text-gray-700">9:15 AM</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="q2-b" name="answers[2]" type="radio" value="B" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                                        <label for="q2-b" class="ml-2 block text-sm text-gray-700">9:50 AM</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="q2-c" name="answers[2]" type="radio" value="C" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                                        <label for="q2-c" class="ml-2 block text-sm text-gray-700">10:15 AM</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="q2-d" name="answers[2]" type="radio" value="D" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                                        <label for="q2-d" class="ml-2 block text-sm text-gray-700">10:50 AM</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-8">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Submit Answers
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 