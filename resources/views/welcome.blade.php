<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Lab-Bahasa AI') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:400,600,700" rel="stylesheet" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom styles -->
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
            padding: 20px;
        }

        .title {
            font-size: 4rem;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .links > a {
            color: #fff;
            padding: 10px 25px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
            border-radius: 50px;
            background: rgba(255,255,255,0.2);
            margin: 0 10px;
            transition: background 0.3s;
        }

        .links > a:hover {
            background: rgba(255,255,255,0.3);
        }

        .btn-get-started {
            background: #fff;
            color: #6359dd;
            padding: 12px 30px;
            font-weight: bold;
            border-radius: 50px;
            text-transform: uppercase;
            margin-top: 20px;
            display: inline-block;
            text-decoration: none;
        }

        .btn-get-started:hover {
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            color: #4A6FDC;
        }

        .features {
            margin-top: 50px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .feature {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 20px;
            margin: 10px;
            width: 250px;
            backdrop-filter: blur(10px);
        }

        .feature h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .feature p {
            opacity: 0.8;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="flex-center position-ref full-height">
        @if (Route::has('login'))
            <div class="top-right links">
                @auth
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Login</a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}">Register</a>
                    @endif
                @endauth
            </div>
        @endif

        <div class="content">
            <div class="title">
                Lab-Bahasa AI
            </div>

            <div class="subtitle">
                Your AI-powered language learning platform
            </div>

            @if (Route::has('login'))
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-get-started">Go to Dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="btn-get-started">Get Started</a>
                @endauth
            @endif

            <div class="features">
                <div class="feature">
                    <h3>AI Assessment</h3>
                    <p>Accurately measure your language proficiency with our AI-powered assessment tools.</p>
                </div>
                <div class="feature">
                    <h3>Virtual Tutor</h3>
                    <p>Practice conversations with our AI language tutor who adapts to your learning needs.</p>
                </div>
                <div class="feature">
                    <h3>Grammar Checker</h3>
                    <p>Perfect your writing skills with instant grammar correction and suggestions.</p>
                </div>
                <div class="feature">
                    <h3>Speech Recognition</h3>
                    <p>Improve your pronunciation with real-time feedback on your speaking.</p>
                </div>
                <div class="feature">
                    <h3>Personalized Learning</h3>
                    <p>Get customized learning materials based on your proficiency level.</p>
                </div>
                <div class="feature">
                    <h3>Progress Tracking</h3>
                    <p>Track your learning journey with detailed progress reports and analytics.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
