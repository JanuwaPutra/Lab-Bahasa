<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Check if the user has the required role.
     * 
     * @return \Illuminate\Http\RedirectResponse|null
     */
    private function checkRole()
    {
        $user = auth()->user();
        
        if (!$user || !($user->hasRole('teacher') || $user->hasRole('admin'))) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }
        
        return null;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($redirect = $this->checkRole()) {
            return $redirect;
        }
        
        $type = $request->query('type', 'pretest');
        $language = $request->query('language', 'id');
        
        $questions = Question::byAssessmentType($type)
            ->orderBy('level')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Get time limit for this test type
        $timeLimit = \App\Models\TestSettings::getTimeLimit($type, $language);
            
        return view('questions.index', compact('questions', 'type', 'timeLimit', 'language'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if ($redirect = $this->checkRole()) {
            return $redirect;
        }
        
        return view('questions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($redirect = $this->checkRole()) {
            return $redirect;
        }
        
        // Debug the request data
        \Log::info('Question Data:', [
            'type' => $request->type,
            'correct_answer' => $request->correct_answer,
            'options' => $request->options,
            'option_scores' => $request->option_scores,
            'all_data' => $request->all()
        ]);
        
        $request->validate([
            'text' => 'required|string',
            'type' => 'required|in:multiple_choice,true_false,essay,fill_blank',
            'assessment_type' => 'required|in:placement,pretest,post_test,listening,reading,speaking,grammar',
            'level' => 'required|integer|min:1|max:3',
            'points' => 'required|integer|min:1',
            'language' => 'required|string|size:2',
        ]);
        
        // For pretest and placement, default to level 1
        if (in_array($request->assessment_type, ['pretest', 'placement']) && (!$request->has('level') || empty($request->level))) {
            $request->merge(['level' => 1]);
        }
        
        // Additional validation based on question type
        if ($request->type == 'multiple_choice') {
            $request->validate([
                'options' => 'required|array|min:2',
                'options.*' => 'required|string',
                'option_scores' => 'nullable|array',
                'option_scores.*' => 'nullable|integer',
            ]);
            
            // Check if correct_answer_select is present in the request
            if ($request->has('correct_answer_select') && $request->correct_answer_select !== '') {
                // Use correct_answer_select as the correct answer
                $correctAnswer = (int) $request->correct_answer_select;
                $request->merge(['correct_answer' => $correctAnswer]);
            } 
            // If not, check if correct_answer is present
            else if (!$request->has('correct_answer') || $request->correct_answer === '') {
                \Log::error('Missing correct answer in request', [
                    'request_data' => $request->all(),
                    'has_correct_answer' => $request->has('correct_answer'),
                    'has_correct_answer_select' => $request->has('correct_answer_select'),
                    'correct_answer_value' => $request->correct_answer,
                    'correct_answer_select_value' => $request->correct_answer_select
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['correct_answer' => 'Jawaban benar harus dipilih.']);
            }
            
            // Convert correct_answer to integer if it's a string
            $correctAnswer = is_numeric($request->correct_answer) ? (int) $request->correct_answer : $request->correct_answer;
            
            // Validate that the correct_answer is a valid index
            if (is_int($correctAnswer) && ($correctAnswer < 0 || $correctAnswer >= count($request->options))) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['correct_answer' => 'Jawaban benar harus merupakan indeks yang valid.']);
            }
            
            // Set the correct_answer
            $request->merge(['correct_answer' => $correctAnswer]);
            
            // Debug after processing
            \Log::info('After processing:', [
                'correct_answer' => $request->correct_answer,
                'is_numeric' => is_numeric($request->correct_answer),
                'is_int' => is_int($correctAnswer)
            ]);
        } elseif ($request->type == 'true_false') {
            // For true/false, check if correct_answer is set; if not, default to "true"
            \Log::info('True/False question data before processing:', [
                'has_correct_answer' => $request->has('correct_answer'),
                'correct_answer_value' => $request->correct_answer,
                'request_all' => $request->all()
            ]);
            
            if (!$request->has('correct_answer') || !in_array($request->correct_answer, ['true', 'false'])) {
                $request->merge(['correct_answer' => 'true']);
                \Log::info('Setting default true/false value to TRUE');
            }
            
            // Ensure option_scores has exactly 2 items for true/false
            $optionScores = $request->input('option_scores', []);
            if (!isset($optionScores[0])) $optionScores[0] = 0;
            if (!isset($optionScores[1])) $optionScores[1] = 0;
            $request->merge(['option_scores' => $optionScores]);
            
            // Validate after ensuring we have a value
            $request->validate([
                'correct_answer' => 'required|in:true,false',
            ]);
            
            \Log::info('True/False question data after processing:', [
                'correct_answer' => $request->correct_answer,
                'option_scores' => $request->option_scores
            ]);
        } elseif ($request->type == 'essay') {
            $request->validate([
                'min_words' => 'required|integer|min:10',
            ]);
            // For essay questions, there's no correct answer
            $request->merge(['correct_answer' => null]);
        } elseif ($request->type == 'fill_blank') {
            $request->validate([
                'correct_answer' => 'required|string',
            ]);
        }
        
        // Create the question
        $question = new Question();
        $question->text = $request->text;
        $question->type = $request->type;
        $question->assessment_type = $request->assessment_type;
        $question->level = $request->level;
        $question->points = $request->points;
        $question->language = $request->language;
        $question->active = $request->has('active');
        
        // Set type-specific fields
        if ($request->type == 'multiple_choice') {
            // Set options directly, the model will handle JSON encoding
            $question->options = $request->options;
            $question->correct_answer = $request->correct_answer;
            
            // Set option scores if provided
            if ($request->has('option_scores')) {
                $question->option_scores = $request->option_scores;
            }
        } elseif ($request->type == 'true_false') {
            $question->correct_answer = $request->correct_answer;
            $question->options = []; // Ensure options is set to empty array
            
            // Set option scores if provided
            if ($request->has('option_scores')) {
                $question->option_scores = $request->option_scores;
            }
        } elseif ($request->type == 'essay') {
            $question->min_words = $request->min_words;
            $question->correct_answer = null; // Ensure correct_answer is null
            $question->options = []; // Ensure options is set to empty array
        } elseif ($request->type == 'fill_blank') {
            $question->correct_answer = $request->correct_answer;
            $question->options = []; // Ensure options is set to empty array
        }
        
        $question->save();
        
        return redirect()->route('questions.index', ['type' => $question->assessment_type])
            ->with('success', 'Pertanyaan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        if ($redirect = $this->checkRole()) {
            return $redirect;
        }
        
        return view('questions.show', compact('question'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question)
    {
        if ($redirect = $this->checkRole()) {
            return $redirect;
        }
        
        return view('questions.edit', compact('question'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Question $question)
    {
        if ($redirect = $this->checkRole()) {
            return $redirect;
        }
        
        $request->validate([
            'text' => 'required|string',
            'level' => 'required|integer|min:1|max:3',
            'points' => 'required|integer|min:1',
            'language' => 'required|string|size:2',
        ]);
        
        // For pretest and placement, default to level 1
        if (in_array($question->assessment_type, ['pretest', 'placement']) && (!$request->has('level') || empty($request->level))) {
            $request->merge(['level' => 1]);
        }
        
        // Additional validation based on question type
        if ($question->type == 'multiple_choice') {
            $request->validate([
                'options' => 'required|array|min:2',
                'options.*' => 'required|string',
                'option_scores' => 'nullable|array',
                'option_scores.*' => 'nullable|integer',
            ]);
            
            // Check if correct_answer_select is present in the request
            if ($request->has('correct_answer_select') && $request->correct_answer_select !== '') {
                // Use correct_answer_select as the correct answer
                $correctAnswer = (int) $request->correct_answer_select;
                $request->merge(['correct_answer' => $correctAnswer]);
            }
            // If not, check if correct_answer is present
            else if (!$request->has('correct_answer') || $request->correct_answer === '') {
                \Log::error('Missing correct_answer in update request', [
                    'request_data' => $request->all(),
                    'has_correct_answer' => $request->has('correct_answer'),
                    'has_correct_answer_select' => $request->has('correct_answer_select'),
                    'correct_answer_value' => $request->correct_answer,
                    'correct_answer_select_value' => $request->correct_answer_select,
                    'question_id' => $question->id
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['correct_answer' => 'Jawaban benar harus dipilih.']);
            }
            
            // Convert correct_answer to integer if it's a string
            $correctAnswer = is_numeric($request->correct_answer) ? (int) $request->correct_answer : $request->correct_answer;
            
            // Validate that the correct_answer is a valid index
            if (is_int($correctAnswer) && ($correctAnswer < 0 || $correctAnswer >= count($request->options))) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['correct_answer' => 'Jawaban benar harus merupakan indeks yang valid.']);
            }
            
            // Set the correct_answer
            $request->merge(['correct_answer' => $correctAnswer]);
            
            // Debug after processing
            \Log::info('After update processing:', [
                'correct_answer' => $request->correct_answer,
                'is_numeric' => is_numeric($request->correct_answer),
                'is_int' => is_int($correctAnswer),
                'question_id' => $question->id,
                'option_scores' => $request->option_scores
            ]);
        } elseif ($question->type == 'true_false') {
            // For true/false, check if correct_answer is set; if not, default to "true"
            \Log::info('True/False question update before processing:', [
                'has_correct_answer' => $request->has('correct_answer'),
                'correct_answer_value' => $request->correct_answer,
                'request_all' => $request->all()
            ]);
            
            if (!$request->has('correct_answer') || !in_array($request->correct_answer, ['true', 'false'])) {
                $request->merge(['correct_answer' => 'true']);
                \Log::info('Setting default true/false value to TRUE (update)');
            }
            
            // Ensure option_scores has exactly 2 items for true/false
            $optionScores = $request->input('option_scores', []);
            if (!isset($optionScores[0])) $optionScores[0] = 0;
            if (!isset($optionScores[1])) $optionScores[1] = 0;
            $request->merge(['option_scores' => $optionScores]);
            
            // Validate after ensuring we have a value
            $request->validate([
                'correct_answer' => 'required|in:true,false',
            ]);
            
            \Log::info('True/False question update after processing:', [
                'correct_answer' => $request->correct_answer,
                'option_scores' => $request->option_scores
            ]);
        } elseif ($question->type == 'essay') {
            $request->validate([
                'min_words' => 'required|integer|min:10',
            ]);
            // For essay questions, there's no correct answer
            $request->merge(['correct_answer' => null]);
        } elseif ($question->type == 'fill_blank') {
            $request->validate([
                'correct_answer' => 'required|string',
            ]);
        }
        
        // Update the question
        $question->text = $request->text;
        $question->level = $request->level;
        $question->points = $request->points;
        $question->language = $request->language;
        $question->active = $request->has('active');
        
        // Update type-specific fields
        if ($question->type == 'multiple_choice') {
            // Set options directly, the model will handle JSON encoding
            $question->options = $request->options;
            $question->correct_answer = $request->correct_answer;
            
            // Set option scores if provided
            if ($request->has('option_scores')) {
                $question->option_scores = $request->option_scores;
            }
        } elseif ($question->type == 'true_false') {
            $question->correct_answer = $request->correct_answer;
            $question->options = []; // Ensure options is set to empty array
            
            // Set option scores if provided
            if ($request->has('option_scores')) {
                $question->option_scores = $request->option_scores;
            }
        } elseif ($question->type == 'essay') {
            $question->min_words = $request->min_words;
            $question->correct_answer = null; // Ensure correct_answer is null
            $question->options = []; // Ensure options is set to empty array
        } elseif ($question->type == 'fill_blank') {
            $question->correct_answer = $request->correct_answer;
            $question->options = []; // Ensure options is set to empty array
        }
        
        $question->save();
        
        return redirect()->route('questions.index', ['type' => $question->assessment_type])
            ->with('success', 'Pertanyaan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        if ($redirect = $this->checkRole()) {
            return $redirect;
        }
        
        $type = $question->assessment_type;
        $question->delete();
        
        return redirect()->route('questions.index', ['type' => $type])
            ->with('success', 'Pertanyaan berhasil dihapus.');
    }
    
    /**
     * Delete multiple questions at once.
     */
    public function bulkDelete(Request $request)
    {
        if ($redirect = $this->checkRole()) {
            return $redirect;
        }
        
        $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:questions,id',
        ]);
        
        $type = 'pretest'; // Default
        
        if (!empty($request->question_ids)) {
            // Get the assessment type from the first question for redirect
            $firstQuestion = Question::find($request->question_ids[0]);
            if ($firstQuestion) {
                $type = $firstQuestion->assessment_type;
            }
            
            // Delete all selected questions
            $count = Question::whereIn('id', $request->question_ids)->delete();
            
            return redirect()->route('questions.index', ['type' => $type])
                ->with('success', $count . ' pertanyaan berhasil dihapus.');
        }
        
        return redirect()->route('questions.index', ['type' => $type])
            ->with('error', 'Tidak ada pertanyaan yang dipilih.');
    }

    /**
     * Update test settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        if ($redirect = $this->checkRole()) {
            return $redirect;
        }
        
        $request->validate([
            'test_type' => 'required|string',
            'time_limit' => 'required|integer|min:0|max:180',
            'language' => 'required|string|in:id,en',
        ]);
        
        $testType = $request->test_type;
        $timeLimit = $request->time_limit;
        $language = $request->language;
        
        // Find or create settings for this test type
        $settings = \App\Models\TestSettings::firstOrNew([
            'test_type' => $testType,
            'language' => $language,
        ]);
        
        $settings->time_limit = $timeLimit;
        $settings->save();
        
        return redirect()->route('questions.index', ['type' => $testType, 'language' => $language])
            ->with('success', 'Pengaturan waktu ujian berhasil diperbarui.');
    }
}
