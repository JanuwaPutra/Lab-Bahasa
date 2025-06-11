<?php

namespace App\Http\Controllers;

use App\Services\GrammarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TestResult;

class GrammarController extends Controller
{
    protected $grammarService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\GrammarService  $grammarService
     * @return void
     */
    public function __construct(GrammarService $grammarService)
    {
        $this->grammarService = $grammarService;
    }

    /**
     * Show the grammar correction form
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('grammar.index');
    }

    /**
     * Process grammar correction request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function correct(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'language' => 'required|string|size:2',
        ]);

        $text = $request->input('text');
        $language = $request->input('language', 'en');

        // Count words
        $wordCount = $this->grammarService->countWords($text);

        // Get corrected text and paraphrase
        $result = $this->grammarService->analyzeAndParaphrase($text, $language);
        
        // Save the test result if authenticated
        if (Auth::check()) {
            TestResult::create([
                'user_id' => Auth::id(),
                'test_type' => 'grammar',
                'original_text' => $text,
                'corrected_text' => $result['corrected_text'],
                'word_count' => $wordCount,
                'language' => $language
            ]);
        }

        return view('grammar.index', [
            'originalText' => $text,
            'correctedText' => $result['corrected_text'],
            'paraphraseTitle' => $result['paraphrase_title'],
            'wordCount' => $wordCount,
            'language' => $language
        ]);
    }

    /**
     * API endpoint for grammar correction
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiCorrect(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'language' => 'required|string|size:2',
        ]);

        $text = $request->input('text');
        $language = $request->input('language', 'en');

        // Get corrected text with explanations
        $result = $this->grammarService->correctGrammar($text, $language);
        
        return response()->json([
            'original_text' => $text,
            'corrected_text' => $result['corrected_text'],
            'explanations' => $result['explanations']
        ]);
    }
}
