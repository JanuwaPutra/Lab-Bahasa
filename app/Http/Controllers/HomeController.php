<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }
    
    /**
     * Show the user dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function dashboard()
    {
        return view('dashboard');
    }
    
    /**
     * Show the user profile.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function profile()
    {
        return view('profile');
    }
    
    /**
     * Reset user data.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetData()
    {
        // Add logic to reset user data here
        
        return redirect()->route('dashboard')->with('success', 'Your data has been reset');
    }
    
    /**
     * Set the application language.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setLanguage(Request $request)
    {
        $request->validate([
            'language' => 'required|string|in:id,en,ru',
        ]);
        
        $language = $request->input('language');
        session(['language' => $language]);
        
        return back()->with('success', 'Bahasa berhasil diubah');
    }
}
