<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use App\Models\MaterialQuiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MaterialQuizController extends Controller
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
     * Check if user is authorized to access this controller.
     */
    private function checkAuthorization()
    {
        if (!Auth::check() || (Auth::user()->role !== 'teacher' && Auth::user()->role !== 'admin')) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Display a form to create a quiz for a material.
     *
     * @param  int  $materialId
     * @return \Illuminate\View\View
     */
    public function create($materialId)
    {
        $this->checkAuthorization();
        
        $material = LearningMaterial::findOrFail($materialId);
        
        // Check if a quiz already exists
        $quiz = $material->quiz;
        
        if ($quiz) {
            return redirect()->route('teacher.materials.quiz.edit', $materialId)
                ->with('info', 'Kuis untuk materi ini sudah ada. Silakan edit kuis yang sudah ada.');
        }
        
        return view('teacher.materials_quiz_create', compact('material'));
    }
    
    /**
     * Store a new quiz for a material.
     *
     * @param  Request  $request
     * @param  int  $materialId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $materialId)
    {
        $material = LearningMaterial::findOrFail($materialId);
        
        // Validate the request
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'passing_score' => 'required|integer|min:1|max:100',
            'time_limit' => 'nullable|integer|min:1',
            'must_pass' => 'nullable',
            'active' => 'nullable',
            'questions' => 'required|array|min:1',
            'questions.*.text' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.correct_answer' => 'required|integer|min:0',
            'questions.*.points' => 'nullable|integer|min:1',
        ]);
        
        // Format questions properly
        $questions = [];
        foreach ($request->input('questions') as $index => $question) {
            $questionData = [
                'text' => $question['text'],
                'type' => 'multiple_choice', // Force multiple_choice type
                'points' => $question['points'] ?? 1,
            ];
            
            $questionData['options'] = $question['options'];
            $questionData['correct_answer'] = (int) $question['correct_answer'];
            
            $questions[] = $questionData;
        }
        
        // Create the quiz
        $quiz = MaterialQuiz::create([
            'learning_material_id' => $materialId,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'passing_score' => $request->input('passing_score'),
            'time_limit' => $request->input('time_limit'),
            'must_pass' => $request->boolean('must_pass', true),
            'active' => $request->boolean('active', true),
            'questions' => $questions,
        ]);
        
        return redirect()->route('teacher.materials')
            ->with('success', 'Kuis berhasil dibuat untuk materi "' . $material->title . '".');
    }
    
    /**
     * Display a form to edit a quiz for a material.
     *
     * @param  int  $materialId
     * @return \Illuminate\View\View
     */
    public function edit($materialId)
    {
        $material = LearningMaterial::findOrFail($materialId);
        $quiz = $material->quiz;
        
        if (!$quiz) {
            return redirect()->route('teacher.materials.quiz.create', $materialId)
                ->with('info', 'Kuis untuk materi ini belum ada. Silakan buat kuis baru.');
        }
        
        return view('teacher.materials_quiz_edit', compact('material', 'quiz'));
    }
    
    /**
     * Update a quiz for a material.
     *
     * @param  Request  $request
     * @param  int  $materialId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $materialId)
    {
        $this->checkAuthorization();
        
        $material = LearningMaterial::findOrFail($materialId);
        $quiz = $material->quiz;
        
        if (!$quiz) {
            return redirect()->route('teacher.materials.quiz.create', $materialId)
                ->with('error', 'Kuis untuk materi ini belum ada.');
        }
        
        // Validate the request
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'passing_score' => 'required|integer|min:1|max:100',
            'time_limit' => 'nullable|integer|min:1',
            'must_pass' => 'nullable',
            'active' => 'nullable',
            'questions' => 'required|array|min:1',
            'questions.*.text' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.correct_answer' => 'required|integer|min:0',
            'questions.*.points' => 'nullable|integer|min:1',
        ]);
        
        // Format questions properly
        $questions = [];
        foreach ($request->input('questions') as $index => $question) {
            $questionData = [
                'text' => $question['text'],
                'type' => 'multiple_choice', // Force multiple_choice type
                'points' => $question['points'] ?? 1,
            ];
            
            $questionData['options'] = $question['options'];
            $questionData['correct_answer'] = (int) $question['correct_answer'];
            
            $questions[] = $questionData;
        }
        
        // Update the quiz
        $quiz->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'passing_score' => $request->input('passing_score'),
            'time_limit' => $request->input('time_limit'),
            'must_pass' => $request->boolean('must_pass', true),
            'active' => $request->boolean('active', true),
            'questions' => $questions,
        ]);
        
        return redirect()->route('teacher.materials')
            ->with('success', 'Kuis berhasil diperbarui untuk materi "' . $material->title . '".');
    }
    
    /**
     * Delete a quiz for a material.
     *
     * @param  int  $materialId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($materialId)
    {
        $material = LearningMaterial::findOrFail($materialId);
        $quiz = $material->quiz;
        
        if ($quiz) {
            $quiz->delete();
            return redirect()->route('teacher.materials')
                ->with('success', 'Kuis berhasil dihapus dari materi "' . $material->title . '".');
        }
        
        return redirect()->route('teacher.materials')
            ->with('info', 'Tidak ada kuis untuk dihapus.');
    }
} 