<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Assessment;
use App\Models\TeacherLanguage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !in_array(Auth::user()->role, ['teacher', 'admin'])) {
                return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
            }
            return $next($request);
        });
    }

    /**
     * Display a list of students.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function students(Request $request)
    {
        // Get the current teacher
        $teacher = Auth::user();
        
        // Get all users with role 'student' or null (default role)
        $query = User::where(function($q) {
            $q->where('role', 'student')
              ->orWhereNull('role');
        });
        
        // Initialize teacherLanguageSettings
        $teacherLanguageSettings = [];
        
        // If user is a teacher (not admin), filter students by teacher's assigned language levels
        if ($teacher->role === 'teacher') {
            // Get teacher's assigned language levels - use direct DB query
            $teacherLanguages = \DB::table('teacher_languages')->where('teacher_id', $teacher->id)->get();
            
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
            $teacherLanguageSettings = [];
            $languages = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
            $levels = [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
            
            foreach ($teacherLanguages as $setting) {
                $teacherLanguageSettings[] = [
                    'language_code' => $setting->language,
                    'language' => $languages[$setting->language] ?? $setting->language,
                    'level' => $setting->level,
                    'level_name' => $levels[$setting->level] ?? 'Unknown'
                ];
            }
        }
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $students = $query->orderBy('name')->paginate(10);
        
        return view('teacher.students', compact('students', 'teacherLanguageSettings'));
    }

    /**
     * Display a specific student's details.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function studentDetail($id)
    {
        $student = User::findOrFail($id);
        $teacher = Auth::user();
        
        // Check if the user is a student
        if ($student->role === 'teacher' || $student->role === 'admin') {
            return redirect()->route('teacher.students')
                ->with('error', 'Anda tidak dapat melihat detail guru atau admin.');
        }
        
        // If user is a teacher (not admin), check if they can access this student
        if ($teacher->role === 'teacher') {
            $teacherLanguages = \DB::table('teacher_languages')->where('teacher_id', $teacher->id)->get();
            $studentLevel = $student->getCurrentLevel(session('language', 'id'));
            $studentLanguage = session('language', 'id');
            
            $canAccess = false;
            foreach ($teacherLanguages as $setting) {
                if ($setting->language === $studentLanguage && $setting->level === $studentLevel) {
                    $canAccess = true;
                    break;
                }
            }
            
            if (!$canAccess) {
                return redirect()->route('teacher.students')
                    ->with('error', 'Anda tidak memiliki akses untuk melihat detail siswa ini.');
            }
        }
        
        return view('teacher.student_detail', compact('student'));
    }

    /**
     * Display test results.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function testResults(Request $request)
    {
        $type = $request->query('type', 'pretest');
        $query = Assessment::with('user')->where('type', $type);
        
        // If user is a teacher (not admin), filter results by teacher's assigned language levels
        $teacher = Auth::user();
        
        // Initialize teacherLanguageSettings
        $teacherLanguageSettings = [];
        
        if ($teacher->role === 'teacher') {
            $teacherLanguages = \DB::table('teacher_languages')->where('teacher_id', $teacher->id)->get();
            
            if ($teacherLanguages->count() > 0) {
                $query->where(function($q) use ($teacherLanguages) {
                    foreach ($teacherLanguages as $setting) {
                        $q->orWhere(function($levelQuery) use ($setting) {
                            $levelQuery->where('language', $setting->language)
                                      ->where('level', $setting->level);
                        });
                    }
                });
            }
            
            // Get teacher's language settings for display
            $languages = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
            $levels = [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
            
            foreach ($teacherLanguages as $setting) {
                $teacherLanguageSettings[] = [
                    'language_code' => $setting->language,
                    'language' => $languages[$setting->language] ?? $setting->language,
                    'level' => $setting->level,
                    'level_name' => $levels[$setting->level] ?? 'Unknown'
                ];
            }
        }
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Filter by student ID if provided
        if ($request->has('student_id') && !empty($request->student_id)) {
            $query->where('user_id', $request->student_id);
        }
        
        $results = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return view('teacher.test_results', compact('results', 'type', 'teacherLanguageSettings'));
    }

    /**
     * Display a specific test result.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function testResultDetail($id)
    {
        $result = Assessment::with('user')->findOrFail($id);
        $teacher = Auth::user();
        
        // If user is a teacher (not admin), check if they can access this result
        if ($teacher->role === 'teacher') {
            $teacherLanguages = \DB::table('teacher_languages')->where('teacher_id', $teacher->id)->get();
            
            $canAccess = false;
            foreach ($teacherLanguages as $setting) {
                if ($setting->language === $result->language && $setting->level === $result->level) {
                    $canAccess = true;
                    break;
                }
            }
            
            if (!$canAccess) {
                return redirect()->route('teacher.test.results')
                    ->with('error', 'Anda tidak memiliki akses untuk melihat hasil test ini.');
            }
        }
        
        // Process answers and details if needed
        if ($result->answers && (empty($result->details) || $result->details === '[]')) {
            // Process details directly instead of relying on middleware
            app(\App\Http\Middleware\UpdateAssessmentOnDisplay::class)
                ->handle(request(), function ($request) { return null; });
            
            // Reload result to get the updated data
            $result = Assessment::with('user')->findOrFail($id);
        }
        
        return view('teacher.test_result_detail', compact('result'));
    }
    
    /**
     * Display a list of learning materials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function materials(Request $request)
    {
        $level = $request->query('level');
        $language = $request->query('language', 'id');
        $teacher = Auth::user();
        
        $query = \App\Models\LearningMaterial::orderBy('level')->orderBy('order');
        
        // Initialize teacherLanguageSettings
        $teacherLanguageSettings = [];
        
        // If user is a teacher (not admin), filter materials by teacher's assigned language levels
        if ($teacher->role === 'teacher') {
            // Use direct DB query to avoid model issues
            $teacherLanguages = \DB::table('teacher_languages')->where('teacher_id', $teacher->id)->get();
            
            if ($teacherLanguages->count() > 0) {
                $query->where(function($q) use ($teacherLanguages) {
                    foreach ($teacherLanguages as $setting) {
                        $q->orWhere(function($levelQuery) use ($setting) {
                            $levelQuery->where('language', $setting->language)
                                      ->where('level', $setting->level);
                        });
                    }
                });
            }
            
            // Get teacher's language settings for display
            $languages = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
            $levels = [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
            
            foreach ($teacherLanguages as $setting) {
                $teacherLanguageSettings[] = [
                    'language_code' => $setting->language,
                    'language' => $languages[$setting->language] ?? $setting->language,
                    'level' => $setting->level,
                    'level_name' => $levels[$setting->level] ?? 'Unknown'
                ];
            }
            
            // Debug output
            \Log::debug('Teacher Language Settings in materials method:', [
                'teacher_id' => $teacher->id, 
                'count' => count($teacherLanguageSettings),
                'settings' => $teacherLanguageSettings
            ]);
        }
        
        // Filter by level if provided
        if ($level) {
            $query->where('level', $level);
        }
        
        // Filter by language
        $query->where('language', $language);
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        $materials = $query->paginate(10);
        
        return view('teacher.materials', compact('materials', 'level', 'language', 'teacherLanguageSettings'));
    }
    
    /**
     * Show the form for creating a new learning material.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function createMaterial()
    {
        $teacher = Auth::user();
        
        // Get language settings (for teacher or admin)
        $teacherLanguageSettings = [];
        
        // Define the available languages and levels
        $languages = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
        $levels = [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
        
        // IMPORTANT: Force only the teacher's actual language settings to be used
        if ($teacher->role === 'teacher') {
            // For teachers, get their assigned language levels - use direct DB query to avoid any model issues
            $teacherLanguages = \DB::table('teacher_languages')->where('teacher_id', $teacher->id)->get();
            
            // Debug the teacher languages
            \Log::debug('Teacher Languages from DB:', [
                'teacher_id' => $teacher->id,
                'count' => $teacherLanguages->count(),
                'languages' => $teacherLanguages->toArray()
            ]);
            
            // Only use the teacher's actual language settings
            foreach ($teacherLanguages as $setting) {
                $teacherLanguageSettings[] = [
                    'language_code' => $setting->language,
                    'language' => $languages[$setting->language] ?? $setting->language,
                    'level' => $setting->level,
                    'level_name' => $levels[$setting->level] ?? 'Unknown'
                ];
            }
            
            // If no settings found, add at least one default option
            if (empty($teacherLanguageSettings)) {
                $teacherLanguageSettings[] = [
                    'language_code' => 'id',
                    'language' => 'Indonesia',
                    'level' => 1,
                    'level_name' => 'Beginner'
                ];
            }
        } else {
            // For admin, provide all possible language and level combinations
            foreach ($languages as $code => $name) {
                foreach ($levels as $level => $levelName) {
                    $teacherLanguageSettings[] = [
                        'language_code' => $code,
                        'language' => $name,
                        'level' => $level,
                        'level_name' => $levelName
                    ];
                }
            }
        }
        
        // Make sure we have at least one language and level option
        if (empty($teacherLanguageSettings)) {
            $teacherLanguageSettings[] = [
                'language_code' => 'id',
                'language' => 'Indonesia',
                'level' => 1,
                'level_name' => 'Beginner'
            ];
        }
        
        // Debug output
        \Log::debug('Teacher Language Settings:', [
            'teacher_id' => $teacher->id, 
            'teacher_role' => $teacher->role,
            'settings_count' => count($teacherLanguageSettings),
            'settings' => $teacherLanguageSettings
        ]);
        
        // Double-check direct DB query for teacher languages
        $directDbCheck = \DB::table('teacher_languages')->where('teacher_id', $teacher->id)->get();
        \Log::debug('Direct DB check:', [
            'count' => $directDbCheck->count(),
            'data' => $directDbCheck->toArray()
        ]);
        
        return view('teacher.materials_create', compact('teacherLanguageSettings'));
    }
    
    /**
     * Store a newly created learning material in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeMaterial(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'material_type' => 'required|string|in:text,video,audio,document',
            'media_url' => 'nullable|string|url',
            'level' => 'required|integer|min:1|max:3',
            'language' => 'required|string|size:2',
            'tags' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
        ]);
        
        $teacher = Auth::user();
        
        // If user is a teacher (not admin), check if they can create material for this language and level
        if ($teacher->role === 'teacher') {
            $canCreate = \DB::table('teacher_languages')
                ->where('teacher_id', $teacher->id)
                ->where('language', $request->language)
                ->where('level', $request->level)
                ->exists();
                
            if (!$canCreate) {
                return redirect()->route('teacher.materials')
                    ->with('error', 'Anda tidak memiliki akses untuk membuat materi dengan bahasa dan level tersebut.');
            }
        }
        
        $material = new \App\Models\LearningMaterial();
        $material->title = $request->title;
        $material->description = $request->description;
        $material->content = $request->content;
        $material->type = $request->material_type;
        $material->url = $request->media_url;
        $material->level = $request->level;
        $material->language = $request->language;
        $material->active = $request->has('active');
        $material->order = $request->order ?? 0;
        
        // Handle metadata
        $metadata = [
            'tags' => $request->tags,
        ];
        
        if ($request->material_type === 'video' || $request->material_type === 'audio') {
            $metadata['duration'] = $request->duration ?? 0;
        }
        
        $material->metadata = $metadata;
        $material->save();
        
        return redirect()->route('teacher.materials', ['level' => $material->level, 'language' => $material->language])
            ->with('success', 'Materi pembelajaran berhasil ditambahkan.');
    }
    
    /**
     * Show the form for editing the specified learning material.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function editMaterial($id)
    {
        $material = \App\Models\LearningMaterial::findOrFail($id);
        $teacher = Auth::user();
        
        // If user is a teacher (not admin), check if they can edit this material
        if ($teacher->role === 'teacher') {
            $canEdit = \DB::table('teacher_languages')
                ->where('teacher_id', $teacher->id)
                ->where('language', $material->language)
                ->where('level', $material->level)
                ->exists();
                
            if (!$canEdit) {
                return redirect()->route('teacher.materials')
                    ->with('error', 'Anda tidak memiliki akses untuk mengedit materi ini.');
            }
        }
        
        // Get language settings (for teacher or admin)
        $teacherLanguageSettings = [];
        
        // Define the available languages and levels
        $languages = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
        $levels = [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
        
        // IMPORTANT: Force only the teacher's actual language settings to be used
        if ($teacher->role === 'teacher') {
            // For teachers, get their assigned language levels - use direct DB query to avoid any model issues
            $teacherLanguages = \DB::table('teacher_languages')->where('teacher_id', $teacher->id)->get();
            
            // Debug the teacher languages
            \Log::debug('Teacher Languages from DB (Edit Material):', [
                'teacher_id' => $teacher->id,
                'count' => $teacherLanguages->count(),
                'languages' => $teacherLanguages->toArray()
            ]);
            
            // Only use the teacher's actual language settings
            foreach ($teacherLanguages as $setting) {
                $teacherLanguageSettings[] = [
                    'language_code' => $setting->language,
                    'language' => $languages[$setting->language] ?? $setting->language,
                    'level' => $setting->level,
                    'level_name' => $levels[$setting->level] ?? 'Unknown'
                ];
            }
            
            // If no settings found, add at least one default option
            if (empty($teacherLanguageSettings)) {
                $teacherLanguageSettings[] = [
                    'language_code' => 'id',
                    'language' => 'Indonesia',
                    'level' => 1,
                    'level_name' => 'Beginner'
                ];
            }
        } else {
            // For admin, provide all possible language and level combinations
            foreach ($languages as $code => $name) {
                foreach ($levels as $level => $levelName) {
                    $teacherLanguageSettings[] = [
                        'language_code' => $code,
                        'language' => $name,
                        'level' => $level,
                        'level_name' => $levelName
                    ];
                }
            }
        }
        
        // Make sure we have at least one language and level option
        if (empty($teacherLanguageSettings)) {
            $teacherLanguageSettings[] = [
                'language_code' => 'id',
                'language' => 'Indonesia',
                'level' => 1,
                'level_name' => 'Beginner'
            ];
        }
        
        // Debug output
        \Log::debug('Teacher Language Settings (Edit Material):', [
            'teacher_id' => $teacher->id, 
            'teacher_role' => $teacher->role,
            'settings_count' => count($teacherLanguageSettings),
            'settings' => $teacherLanguageSettings
        ]);
        
        return view('teacher.materials_edit', compact('material', 'teacherLanguageSettings'));
    }
    
    /**
     * Update the specified learning material in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateMaterial(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'material_type' => 'required|string|in:text,video,audio,document',
            'media_url' => 'nullable|string|url',
            'level' => 'required|integer|min:1|max:3',
            'language' => 'required|string|size:2',
            'tags' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
        ]);
        
        $material = \App\Models\LearningMaterial::findOrFail($id);
        $teacher = Auth::user();
        
        // If user is a teacher (not admin), check if they can update this material
        if ($teacher->role === 'teacher') {
            // Check current material access
            $canAccessCurrent = \DB::table('teacher_languages')
                ->where('teacher_id', $teacher->id)
                ->where('language', $material->language)
                ->where('level', $material->level)
                ->exists();
                
            // Check target language/level access if changing
            $canAccessTarget = \DB::table('teacher_languages')
                ->where('teacher_id', $teacher->id)
                ->where('language', $request->language)
                ->where('level', $request->level)
                ->exists();
                
            if (!$canAccessCurrent || !$canAccessTarget) {
                return redirect()->route('teacher.materials')
                    ->with('error', 'Anda tidak memiliki akses untuk mengubah materi ini dengan bahasa dan level tersebut.');
            }
        }
        
        $material->title = $request->title;
        $material->description = $request->description;
        $material->content = $request->content;
        $material->type = $request->material_type;
        $material->url = $request->media_url;
        $material->level = $request->level;
        $material->language = $request->language;
        $material->active = $request->has('active');
        $material->order = $request->order ?? 0;
        
        // Handle metadata
        $metadata = $material->metadata ?? [];
        $metadata['tags'] = $request->tags;
        
        if ($request->material_type === 'video' || $request->material_type === 'audio') {
            $metadata['duration'] = $request->duration ?? 0;
        }
        
        $material->metadata = $metadata;
        $material->save();
        
        return redirect()->route('teacher.materials', ['level' => $material->level, 'language' => $material->language])
            ->with('success', 'Materi pembelajaran berhasil diperbarui.');
    }
    
    /**
     * Remove the specified learning material from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyMaterial($id)
    {
        $material = \App\Models\LearningMaterial::findOrFail($id);
        $teacher = Auth::user();
        
        // If user is a teacher (not admin), check if they can delete this material
        if ($teacher->role === 'teacher') {
            $canDelete = TeacherLanguage::where('teacher_id', $teacher->id)
                ->where('language', $material->language)
                ->where('level', $material->level)
                ->exists();
                
            if (!$canDelete) {
                return redirect()->route('teacher.materials')
                    ->with('error', 'Anda tidak memiliki akses untuk menghapus materi ini.');
            }
        }
        
        $level = $material->level;
        $language = $material->language;
        
        $material->delete();
        
        return redirect()->route('teacher.materials', ['level' => $level, 'language' => $language])
            ->with('success', 'Materi pembelajaran berhasil dihapus.');
    }
} 