<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assessment;
use App\Models\EvaluationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['index']);
    }

    /**
     * Show the evaluation page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $assessments = null;
        $evaluationSettings = null;
        
        if (auth()->check()) {
            $user = auth()->user();
            $assessments = Assessment::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
                
            // Get user's evaluation settings or create default if not exists
            $evaluationSettings = EvaluationSetting::getForUser($user->id);
        }
        
        return view('assessment.evaluation', [
            'assessments' => $assessments,
            'settings' => $evaluationSettings
        ]);
    }
    
    /**
     * Show the evaluation settings page for teachers.
     *
     * @return \Illuminate\View\View
     */
    public function showSettingsPage()
    {
        if (!auth()->user() || (auth()->user()->role !== 'teacher' && auth()->user()->role !== 'admin')) {
            abort(403, 'Unauthorized action.');
        }
        
        $students = User::where('role', 'student')->orderBy('name')->get();
        
        return view('teacher.evaluation-settings', [
            'students' => $students
        ]);
    }
    
    /**
     * Show evaluation settings for a specific student.
     *
     * @param int $studentId
     * @return \Illuminate\View\View
     */
    public function showStudentSettings($studentId)
    {
        if (!auth()->user() || (auth()->user()->role !== 'teacher' && auth()->user()->role !== 'admin')) {
            abort(403, 'Unauthorized action.');
        }
        
        $student = User::findOrFail($studentId);
        $settings = EvaluationSetting::getForUser($studentId);
        
        return view('teacher.student-evaluation-settings', [
            'student' => $student,
            'settings' => $settings
        ]);
    }
    
    /**
     * Update evaluation settings for a specific student.
     *
     * @param Request $request
     * @param int $studentId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStudentSettings(Request $request, $studentId)
    {
        if (!auth()->user() || (auth()->user()->role !== 'teacher' && auth()->user()->role !== 'admin')) {
            abort(403, 'Unauthorized action.');
        }
        
        // Debug input
        \Log::info('Incoming request data', [
            'all' => $request->all(),
            'show_placement_test' => $request->has('show_placement_test'),
            'show_listening_test' => $request->has('show_listening_test'),
            'show_reading_test' => $request->has('show_reading_test'),
            'show_speaking_test' => $request->has('show_speaking_test'),
            'show_grammar_test' => $request->has('show_grammar_test'),
        ]);
        
        $student = User::findOrFail($studentId);
        
        // Prepare settings data
        $settingsData = [
            'teacher_id' => auth()->id(),
            'show_placement_test' => $request->has('show_placement_test') ? true : false,
            'show_listening_test' => $request->has('show_listening_test') ? true : false,
            'show_reading_test' => $request->has('show_reading_test') ? true : false,
            'show_speaking_test' => $request->has('show_speaking_test') ? true : false,
            'show_grammar_test' => $request->has('show_grammar_test') ? true : false,
            'notes' => $request->notes,
        ];
        
        // Debug
        \Log::info('Settings data to save', $settingsData);
        
        // Get existing settings or create new
        $settings = EvaluationSetting::where('user_id', $studentId)->first();
        
        if ($settings) {
            // Update existing
            $settings->update($settingsData);
            \Log::info('Updated existing settings', ['id' => $settings->id]);
        } else {
            // Create new
            $settingsData['user_id'] = $studentId;
            $settings = EvaluationSetting::create($settingsData);
            \Log::info('Created new settings', ['id' => $settings->id]);
        }
        
        // Double check the saved data
        $freshSettings = EvaluationSetting::find($settings->id);
        \Log::info('Saved settings', [
            'id' => $freshSettings->id,
            'show_placement_test' => $freshSettings->show_placement_test,
            'show_listening_test' => $freshSettings->show_listening_test,
            'show_reading_test' => $freshSettings->show_reading_test,
            'show_speaking_test' => $freshSettings->show_speaking_test,
            'show_grammar_test' => $freshSettings->show_grammar_test,
        ]);
        
        return redirect()->route('teacher.evaluation.settings')
            ->with('success', "Pengaturan evaluasi untuk {$student->name} berhasil diperbarui.");
    }
}
