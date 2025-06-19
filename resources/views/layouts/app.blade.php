<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Lab-Bahasa AI</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #e4eaec 100%);
            min-height: 100vh;
        }
        .navbar-brand {
            font-weight: bold;
            color: #4A6FDC !important;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }
        main {
            min-height: calc(100vh - 100px);
        }
        .card {
            border-radius: 0.5rem;
            border: 0;
        }
        .bg-gradient-primary {
            background: linear-gradient(135deg, #4A6FDC 0%, #6C8FF8 100%) !important;
        }
        .input-group-text {
            border-right: 0;
        }
        .input-group .form-control {
            border-left: 0;
        }
        .input-group-text i {
            width: 20px;
            color: #4A6FDC;
        }
        .btn-primary {
            background-color: #4A6FDC;
            border-color: #4A6FDC;
        }
        .btn-primary:hover {
            background-color: #3A5DC7;
            border-color: #3A5DC7;
        }
        .text-primary {
            color: #4A6FDC !important;
        }
        .invalid-feedback.d-block {
            display: block !important;
        }
        .auth-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .auth-logo h1 {
            font-weight: 700;
            color: #4A6FDC;
            margin-bottom: 0;
            font-size: 2rem;
        }
        .auth-logo p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .auth-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem 0;
        }
        .toggle-password {
            border-left: 0;
            background-color: transparent;
            color: #6c757d;
            border-color: #ced4da;
        }
        .toggle-password:hover {
            background-color: #f8f9fa;
            color: #4A6FDC;
        }
        .toggle-password:focus {
            box-shadow: none;
            outline: none;
        }
        .input-group:has(.toggle-password) .form-control {
            border-right: 0;
        }
    </style>
</head>
<body>
    <div id="app">
        @if(request()->is('login') || request()->is('register') || request()->is('password/reset') || request()->is('password/reset/*'))
            <main class="auth-wrapper">
                <div class="container">
                    <div class="auth-logo">
                        <h1>Lab-Bahasa AI</h1>
                        <p>Platform Pembelajaran Bahasa Interaktif</p>
                    </div>
                    @yield('content')
                </div>
            </main>
        @else
            <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
                <div class="container">
                    <a class="navbar-brand" href="{{ url('/') }}">
                        Lab-Bahasa AI
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <!-- Left Side Of Navbar -->
                        <ul class="navbar-nav me-auto">
                            @auth
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('learning') }}">{{ __('Learning') }}</a>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarAssessment" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ __('Assessment') }}
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarAssessment">
                                        <li><a class="dropdown-item" href="{{ route('placement-test') }}">{{ __('Placement Test') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('pretest') }}">{{ __('Pre-Test') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('post-test') }}">{{ __('Post-Test') }}</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="{{ route('progress-report') }}">{{ __('Progress Report') }}</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarTools" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ __('Tools') }}
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarTools">
                                        <li><a class="dropdown-item" href="{{ route('grammar') }}">{{ __('Grammar Checker') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('speech') }}">{{ __('Speech Recognition') }}</a></li>
                                        <li><a class="dropdown-item" href="{{ route('youtube-transcription') }}">{{ __('YouTube Transcription') }}</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('virtual-tutor') }}">{{ __('Virtual Tutor') }}</a>
                                </li>
                            @endauth
                        </ul>

                        <!-- Right Side Of Navbar -->
                        <ul class="navbar-nav ms-auto">
                            <!-- Authentication Links -->
                            @guest
                                @if (Route::has('login'))
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('login') }}"><i class="fas fa-sign-in-alt me-1"></i>{{ __('Login') }}</a>
                                    </li>
                                @endif

                                @if (Route::has('register'))
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('register') }}"><i class="fas fa-user-plus me-1"></i>{{ __('Register') }}</a>
                                    </li>
                                @endif
                            @else
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                        <i class="fas fa-user-circle me-1"></i>{{ Auth::user()->name }}
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        <a class="dropdown-item" href="{{ route('profile') }}">
                                            <i class="fas fa-user me-1"></i>{{ __('My Profile') }}
                                        </a>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                        onclick="event.preventDefault();
                                                        document.getElementById('logout-form').submit();">
                                            <i class="fas fa-sign-out-alt me-1"></i>{{ __('Logout') }}
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </div>
            </nav>

            <main class="py-4">
                @yield('content')
            </main>
        @endif
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts Stack -->
    @stack('scripts')
</body>
</html>
