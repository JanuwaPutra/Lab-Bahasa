<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherMaterialController extends Controller
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
     * Display a listing of learning materials.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $this->checkAuthorization();
        
        $level = $request->input('level');
        $language = $request->input('language', 'id');
        $search = $request->input('search');
        
        $query = LearningMaterial::query();
        
        if ($level) {
            $query->where('level', $level);
        }
        
        if ($language) {
            $query->where('language', $language);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        $materials = $query->orderBy('level')
            ->orderBy('order')
            ->paginate(10);
        
        return view('teacher.materials', compact('materials', 'level', 'language'));
    }

    /**
     * Show the form for creating a new learning material.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $this->checkAuthorization();
        
        return view('teacher.materials_create');
    }

    /**
     * Store a newly created learning material.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->checkAuthorization();
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'material_type' => 'required|string|in:text,video,audio,document',
            'media_url' => 'nullable|url',
            'level' => 'required|integer|min:1|max:3',
            'language' => 'required|string|size:2',
            'order' => 'nullable|integer|min:0',
            'tags' => 'nullable|string',
            'active' => 'nullable',
        ]);
        
        // Prepare metadata
        $metadata = [
            'tags' => $request->input('tags') ? explode(',', $request->input('tags')) : [],
        ];
        
        if ($request->has('duration')) {
            $metadata['duration'] = (int)$request->input('duration');
        }
        
        // Create learning material
        $material = LearningMaterial::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'content' => $request->input('content'),
            'type' => $request->input('material_type'),
            'url' => $request->input('media_url'),
            'level' => $request->input('level'),
            'language' => $request->input('language'),
            'order' => $request->input('order', 0),
            'metadata' => $metadata,
            'active' => $request->boolean('active', true),
        ]);
        
        return redirect()->route('teacher.materials')
            ->with('success', 'Materi pembelajaran berhasil dibuat.');
    }

    /**
     * Show the form for editing a learning material.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $this->checkAuthorization();
        
        $material = LearningMaterial::findOrFail($id);
        
        return view('teacher.materials_edit', compact('material'));
    }

    /**
     * Update a learning material.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $this->checkAuthorization();
        
        $material = LearningMaterial::findOrFail($id);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'material_type' => 'required|string|in:text,video,audio,document',
            'media_url' => 'nullable|url',
            'level' => 'required|integer|min:1|max:3',
            'language' => 'required|string|size:2',
            'order' => 'nullable|integer|min:0',
            'tags' => 'nullable|string',
            'active' => 'nullable',
        ]);
        
        // Prepare metadata
        $metadata = [
            'tags' => $request->input('tags') ? explode(',', $request->input('tags')) : [],
        ];
        
        if ($request->has('duration')) {
            $metadata['duration'] = (int)$request->input('duration');
        }
        
        // Get existing metadata to preserve other values
        $existingMetadata = $material->metadata ?? [];
        $metadata = array_merge($existingMetadata, $metadata);
        
        // Update learning material
        $material->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'content' => $request->input('content'),
            'type' => $request->input('material_type'),
            'url' => $request->input('media_url'),
            'level' => $request->input('level'),
            'language' => $request->input('language'),
            'order' => $request->input('order', 0),
            'metadata' => $metadata,
            'active' => $request->boolean('active', true),
        ]);
        
        return redirect()->route('teacher.materials')
            ->with('success', 'Materi pembelajaran berhasil diperbarui.');
    }

    /**
     * Remove a learning material.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $this->checkAuthorization();
        
        $material = LearningMaterial::findOrFail($id);
        $material->delete();
        
        return redirect()->route('teacher.materials')
            ->with('success', 'Materi pembelajaran berhasil dihapus.');
    }
} 