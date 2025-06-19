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
        
        $teacher = Auth::user();
        $query = User::where('role', 'student');
        
        // Initialize teacherLanguageSettings
        $teacherLanguageSettings = [];
        
        // If user is a teacher (not admin), filter students by teacher's assigned language levels
        if ($teacher->role === 'teacher') {
            // Get teacher's assigned language levels
            $teacherLanguages = \App\Models\TeacherLanguage::where('teacher_id', $teacher->id)->get();
            
            if ($teacherLanguages->count() > 0) {
                $query->whereHas('assessments', function($q) use ($teacherLanguages) {
                    $q->where(function($subQuery) use ($teacherLanguages) {
                        foreach ($teacherLanguages as $setting) {
                            $subQuery->orWhere(function($levelQuery) use ($setting) {
                                $levelQuery->where('language', $setting->language)
                                          ->where('level', $setting->level);
                            });
                        }
                    });
                });
            }
            
            // Get teacher's language settings for display
            $teacherLanguageSettings = \App\Models\TeacherLanguage::where('teacher_id', $teacher->id)
                ->get()
                ->map(function($setting) {
                    $languages = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
                    $levels = [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
                    
                    return [
                        'language' => $languages[$setting->language] ?? $setting->language,
                        'level' => $setting->level,
                        'level_name' => $levels[$setting->level] ?? 'Unknown'
                    ];
                });
        }
        
        $students = $query->orderBy('name')->get();
        
        return view('teacher.evaluation-settings', [
            'students' => $students,
            'teacherLanguageSettings' => $teacherLanguageSettings
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
        $teacher = Auth::user();
        
        // If user is a teacher (not admin), check if they can access this student
        if ($teacher->role === 'teacher') {
            $teacherLanguages = \App\Models\TeacherLanguage::where('teacher_id', $teacher->id)->get();
            
            // Get student's latest assessment for language and level
            $latestAssessment = Assessment::where('user_id', $student->id)
                ->whereIn('type', ['pretest', 'post_test', 'placement', 'level_change'])
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($latestAssessment) {
                $studentLevel = $latestAssessment->level;
                $studentLanguage = $latestAssessment->language;
                
                $canAccess = false;
                foreach ($teacherLanguages as $setting) {
                    if ($setting->language === $studentLanguage && $setting->level === $studentLevel) {
                        $canAccess = true;
                        break;
                    }
                }
                
                if (!$canAccess) {
                    return redirect()->route('teacher.evaluation.settings')
                        ->with('error', 'Anda tidak memiliki akses untuk mengatur evaluasi siswa ini.');
                }
            } else {
                // If student has no assessments yet, deny access
                return redirect()->route('teacher.evaluation.settings')
                    ->with('error', 'Siswa ini belum memiliki hasil tes untuk menentukan level dan bahasa.');
            }
        }
        
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
        
        $student = User::findOrFail($studentId);
        $teacher = Auth::user();
        
        // If user is a teacher (not admin), check if they can access this student
        if ($teacher->role === 'teacher') {
            $teacherLanguages = \App\Models\TeacherLanguage::where('teacher_id', $teacher->id)->get();
            
            // Get student's latest assessment for language and level
            $latestAssessment = Assessment::where('user_id', $student->id)
                ->whereIn('type', ['pretest', 'post_test', 'placement', 'level_change'])
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($latestAssessment) {
                $studentLevel = $latestAssessment->level;
                $studentLanguage = $latestAssessment->language;
                
                $canAccess = false;
                foreach ($teacherLanguages as $setting) {
                    if ($setting->language === $studentLanguage && $setting->level === $studentLevel) {
                        $canAccess = true;
                        break;
                    }
                }
                
                if (!$canAccess) {
                    return redirect()->route('teacher.evaluation.settings')
                        ->with('error', 'Anda tidak memiliki akses untuk mengatur evaluasi siswa ini.');
                }
            } else {
                // If student has no assessments yet, deny access
                return redirect()->route('teacher.evaluation.settings')
                    ->with('error', 'Siswa ini belum memiliki hasil tes untuk menentukan level dan bahasa.');
            }
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
