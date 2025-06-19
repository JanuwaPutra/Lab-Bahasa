<x-app-layout>
  <div class="container mt-5">
    @if(auth()->user() && !auth()->user()->hasCompletedPretest(session('language', 'id')))
    <div class="alert alert-warning mb-5">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Mulai dengan Pretest!</h4>
          <p>Untuk mengakses semua fitur Lab Bahasa AI, Anda perlu menyelesaikan pretest terlebih dahulu. Pretest ini akan menentukan level kemampuan bahasa Anda (Level 1-Beginner, Level 2-Intermediate, atau Level 3-Advanced) dan menyediakan materi pembelajaran sesuai level tersebut. Setelah mempelajari materi, Anda dapat mengambil post-test untuk naik ke level berikutnya dan membuka materi pembelajaran yang lebih lanjut.</p>
        </div>
        <div class="col-md-4 text-end">
          <a href="{{ route('pretest.language', ['language' => session('language', 'id')]) }}" class="btn btn-success btn-lg">Mulai Pretest Sekarang</a>
        </div>
      </div>
    </div>
    @endif

    <div class="row align-items-center mb-5">
      <div class="col-md-6">
        <h1 class="display-4 fw-bold">Lab Bahasa AI</h1>
        <p class="lead">Platform pembelajaran bahasa interaktif dengan teknologi AI.</p>
        <p class="mb-4">
          Tingkatkan kemampuan bahasa Anda dengan program pembelajaran adaptif yang disesuaikan dengan level kemampuan Anda.
        </p>
        
        <!-- Language Selector -->
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-language me-2"></i>Pilih Bahasa</h5>
          </div>
          <div class="card-body">
            <form action="{{ route('set-language') }}" method="POST" class="language-selector">
              @csrf
              <div class="row">
                <div class="col-md-8">
                  <select name="language" class="form-select" id="language-select">
                    <option value="id" {{ session('language', 'id') == 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
                    <option value="en" {{ session('language') == 'en' ? 'selected' : '' }}>English</option>
                    <option value="ru" {{ session('language') == 'ru' ? 'selected' : '' }}>Русский (Russian)</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <button type="submit" class="btn btn-primary w-100">Pilih</button>
                </div>
              </div>
              
              @if(auth()->user())
              <div class="mt-3">
                <div class="row">
                  @php
                    $languages = ['id', 'en', 'ru'];
                    $languageNames = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
                    $userRole = auth()->user()->role;
                  @endphp
                  
                  @if($userRole !== 'admin' && $userRole !== 'teacher')
                  @foreach($languages as $lang)
                    <div class="col-md-4 mb-2">
                      <div class="card {{ session('language', 'id') == $lang ? 'border-primary' : '' }}">
                        <div class="card-body p-2 text-center">
                          <h6 class="mb-1">{{ $languageNames[$lang] }}</h6>
                          @if(auth()->user()->hasCompletedPretest($lang))
                            @php
                              $userLevel = auth()->user()->getCurrentLevel($lang);
                              $levelNames = [
                                1 => 'Beginner',
                                2 => 'Intermediate',
                                3 => 'Advanced'
                              ];
                              $levelName = $levelNames[$userLevel] ?? '';
                            @endphp
                            <span class="badge bg-success">Level {{ $userLevel }} ({{ $levelName }})</span>
                          @else
                            <span class="badge bg-secondary">Belum Pretest</span>
                          @endif
                        </div>
                      </div>
                    </div>
                  @endforeach
                  @else
                  <div class="col-12">
                    <div class="alert alert-info mb-0">
                      <i class="fas fa-info-circle me-2"></i>
                      Anda login sebagai <strong>{{ ucfirst($userRole) }}</strong>. Anda memiliki akses penuh ke semua bahasa.
                    </div>
                  </div>
                  @endif
                </div>
              </div>
              @endif
            </form>
          </div>
        </div>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
          @php
            $selectedLanguage = session('language', 'id');
            $userRole = auth()->user()->role ?? '';
          @endphp
          
          @if($userRole === 'admin')
            <a href="{{ route('admin.role.management') }}" class="btn btn-primary btn-lg px-4">
              Manajemen Role
            </a>
          @elseif($userRole === 'teacher')
            <a href="{{ route('questions.index') }}" class="btn btn-primary btn-lg px-4">
              Manajemen Soal
            </a>
            <a href="{{ route('teacher.students') }}" class="btn btn-success btn-lg px-4">
              Data Siswa
            </a>
          @elseif(auth()->user() && auth()->user()->hasCompletedPretest($selectedLanguage))
            <a href="{{ route('learning.materials') }}" class="btn btn-primary btn-lg px-4">
              Materi Pembelajaran
            </a>
            <a href="{{ route('quiz.history.index') }}" class="btn btn-info btn-lg px-4">
              Riwayat Kuis
            </a>
            <a href="{{ route('post-test.language', ['language' => $selectedLanguage]) }}" class="btn btn-success btn-lg px-4">
              Ambil Post-Test
            </a>
          @else
            <a href="{{ route('pretest.language', ['language' => $selectedLanguage]) }}" class="btn btn-success btn-lg px-4">
              Mulai Pretest
            </a>
          @endif
          
          @if($userRole !== 'admin' && $userRole !== 'teacher')
          <a href="{{ route('evaluation') }}" class="btn btn-outline-secondary btn-lg px-4">
            Evaluasi Mandiri
          </a>
          @endif
        </div>
        
        @if(auth()->user() && auth()->user()->hasCompletedPretest($selectedLanguage) && auth()->user()->role !== 'admin' && auth()->user()->role !== 'teacher')
        <div class="alert alert-info mt-4">
          <div class="d-flex align-items-center">
            <div class="me-3">
              @php
                $currentLevel = auth()->user()->getCurrentLevel($selectedLanguage);
                $levelNames = [
                  1 => 'Beginner',
                  2 => 'Intermediate',
                  3 => 'Advanced'
                ];
                $levelName = $levelNames[$currentLevel] ?? '';
              @endphp
              <span class="badge bg-primary p-2">Level {{ $currentLevel }} ({{ $levelName }})</span>
            </div>
            <div>
              <h5 class="mb-0">Selamat datang kembali!</h5>
              <p class="mb-0">
                @php
                  $pretestDate = auth()->user()->pretestDate($selectedLanguage);
                  $languageNames = ['id' => 'Bahasa Indonesia', 'en' => 'Bahasa Inggris', 'ru' => 'Bahasa Rusia'];
                  $languageName = $languageNames[$selectedLanguage] ?? 'Bahasa Indonesia';
                @endphp
                @if($pretestDate == 'tanggal tidak tersedia')
                  Anda telah menyelesaikan pretest {{ $languageName }}.
                @else
                  Anda telah menyelesaikan pretest {{ $languageName }} pada {{ $pretestDate }}.
                @endif
              </p>
            </div>
          </div>
        </div>
        @elseif(auth()->user() && (auth()->user()->role === 'admin' || auth()->user()->role === 'teacher'))
        <div class="alert alert-info mt-4">
          <div class="d-flex align-items-center">
            <div class="me-3">
              <span class="badge bg-primary p-2">{{ ucfirst(auth()->user()->role) }}</span>
            </div>
            <div>
              <h5 class="mb-0">Selamat datang, {{ auth()->user()->name }}!</h5>
              <p class="mb-0">
                Anda memiliki akses penuh ke semua fitur dan bahasa di Lab Bahasa AI.
              </p>
            </div>
          </div>
        </div>
        @endif
      </div>
      <div class="col-md-6 text-center">
        <!-- Placeholder untuk gambar atau elemen visual -->
      </div>
    </div>

    <div class="row mb-5">
      <div class="col-md-12 text-center">
        <h2>Fitur Lab Bahasa AI</h2>
        <p class="lead">Berbagai alat AI untuk membantu Anda menguasai bahasa baru</p>
      </div>
    </div>

    <div class="row g-4 pb-5">
      <div class="col-md-4">
        <div class="card h-100 {{ (!auth()->user() || !auth()->user()->hasCompletedPretest($selectedLanguage)) ? 'border-success' : '' }}">
          <div class="card-body">
            <h5 class="card-title">
              <i class="fas fa-check-circle text-primary me-2"></i>
              Pretest & Post-test
            </h5>
            <p class="card-text">
              Mulai dengan pretest untuk menentukan level kemampuan Anda. Kemudian naik level melalui post-test untuk mengakses materi pembelajaran lanjutan.
            </p>
            @if(!auth()->user() || !auth()->user()->hasCompletedPretest($selectedLanguage))
            <a href="{{ route('pretest.language', ['language' => $selectedLanguage]) }}" class="btn btn-success">Mulai Pretest</a>
            @else
            <a href="{{ route('post-test.language', ['language' => $selectedLanguage]) }}" class="btn btn-sm btn-primary">Ambil Post-test</a>
            @endif
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card h-100 {{ (!auth()->user() || !auth()->user()->hasCompletedPretest($selectedLanguage)) ? 'text-muted' : '' }}">
          <div class="card-body">
            <h5 class="card-title">
              <i class="fas fa-pen text-primary me-2"></i>
              Koreksi Tata Bahasa
            </h5>
            <p class="card-text">
              Dapatkan koreksi instan untuk teks Anda dengan penjelasan mendalam tentang kesalahan dan saran perbaikan.
            </p>
            @if(auth()->user() && auth()->user()->hasCompletedPretest($selectedLanguage))
            <a href="{{ route('grammar') }}" class="btn btn-sm btn-primary">Buka Grammar Checker</a>
            @else
            <a href="{{ route('pretest.language', ['language' => $selectedLanguage]) }}" class="btn btn-sm btn-secondary">Selesaikan Pretest Dulu</a>
            @endif
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card h-100 {{ (!auth()->user() || !auth()->user()->hasCompletedPretest($selectedLanguage)) ? 'text-muted' : '' }}">
          <div class="card-body">
            <h5 class="card-title">
              <i class="fas fa-microphone text-primary me-2"></i>
              Latihan Pengucapan
            </h5>
            <p class="card-text">
              Latih pengucapan Anda dengan AI yang memberikan umpan balik langsung tentang akurasi dan kejelasan.
            </p>
            @if(auth()->user() && auth()->user()->hasCompletedPretest($selectedLanguage))
            <a href="{{ route('speech') }}" class="btn btn-sm btn-primary">Latih Pengucapan</a>
            @else
            <a href="{{ route('pretest.language', ['language' => $selectedLanguage]) }}" class="btn btn-sm btn-secondary">Selesaikan Pretest Dulu</a>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 pb-5">
      <div class="col-md-4">
        <div class="card h-100 {{ (!auth()->user() || !auth()->user()->hasCompletedPretest($selectedLanguage)) ? 'text-muted' : '' }}">
          <div class="card-body">
            <h5 class="card-title">
              <i class="fas fa-tasks text-primary me-2"></i>
              Pembelajaran Adaptif
            </h5>
            <p class="card-text">
              Soal-soal yang secara otomatis menyesuaikan dengan level kemampuan Anda, memberikan tantangan yang tepat.
            </p>
            @if(auth()->user() && auth()->user()->hasCompletedPretest($selectedLanguage))
            <a href="{{ route('learning') }}" class="btn btn-sm btn-primary">Mulai Belajar</a>
            @else
            <a href="{{ route('pretest.language', ['language' => $selectedLanguage]) }}" class="btn btn-sm btn-secondary">Selesaikan Pretest Dulu</a>
            @endif
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card h-100 {{ (!auth()->user() || !auth()->user()->hasCompletedPretest($selectedLanguage)) ? 'text-muted' : '' }}">
          <div class="card-body">
            <h5 class="card-title">
              <i class="fas fa-user-graduate text-primary me-2"></i>
              Tutor Virtual
            </h5>
            <p class="card-text">
              Berinteraksi dengan tutor AI yang dapat membantu Anda berlatih percakapan dan menjawab pertanyaan.
            </p>
            @if(auth()->user() && auth()->user()->hasCompletedPretest($selectedLanguage))
            <a href="{{ route('virtual-tutor') }}" class="btn btn-sm btn-primary">Bicara dengan Tutor</a>
            @else
            <a href="{{ route('pretest.language', ['language' => $selectedLanguage]) }}" class="btn btn-sm btn-secondary">Selesaikan Pretest Dulu</a>
            @endif
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card h-100 {{ (!auth()->user() || !auth()->user()->hasCompletedPretest($selectedLanguage)) ? 'text-muted' : '' }}">
          <div class="card-body">
            <h5 class="card-title">
              <i class="fas fa-chart-line text-primary me-2"></i>
              Evaluasi & Laporan Kemajuan
            </h5>
            <p class="card-text">
              Pantau kemajuan belajar Anda, identifikasi area yang perlu ditingkatkan, dan dapatkan rekomendasi personalisasi.
            </p>
            @if(auth()->user() && auth()->user()->hasCompletedPretest($selectedLanguage))
            <a href="{{ route('progress-report') }}" class="btn btn-sm btn-primary">Lihat Laporan</a>
            @else
            <a href="{{ route('pretest.language', ['language' => $selectedLanguage]) }}" class="btn btn-sm btn-secondary">Selesaikan Pretest Dulu</a>
            @endif
          
          </div>
        </div>
      </div>
    </div>
  </div>

  <style>
    .card-highlight {
      border-color: #28a745;
      box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
    }
    
    .display-4 {
      font-weight: 700;
      color: #212529;
    }
    
    .card {
      border-radius: 0.5rem;
    }
    
    .text-muted .card-title,
    .text-muted .card-text {
      opacity: 0.6;
    }
  </style>
  
  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Check if we're coming from post-test with level up
      const urlParams = new URLSearchParams(window.location.search);
      const fromPostTest = urlParams.get('from_post_test');
      const levelUp = urlParams.get('level_up');
      
      if (fromPostTest && levelUp === 'true') {
        // Force a refresh to ensure the latest level is displayed
        window.location.href = '{{ route("dashboard") }}';
      }
    });
  </script>
  @endpush
</x-app-layout> 