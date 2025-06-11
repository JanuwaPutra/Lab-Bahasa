<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Assessment;
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
        // Get all users with role 'student' or null (default role)
        $query = User::where(function($q) {
            $q->where('role', 'student')
              ->orWhereNull('role');
        });
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $students = $query->orderBy('name')->paginate(10);
        
        return view('teacher.students', compact('students'));
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
        
        // Check if the user is a student
        if ($student->role === 'teacher' || $student->role === 'admin') {
            return redirect()->route('teacher.students')
                ->with('error', 'Anda tidak dapat melihat detail guru atau admin.');
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
        
        return view('teacher.test_results', compact('results', 'type'));
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
        
        $query = \App\Models\LearningMaterial::orderBy('level')->orderBy('order');
        
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
        
        return view('teacher.materials', compact('materials', 'level', 'language'));
    }
    
    /**
     * Show the form for creating a new learning material.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function createMaterial()
    {
        return view('teacher.materials_create');
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
        
        return view('teacher.materials_edit', compact('material'));
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
        $level = $material->level;
        $language = $material->language;
        
        $material->delete();
        
        return redirect()->route('teacher.materials', ['level' => $level, 'language' => $language])
            ->with('success', 'Materi pembelajaran berhasil dihapus.');
    }
} 