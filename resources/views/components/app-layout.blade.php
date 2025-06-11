<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Lab Bahasa AI') }}</title>
    
    <!-- Prevent sidebar flash with inline script -->
    <script>
      (function() {
        var isMobile = window.innerWidth < 768;
        var isCollapsed = localStorage.getItem('sidebarState') === 'collapsed';
        
        // Add classes immediately before any rendering
        if (isMobile) {
          document.documentElement.className += ' sidebar-mobile';
        } else if (isCollapsed) {
          document.documentElement.className += ' sidebar-collapsed';
        }
        
        // Disable all transitions during page load
        document.documentElement.className += ' no-transition';
      })();
    </script>
    
    <!-- Bootstrap 5.3 CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <!-- Bootstrap Icons -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
      rel="stylesheet"
    />
    <!-- Font Awesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
      /* Main Styles */
      :root {
        --sidebar-width: 250px;
        --sidebar-collapsed-width: 80px;
        --primary-color: #4A6FDC;
        --secondary-color: #6c757d;
        --success-color: #28a745;
        --info-color: #17a2b8;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --light-color: #f8f9fa;
        --dark-color: #343a40;
      }
      
      body {
        font-family: 'Nunito', sans-serif;
        background-color: #f8f9fa;
        overflow-x: hidden;
      }
      
      /* Flash Messages */
      .flash-messages-container {
        position: relative;
        z-index: 1030;
      }
      
      /* Sidebar Styles */
    </style>
  </head>
  <body>
    <div class="wrapper">
      <!-- Sidebar -->
      <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
          <div class="d-flex align-items-center">
            <h3>Menu</h3>
          </div>
          <button type="button" id="sidebarCollapseBtn" class="btn btn-link d-md-none">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <ul class="list-unstyled components">
          @auth
          <div class="sidebar-user-info mb-3 p-3 rounded shadow-sm">
            <div class="d-flex align-items-center user-info-container">
              <div class="user-avatar">
                <div class="rounded-circle bg-primary text-white p-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                  <i class="fas fa-user"></i>
                </div>
              </div>
              <div class="user-details ms-3">
                <p class="mb-0 fw-bold text-primary">{{ auth()->check() ? auth()->user()->name : 'Guest' }}</p>
                @php
                  $selectedLanguage = session('language', 'id');
                  $currentLevel = auth()->check() ? auth()->user()->getCurrentLevel($selectedLanguage) : 1;
                  $hasCompletedPretest = auth()->check() ? auth()->user()->hasCompletedPretest($selectedLanguage) : false;
                  $levelNames = [
                    1 => 'Beginner',
                    2 => 'Intermediate',
                    3 => 'Advanced'
                  ];
                  $levelName = $levelNames[$currentLevel] ?? '';
                  $userRole = auth()->check() ? auth()->user()->role : '';
                @endphp
                
                @if($userRole !== 'admin' && $userRole !== 'teacher')
                <p class="mb-0">Level <span class="badge rounded-pill bg-primary">{{ $hasCompletedPretest ? "$currentLevel ($levelName)" : '-' }}</span></p>
                <small class="text-light">{{ auth()->check() ? auth()->user()->pretestDate($selectedLanguage) : '' }}</small>
                @else
                <p class="mb-0">Role: <span class="badge rounded-pill bg-primary">{{ ucfirst($userRole) }}</span></p>
                @endif
              </div>
            </div>
          </div>
          @endauth

          @if(auth()->check() && auth()->user()->role === 'admin')
          <!-- Menu untuk admin -->
          <li class="sidebar-header">Administrasi</li>
          
          <li class="nav-item {{ request()->routeIs('admin.role.*') ? 'active' : '' }}">
            <a href="{{ route('admin.role.management') }}" class="nav-link d-flex align-items-center">
              <i class="fas fa-user-shield me-2"></i>
              <span>Manajemen Role</span>
            </a>
          </li>
          @elseif(auth()->check() && auth()->user()->role === 'teacher')
          <!-- Menu untuk teacher -->
          <li class="sidebar-header">Manajemen</li>
          
          <li class="nav-item {{ request()->routeIs('questions.*') ? 'active' : '' }}">
            <a href="{{ route('questions.index') }}" class="nav-link d-flex align-items-center">
              <i class="fas fa-question-circle me-2"></i>
              <span>Manajemen Soal</span>
            </a>
          </li>
          
          <li class="nav-item {{ request()->routeIs('teacher.materials') ? 'active' : '' }}">
            <a href="{{ route('teacher.materials') }}" class="nav-link d-flex align-items-center">
              <i class="fas fa-book-open me-2"></i>
              <span>Manajemen Materi</span>
            </a>
          </li>
          
          <li class="nav-item {{ request()->routeIs('teacher.students') ? 'active' : '' }}">
            <a href="{{ route('teacher.students') }}" class="nav-link d-flex align-items-center">
              <i class="fas fa-users me-2"></i>
              <span>Data Siswa</span>
            </a>
          </li>
          
          <li class="nav-item {{ request()->routeIs('teacher.test.*') ? 'active' : '' }}">
            <a href="{{ route('teacher.test.results') }}" class="nav-link d-flex align-items-center">
              <i class="fas fa-clipboard-list me-2"></i>
              <span>Hasil Test</span>
            </a>
          </li>

          <li class="nav-item {{ request()->routeIs('teacher.evaluation.*') ? 'active' : '' }}">
            <a href="{{ route('teacher.evaluation.settings') }}" class="nav-link d-flex align-items-center">
              <i class="fas fa-cog me-2"></i>
              <span>Pengaturan Evaluasi</span>
            </a>
          </li>
          @elseif(!auth()->check() || (auth()->check() && !auth()->user()->hasCompletedPretest(session('language', 'id')) && auth()->user()->role !== 'admin' && auth()->user()->role !== 'teacher'))
          <!-- Menu untuk user yang belum pretest untuk bahasa yang dipilih -->
          <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="nav-link">
              <i class="fas fa-home me-2"></i>
              <span>Beranda</span>
            </a>
          </li>
          
          <!-- Evaluasi Mandiri dihilangkan untuk user yang belum pretest -->
          
          <li class="nav-item {{ request()->routeIs('pretest') ? 'active' : '' }}">
            <a href="{{ route('pretest.language', ['language' => $selectedLanguage]) }}" class="nav-link highlight-menu">
              <i class="fas fa-check-circle me-2"></i>
              <span>Pretest</span>
              <span class="badge rounded-pill bg-warning text-dark ms-2">Wajib</span>
            </a>
          </li>
          
          @php
            $languageNames = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
            $languageName = $languageNames[$selectedLanguage] ?? 'Indonesia';
          @endphp

          @else
          <!-- Menu yang hanya muncul untuk siswa yang sudah pretest untuk bahasa yang dipilih -->
          <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="nav-link">
              <i class="fas fa-home me-2"></i>
              <span>Beranda</span>
            </a>
          </li>
          
          <li class="nav-item {{ request()->routeIs('evaluation') ? 'active' : '' }}">
            <a href="{{ route('evaluation') }}" class="nav-link">
              <i class="fas fa-chart-line me-2"></i>
              <span>Evaluasi Mandiri</span>
            </a>
          </li>
          
          @php
            $languageNames = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
            $languageName = $languageNames[$selectedLanguage] ?? 'Indonesia';
          @endphp

          
          <li class="sidebar-header">Pembelajaran</li>
          
          <li class="nav-item {{ request()->routeIs('learning.materials') ? 'active' : '' }}">
            <a href="{{ route('learning.materials') }}" class="nav-link d-flex align-items-center">
              <i class="fas fa-book me-2"></i>
              <span>Materi Pembelajaran</span>
            </a>
          </li>
          
          <li class="nav-item {{ request()->routeIs('post-test') ? 'active' : '' }}">
            <a href="{{ route('post-test.language', ['language' => $selectedLanguage]) }}" class="nav-link d-flex align-items-center">
              <i class="fas fa-graduation-cap me-2"></i>
              <span>Post Test</span>
            </a>
          </li>
          
          <li class="sidebar-header">Alat Bantu</li>
          
          <li class="nav-item {{ request()->routeIs('grammar') ? 'active' : '' }}">
            <a href="{{ route('grammar') }}" class="nav-link d-flex align-items-center">
              <i class="fas fa-pen me-2"></i>
              <span>Koreksi Tata Bahasa</span>
            </a>
          </li>
          
          <li class="nav-item {{ request()->routeIs('speech') ? 'active' : '' }}">
            <a href="{{ route('speech') }}" class="nav-link d-flex align-items-center">
              <i class="fas fa-microphone me-2"></i>
              <span>Speech Recognition</span>
            </a>
          </li>
          
          <li class="nav-item {{ request()->routeIs('virtual-tutor') ? 'active' : '' }}">
            <a href="{{ route('virtual-tutor') }}" class="nav-link d-flex align-items-center">
              <i class="fas fa-user-graduate me-2"></i>
              <span>Tutor Virtual</span>
            </a>
          </li>
          
          <li class="nav-item {{ request()->routeIs('youtube-transcription') ? 'active' : '' }}">
            <a href="{{ route('youtube-transcription') }}" class="nav-link d-flex align-items-center">
              <i class="fab fa-youtube me-2"></i>
              <span>YouTube Transcription</span>
            </a>
          </li>
          @endif
        </ul>

        <div class="sidebar-footer">
          <p class="text-muted small text-center">© {{ date('Y') }} Lab Bahasa AI</p>
        </div>
      </nav>

      <!-- Page Content -->
      <div id="content">
        <!-- Top Navbar -->
        <div class="navbar-container">
          <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
            <div class="container-fluid px-3">
              <button type="button" id="sidebarCollapse" class="btn btn-primary btn-sm d-flex align-items-center justify-content-center">
                <i class="fas fa-bars"></i>
              </button>
              <a class="navbar-brand ms-2 d-flex align-items-center " href="/">
                <i class="fas fa-language me-2 text-white"></i> <span class="text-white">Lab Bahasa</span>  
              </a>
              <div class="d-flex ms-auto me-2">

                
                @if(!auth()->check() || (auth()->check() && !auth()->user()->hasCompletedPretest(session('language', 'id')) && auth()->user()->role !== 'admin' && auth()->user()->role !== 'teacher'))
                <a href="{{ route('pretest.language', ['language' => session('language', 'id')]) }}" class="btn btn-warning btn-sm py-1 d-flex align-items-center">
                  <i class="fas fa-check-circle me-1"></i>
                  <span class="d-none d-sm-inline-block">Pretest</span>
                </a>
                @else
                <div class="dropdown">
                  <span class="badge bg-light text-dark d-flex align-items-center py-2 px-3" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex flex-column">
                      <span class="fw-bold text-primary d-flex align-items-center">
                      
                      </span>
                      <span class="d-flex align-items-center">
                    
                        @if(auth()->check() && auth()->user()->role === 'admin')
                        <span>Admin</span>
                        @elseif(auth()->check() && auth()->user()->role === 'teacher')
                        <span>Teacher</span>
                        @else
                        @php
                          $selectedLang = session('language', 'id');
                          $hasCompletedPretestNav = auth()->check() ? auth()->user()->hasCompletedPretest($selectedLang) : false;
                          $currentLevelNav = auth()->check() ? auth()->user()->getCurrentLevel($selectedLang) : 1;
                          $levelNames = [
                            1 => 'Beginner',
                            2 => 'Intermediate',
                            3 => 'Advanced'
                          ];
                          $levelName = $levelNames[$currentLevelNav] ?? '';
                        @endphp
                        <span>Level: {{ $hasCompletedPretestNav ? "$currentLevelNav ($levelName)" : '-' }}</span>
                        @endif
                      </span>
                    </div>
                  </span>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="fas fa-id-card me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                          <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </button>
                      </form>
                    </li>
                  </ul>
                </div>
                @endif
              </div>
            </div>
          </nav>
        </div>

        <div class="content-area">
          <div class="container-fluid mt-4">
            <!-- Flash Messages -->
            <div class="flash-messages-container mb-4">
              @if (session('success'))
                  <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                      {{ session('success') }}
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
              @endif

              @if (session('error'))
                  <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                      {{ session('error') }}
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
              @endif

              @if (session('info'))
                  <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                      {{ session('info') }}
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
              @endif

              @if (session('warning'))
                  <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                      {{ session('warning') }}
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
              @endif
            </div>
            
            @if(auth()->check() && !auth()->user()->hasCompletedPretest(session('language', 'id')) && !request()->routeIs('dashboard') && !request()->routeIs('pretest') && !request()->routeIs('evaluation') && auth()->user()->role !== 'admin' && auth()->user()->role !== 'teacher')
            <div class="alert alert-warning mb-4 shadow-sm">
              <h4 class="alert-heading fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Pretest Diperlukan!</h4>
              <p>Untuk mengakses halaman ini, Anda perlu menyelesaikan pretest terlebih dahulu untuk menentukan level kemampuan awal Anda.</p>
              <hr>
              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="{{ route('pretest.language', ['language' => session('language', 'id')]) }}" class="btn btn-success">Mulai Pretest Sekarang</a>
              </div>
            </div>
            @endif
            
            {{ $slot }}
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
  </body>
</html>