<x-app-layout>
  @push('styles')
  <style>
    .timer-display {
      font-size: 1.2rem;
      font-weight: bold;
    }
    .word-count {
      font-weight: bold;
    }
    /* Fullscreen mode styles */
    body.fullscreen-mode {
      overflow: hidden;
    }
    .fullscreen-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 9999;
      background-color: #fff;
      overflow-y: auto;
    }
    /* Question navigation styles */
    .question-nav {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 20px;
      justify-content: center;
    }
    .question-nav-item {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 35px;
      height: 35px;
      border-radius: 4px;
      font-weight: 500;
      cursor: pointer;
      background-color: #fff;
      border: 1px solid #dee2e6;
      transition: all 0.2s;
    }
    .question-nav-item:hover {
      background-color: #e9ecef;
    }
    .question-nav-item.active {
      background-color: #198754;
      color: white;
      border-color: #198754;
    }
    .question-nav-item.answered {
      background-color: #d1e7dd;
      border-color: #badbcc;
    }
    .question-nav-item.active.answered {
      background-color: #198754;
      color: white;
    }
  </style>
  @endpush
  
  <div class="container mt-4" id="post-test-container">
    <div class="row mb-4">
      <div class="col-md-12 text-center">
        @php
          $currentLevel = $level ?? auth()->user()->getCurrentLevel() ?? 1;
          $levelNames = [
            1 => 'Beginner',
            2 => 'Intermediate',
            3 => 'Advanced'
          ];
          $levelName = $levelNames[$currentLevel] ?? '';
        @endphp
        <h1>Post-test {{ $languageName ?? 'Bahasa Indonesia' }} - Level {{ $currentLevel }} ({{ $levelName }})</h1>
        <p class="lead">
          Selesaikan tes ini untuk menguji pemahaman Anda dan melihat apakah Anda siap untuk naik ke level berikutnya.
        </p>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8 col-md-10 mx-auto">
        @if(isset($questions) && count($questions) > 0)
        <!-- Konfirmasi Post-test -->
        <div class="card mb-4" id="confirmation-card">
          <div class="card-header bg-success text-white">
            <span>Konfirmasi Post-test {{ $languageName ?? 'Bahasa Indonesia' }}</span>
          </div>
          <div class="card-body">
            <div class="alert alert-warning">
              <h5><i class="fas fa-exclamation-triangle mr-2"></i> Perhatian!</h5>
              <p>Sebelum memulai post-test {{ $languageName ?? 'Bahasa Indonesia' }} level {{ $currentLevel }} ({{ $levelName }}), pastikan hal-hal berikut:</p>
              <ul>
                <li>Anda telah mempelajari semua materi {{ $languageName ?? 'Bahasa Indonesia' }} di level {{ $currentLevel }} ({{ $levelName }})</li>
                <li>Post-test ini memiliki batas waktu <strong>{{ $timeLimit ?? 45 }} menit</strong></li>
                <li>Timer akan terus berjalan bahkan jika halaman di-refresh</li>
                <li>Jawaban Anda akan tersimpan otomatis</li>
                <li>Anda membutuhkan skor minimal 70% untuk lulus</li>
              </ul>
            </div>
            <div class="d-grid gap-2">
              <button id="start-test-btn" class="btn btn-success btn-lg">
                <i class="fas fa-play-circle me-2"></i> Mulai Post-test
              </button>
            </div>
          </div>
        </div>

        <!-- Timer yang terlihat -->
        <div class="sticky-top pt-2 mb-3" style="top: 60px; z-index: 100;">
          <div class="card bg-light border-0 shadow-sm d-none" id="timer-card">
            <div class="card-body py-2 px-3">
              <div class="d-flex justify-content-between align-items-center">
                <strong>Sisa Waktu:</strong>
                <div class="timer-display">
                  <span id="timer-minutes">30</span>:<span id="timer-seconds">00</span>
                </div>
              </div>
              <div class="progress mt-1" style="height: 5px;">
                <div id="timer-progress" class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Question Navigation UI -->
        <div class="card mb-3 d-none" id="question-nav-card">
          <div class="card-header bg-light">
            <span>Navigasi Soal</span>
          </div>
          <div class="card-body py-3">
            <div class="question-nav" id="question-nav">
              @foreach($questions as $index => $question)
              <div class="question-nav-item {{ $index === 0 ? 'active' : '' }}" data-question-index="{{ $index }}">
                {{ $index + 1 }}
              </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="card d-none" id="test-card">
          <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <span>Soal Post-test Level {{ $currentLevel }} ({{ $levelName }})</span>
            <span class="badge bg-light text-success" id="question-counter">1 / {{ count($questions) }}</span>
          </div>
          <div class="card-body">
            <form id="post-test-form" action="{{ route('post-test.evaluate') }}" method="POST">
              @csrf
              <div id="questions-container">
                @foreach($questions as $index => $question)
                <div class="question-item mb-4 {{ $index > 0 ? 'd-none' : '' }}" data-question-id="{{ $question->id }}">
                  <h5 class="mb-3">{{ $index + 1 }}. {{ $question->text }}</h5>

                  @if($question->type == 'multiple_choice')
                  <div class="options-container">
                    @foreach($question->options as $optionIndex => $option)
                    <div class="form-check mb-2">
                      <input class="form-check-input" type="radio" name="question-{{ $question->id }}" id="option-{{ $question->id }}-{{ $optionIndex }}" value="{{ $optionIndex }}">
                      <label class="form-check-label" for="option-{{ $question->id }}-{{ $optionIndex }}">
                        {{ $option }}
                        @if(isset($question->option_scores) && isset($question->option_scores[$optionIndex]))
                        <span class="badge bg-secondary ms-2">{{ $question->option_scores[$optionIndex] > 0 ? '+' : '' }}{{ $question->option_scores[$optionIndex] }}</span>
                        @endif
                      </label>
                    </div>
                    @endforeach
                  </div>
                  
                  @elseif($question->type == 'true_false')
                  <div class="options-container">
                    <div class="form-check mb-2">
                      <input class="form-check-input" type="radio" name="question-{{ $question->id }}" id="option-{{ $question->id }}-true" value="true">
                      <label class="form-check-label" for="option-{{ $question->id }}-true">
                        Benar
                        @if(isset($question->option_scores) && isset($question->option_scores[0]))
                        <span class="badge bg-secondary ms-2">{{ $question->option_scores[0] > 0 ? '+' : '' }}{{ $question->option_scores[0] }}</span>
                        @endif
                      </label>
                    </div>
                    <div class="form-check mb-2">
                      <input class="form-check-input" type="radio" name="question-{{ $question->id }}" id="option-{{ $question->id }}-false" value="false">
                      <label class="form-check-label" for="option-{{ $question->id }}-false">
                        Salah
                        @if(isset($question->option_scores) && isset($question->option_scores[1]))
                        <span class="badge bg-secondary ms-2">{{ $question->option_scores[1] > 0 ? '+' : '' }}{{ $question->option_scores[1] }}</span>
                        @endif
                      </label>
                    </div>
                  </div>
                  
                  @elseif($question->type == 'essay')
                  <div class="form-group">
                    <textarea class="form-control" name="question-{{ $question->id }}" rows="5" placeholder="Tuliskan jawaban Anda di sini..."></textarea>
                    <small class="form-text text-muted">Min. {{ $question->min_words ?? 50 }} kata</small>
                    <div class="mt-2">
                      <span class="word-count">0</span> kata
                    </div>
                  </div>
                  
                  @elseif($question->type == 'fill_blank')
                  <div class="form-group">
                    <input type="text" class="form-control" name="question-{{ $question->id }}" placeholder="Masukkan kata yang tepat...">
                  </div>
                  @endif

                  <div class="d-flex justify-content-between mt-4">
                    @if($index > 0)
                    <button type="button" class="btn btn-secondary btn-prev">Sebelumnya</button>
                    @else
                    <div></div>
                    @endif

                    @if($index < count($questions) - 1)
                    <button type="button" class="btn btn-primary btn-next">Selanjutnya</button>
                    @else
                    <button type="button" class="btn btn-success btn-submit">Selesai & Kirim</button>
                    @endif
                  </div>
                </div>
                @endforeach
              </div>
            </form>
            
            <!-- Loading spinner -->
            <div id="loading-container" class="text-center d-none">
              <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2">Mengevaluasi jawaban Anda...</p>
            </div>
            
            <!-- Results container, initially hidden -->
            <div id="result-container" class="d-none">
              <h4 class="mb-3">Hasil Post-test</h4>
              <div id="result-alert" class="alert">
                <p>Berdasarkan jawaban Anda:</p>
                <p id="result-message" class="fw-bold"></p>
                <div class="row mt-3">
                  <div class="col-md-6">
                    <div class="card bg-light mb-3">
                      <div class="card-body p-3">
                        <h5 class="card-title mb-2">Skor Perolehan</h5>
                        <h2 class="mb-0"><span id="result-score" class="fw-bold"></span> / <span id="result-total-points"></span></h2>
                        <div class="progress mt-2" style="height: 10px;">
                          <div id="score-progress" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="card bg-light mb-3">
                      <div class="card-body p-3">
                        <h5 class="card-title mb-2">Jawaban Benar</h5>
                        <h2 class="mb-0"><span id="result-correct-count" class="fw-bold"></span> / <span id="result-total-questions"></span></h2>
                        <div class="progress mt-2" style="height: 10px;">
                          <div id="correct-progress" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <p class="mt-3 mb-0">Persentase: <span id="result-percentage" class="fw-bold"></span>%</p>
              </div>
              <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('learning.materials') }}" class="btn btn-primary" id="back-to-learning-btn">Kembali ke Pembelajaran</a>
                <a id="retry-button" href="{{ route('post-test') }}?reset=true" class="btn btn-success d-none">
                  <i class="fas fa-redo me-2"></i> Coba Lagi
                </a>
              </div>
            </div>
          </div>
        </div>
        @else
        <div class="card">
          <div class="card-header bg-success text-white">
            <span>Post-test Level {{ $level ?? auth()->user()->getCurrentLevel() ?? 1 }}</span>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <p>Tidak ada soal post-test tersedia untuk level ini saat ini.</p>
              <p>Anda dapat melanjutkan pembelajaran di level berikutnya.</p>
            </div>
            <a href="{{ route('learning.materials') }}" class="btn btn-primary">Kembali ke Pembelajaran</a>
          </div>
        </div>
        @endif
      </div>
      
      <div class="col-lg-4 col-md-10 mx-auto mt-4 mt-lg-0">
        <div class="card mb-4" id="info-card">
          <div class="card-header bg-info text-white">Informasi Post-test</div>
          <div class="card-body">
            <p>Tes ini mengevaluasi pemahaman Anda tentang materi di level {{ $currentLevel }} ({{ $levelName }}).</p>
            <p><strong>Kriteria kelulusan:</strong> Anda perlu mendapatkan skor minimal 70% untuk naik ke level berikutnya.</p>
            
            @if($currentLevel < 3)
            @php
              $nextLevelName = $levelNames[$currentLevel + 1] ?? '';
            @endphp
            <p>Jika Anda lulus, Anda akan naik ke level {{ $currentLevel + 1 }} ({{ $nextLevelName }}).</p>
            @else
            <p>Ini adalah level tertinggi. Jika Anda lulus, Anda telah menguasai materi dengan baik.</p>
            @endif
          </div>
        </div>
        
        <div class="card" id="tips-card">
          <div class="card-header bg-warning text-white">Tips</div>
          <div class="card-body">
            <ul>
              <li>Pastikan Anda telah mempelajari semua materi di level ini</li>
              <li>Jawab semua pertanyaan dengan teliti</li>
              <li>Untuk soal esai, perhatikan jumlah kata minimal</li>
              <li><strong>Ada batas waktu {{ $timeLimit ?? 45 }} menit</strong> untuk menyelesaikan post-test</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Variables
      let currentQuestionIndex = 0;
      const questionItems = document.querySelectorAll('.question-item');
      
      // If no questions are available, exit early
      if (questionItems.length === 0) return;
      
      const totalQuestions = questionItems.length;
      const questionCounter = document.getElementById('question-counter');
      const postTestForm = document.getElementById('post-test-form');
      const loadingContainer = document.getElementById('loading-container');
      const resultContainer = document.getElementById('result-container');
      const questionsContainer = document.getElementById('questions-container');
      const retryButton = document.getElementById('retry-button');
      const confirmationCard = document.getElementById('confirmation-card');
      const testCard = document.getElementById('test-card');
      const timerCard = document.getElementById('timer-card');
      const startTestBtn = document.getElementById('start-test-btn');
      const timerMinutes = document.getElementById('timer-minutes');
      const timerSeconds = document.getElementById('timer-seconds');
      const timerProgress = document.getElementById('timer-progress');
      
      // Waktu tes dalam detik, ambil dari database atau gunakan default
      const TEST_TIME = {{ isset($timeLimit) ? $timeLimit * 60 : 45 * 60 }}; // minutes in seconds
      const STORAGE_PREFIX = 'post_test_level_{{ $level ?? auth()->user()->getCurrentLevel() ?? 1 }}_';
      
      // Server connectivity tracking
      let serverErrorCount = 0;
      const MAX_SERVER_ERRORS = 3; // After this many errors, we'll assume server is unreachable
      let isOfflineMode = false;
      
      // Test state tracking
      let testInProgress = false;
      
      // Function to disable beforeunload warning
      function disableBeforeUnloadWarning() {
        testInProgress = false;
      }
      
      // Function to handle server errors
      function handleServerError(error, operation) {
        console.error(`Error during ${operation}:`, error);
        
        serverErrorCount++;
        
        // If we've had too many server errors, show a warning but continue
        if (serverErrorCount >= MAX_SERVER_ERRORS && !isOfflineMode) {
          console.warn(`Reached maximum server errors (${MAX_SERVER_ERRORS}). Switching to offline mode.`);
          isOfflineMode = true;
          
          // Show a non-blocking notification
          const notification = document.createElement('div');
          notification.style.position = 'fixed';
          notification.style.bottom = '20px';
          notification.style.right = '20px';
          notification.style.backgroundColor = 'rgba(255, 193, 7, 0.9)';
          notification.style.color = '#000';
          notification.style.padding = '10px 20px';
          notification.style.borderRadius = '5px';
          notification.style.zIndex = '9999';
          notification.style.maxWidth = '300px';
          notification.innerHTML = '<strong>Peringatan:</strong> Koneksi ke server terganggu. Tes akan dilanjutkan dalam mode offline. Jawaban Anda akan disimpan secara lokal.';
          
          document.body.appendChild(notification);
          
          // Remove notification after 5 seconds
          setTimeout(() => {
            if (notification.parentNode) {
              notification.parentNode.removeChild(notification);
            }
          }, 5000);
        }
        
        // If it looks like a CSRF token issue, try to refresh it
        if (error.message && (error.message.includes('419') || error.message.includes('CSRF') || error.message.includes('token'))) {
          return refreshCsrfToken();
        }
        
        return Promise.resolve(); // Continue the flow
      }
      
      // Cek apakah tes sudah dimulai sebelumnya
      function checkTestStatus() {
        const startTime = localStorage.getItem(`${STORAGE_PREFIX}start_time`);
        const isStarted = localStorage.getItem(`${STORAGE_PREFIX}is_started`);
        const answers = localStorage.getItem(`${STORAGE_PREFIX}answers`);
        
        // Check URL for reset parameter
        const urlParams = new URLSearchParams(window.location.search);
        const resetParam = urlParams.get('reset');
        
        // If reset parameter is present, clear everything and start fresh
        if (resetParam === 'true') {
          console.log('Reset parameter detected, starting fresh test');
          resetTestState();
          // Show confirmation card instead of starting test automatically
          confirmationCard.classList.remove('d-none');
          testCard.classList.add('d-none');
          timerCard.classList.add('d-none');
          document.getElementById('question-nav-card').classList.add('d-none');
          
          // Show information and tips cards
          const infoCard = document.getElementById('info-card');
          const tipsCard = document.getElementById('tips-card');
          
          if (infoCard) infoCard.classList.remove('d-none');
          if (tipsCard) tipsCard.classList.remove('d-none');
          
          return;
        }
        
        if (isStarted && startTime) {
          // Test sudah dimulai sebelumnya
          startTest(false);
          
          // Selalu coba ambil jawaban terbaru dari server terlebih dahulu
          fetch('{{ route('post-test.get-answers') }}?language={{ $language ?? 'id' }}', {
            headers: {
              'Accept': 'application/json'
            }
          })
          .then(response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
              return response.json();
            } else {
              // If not JSON, session might have expired - refresh CSRF token
              return refreshCsrfToken().then(() => {
                throw new Error('Session might have expired when getting answers, CSRF token refreshed');
              });
            }
          })
          .then(data => {
            console.log('Fetched latest answers from server:', data);
            
            if (data.success && data.answers) {
              // Restore jawaban dari server
              restoreAnswers(data.answers);
              serverErrorCount = 0; // Reset error count on success
            } else if (answers) {
              // Fallback ke localStorage jika server tidak punya data
              console.log('Using localStorage answers as fallback');
              restoreAnswers(JSON.parse(answers));
            }
          })
          .catch(error => {
            handleServerError(error, 'fetching latest answers');
            
            // Fallback ke localStorage jika ada error
            if (answers) {
              console.log('Using localStorage answers due to error');
              restoreAnswers(JSON.parse(answers));
            }
          });
        }
      }
      
      // Reset test state completely
      function resetTestState() {
        // Clear test data from local storage
        localStorage.removeItem(`${STORAGE_PREFIX}start_time`);
        localStorage.removeItem(`${STORAGE_PREFIX}is_started`);
        localStorage.removeItem(`${STORAGE_PREFIX}answers`);
        
        // Reset any form elements
        const formElements = document.querySelectorAll('input[type="radio"], input[type="text"], textarea');
        formElements.forEach(element => {
          if (element.type === 'radio') {
            element.checked = false;
          } else {
            element.value = '';
          }
        });
        
        // Reset word counts for essays
        document.querySelectorAll('.word-count').forEach(element => {
          element.textContent = '0';
        });
        
        // Reset navigation
        document.querySelectorAll('.question-nav-item').forEach(item => {
          item.classList.remove('answered');
          item.classList.remove('active');
          // Reset the first item to active
          if (item.getAttribute('data-question-index') === '0') {
            item.classList.add('active');
          }
        });
        
        // Reset current question index
        currentQuestionIndex = 0;
        
        // Reset question counter
        if (questionCounter) {
          questionCounter.textContent = `1 / ${totalQuestions}`;
        }
        
        // Show first question, hide others
        questionItems.forEach((item, index) => {
          if (index === 0) {
            item.classList.remove('d-none');
          } else {
            item.classList.add('d-none');
          }
        });
        
        // Get fresh CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Juga reset data jawaban di server
        // Force clear parameter prevents redirect
        fetch('{{ route('post-test.save-answers') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            answers: {}, // Use an empty object
            language: '{{ $language ?? "id" }}',
            force_clear: true,
            terminate_test: true // Add this flag to explicitly terminate the test in monitoring
          })
        })
        .then(response => {
          // Check if response is JSON
          const contentType = response.headers.get('content-type');
          if (contentType && contentType.includes('application/json')) {
            return response.json();
          } else {
            // If not JSON, session might have expired - refresh CSRF token
            return refreshCsrfToken().then(() => {
              console.log('Session might have expired when clearing answers, CSRF token refreshed');
              return { success: false };
            });
          }
        })
        .then(data => {
          console.log('Answers cleared on server');
          serverErrorCount = 0; // Reset error count on success
        })
        .catch(error => {
          handleServerError(error, 'clearing answers on server');
          // No need to alert user or retry here
        });
      }
      
      // Start the test
      function startTest(isNew = true) {
        confirmationCard.classList.add('d-none');
        testCard.classList.remove('d-none');
        timerCard.classList.remove('d-none');
        document.getElementById('question-nav-card').classList.remove('d-none');
        
        // Set test in progress flag
        testInProgress = true;
        
        // Hide information and tips cards
        const infoCard = document.getElementById('info-card');
        const tipsCard = document.getElementById('tips-card');
        
        if (infoCard) infoCard.classList.add('d-none');
        if (tipsCard) tipsCard.classList.add('d-none');
        
        if (isNew) {
          // Reset any previous test state
          resetTestState();
          
          // Reset all form inputs
          document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.checked = false;
          });
          
          document.querySelectorAll('input[type="text"], textarea').forEach(input => {
            input.value = '';
          });
          
          // Reset word counts
          document.querySelectorAll('.word-count').forEach(count => {
            count.textContent = '0';
          });
          
          // Reset navigation markers
          document.querySelectorAll('.question-nav-item').forEach(item => {
            item.classList.remove('answered');
          });
          
          // Set start time
          const currentTime = Math.floor(Date.now() / 1000);
          localStorage.setItem(`${STORAGE_PREFIX}start_time`, currentTime);
          localStorage.setItem(`${STORAGE_PREFIX}is_started`, 'true');
          localStorage.setItem(`${STORAGE_PREFIX}answers`, JSON.stringify({}));
          
          // Get fresh CSRF token
          const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
          
          // Make an explicit reset_attempt call to ensure the test is properly registered
          console.log('Starting new post-test with reset_attempt=true');
          
          // Send the reset_attempt request
          fetch('{{ route('post-test.save-answers') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              answers: {}, // Use an object instead of an array
              language: '{{ $language ?? "id" }}',
              reset_attempt: true // Signal to server this is a new attempt
            })
          })
          .then(response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
              return response.json();
            } else {
              // If not JSON, session might have expired - refresh CSRF token
              return refreshCsrfToken().then(() => {
                throw new Error('Session might have expired when saving start time, CSRF token refreshed');
              });
            }
          })
          .then(data => {
            console.log('Start time saved to server');
            serverErrorCount = 0; // Reset error count on success
          })
          .catch(error => {
            handleServerError(error, 'saving start time');
            // Continue anyway, we have the start time in localStorage
          });
        }
        
        startTimer();
        updateNavigation();
      }
      
      // Update navigation buttons and question counter
      function updateNavigation() {
        if (questionCounter) {
          questionCounter.textContent = `${currentQuestionIndex + 1} / ${totalQuestions}`;
        }
        
        // Show current question, hide others
        questionItems.forEach((item, index) => {
          if (index === currentQuestionIndex) {
            item.classList.remove('d-none');
          } else {
            item.classList.add('d-none');
          }
        });
        
        // Update question navigation UI
        const navItems = document.querySelectorAll('.question-nav-item');
        navItems.forEach((item, index) => {
          item.classList.toggle('active', index === currentQuestionIndex);
        });
      }
      
      // Timer function
      function startTimer() {
        // Get the start time from local storage
        const startTime = parseInt(localStorage.getItem(`${STORAGE_PREFIX}start_time`));
        
        // Sync with server immediately at startup
        syncTimeWithServer();
        
        // Set timer interval
        window.timerInterval = setInterval(() => {
          // Get the current time and calculate remaining time
          const currentTime = Math.floor(Date.now() / 1000);
          const elapsedSeconds = Math.max(0, currentTime - startTime);
          const timeLimit = {{ isset($timeLimit) ? $timeLimit * 60 : 45 * 60 }};
          const remainingSeconds = Math.max(0, timeLimit - elapsedSeconds);
          
          // Update the timer display
          updateTimerDisplay(remainingSeconds);
          
          // Save progress more frequently (every 5 seconds) to keep real-time monitoring accurate
          if (elapsedSeconds % 5 === 0) {
            saveAnswers();
          }
          
          // Sync with server every 10 seconds
          if (elapsedSeconds % 10 === 0) {
            syncTimeWithServer();
          }
          
          // Check if time's up
          if (remainingSeconds <= 0) {
            clearInterval(window.timerInterval);
            window.timerInterval = null;
            submitTest(true);
          }
        }, 1000);
      }
      
      // Update timer display
      function updateTimerDisplay(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        
        timerMinutes.textContent = mins.toString().padStart(2, '0');
        timerSeconds.textContent = secs.toString().padStart(2, '0');
        
        // Update progress bar
        const percentage = (seconds / TEST_TIME) * 100;
        timerProgress.style.width = `${percentage}%`;
        
        // Change color based on time remaining
        if (percentage < 25) {
          timerProgress.className = 'progress-bar bg-danger';
        } else if (percentage < 50) {
          timerProgress.className = 'progress-bar bg-warning';
        } else {
          timerProgress.className = 'progress-bar bg-success';
        }
      }
      
      // Sync time with server
      function syncTimeWithServer() {
        // Fetch current server time and update timer accordingly
        fetch('{{ route('post-test.get-time') }}?language={{ $language ?? 'id' }}', {
          headers: {
            'Accept': 'application/json'
          }
        })
        .then(response => {
          // Check if response is JSON
          const contentType = response.headers.get('content-type');
          if (contentType && contentType.includes('application/json')) {
            return response.json();
          } else {
            // If not JSON, session might have expired - refresh CSRF token
            return refreshCsrfToken().then(() => {
              throw new Error('Session might have expired during time sync, CSRF token refreshed');
            });
          }
        })
        .then(data => {
          if (data.success && data.start_time) {
            // If server has a valid start time, update our timer
            if (data.start_time > 0) {
              // Update localStorage with server's start time
              localStorage.setItem(`${STORAGE_PREFIX}start_time`, data.start_time);
              
              // Use direct values from the server when available
              let newRemaining = data.remaining_seconds;
              
              console.log('Time sync with server:', {
                serverStartTime: new Date(data.start_time * 1000).toLocaleTimeString(),
                currentServerTime: new Date(data.current_server_time * 1000).toLocaleTimeString(),
                elapsedSeconds: data.elapsed_seconds,
                elapsedFormatted: Math.floor(data.elapsed_seconds / 60) + ':' + String(data.elapsed_seconds % 60).padStart(2, '0'),
                remainingSeconds: data.remaining_seconds,
                remainingFormatted: Math.floor(data.remaining_seconds / 60) + ':' + String(data.remaining_seconds % 60).padStart(2, '0')
              });
              
              // Force update display immediately
              updateTimerDisplay(newRemaining);
              
              // Also save this remaining time to the database for monitoring
              saveRemainingTime(newRemaining);
              
              // If time's up according to server, submit the test
              if (newRemaining <= 0) {
                clearInterval(window.timerInterval);
                window.timerInterval = null;
                submitTest(true);
              }
            }
            
            // Reset server error count on successful sync
            serverErrorCount = 0;
          }
        })
        .catch(error => {
          handleServerError(error, 'syncing time with server');
          // Don't interrupt the test flow, just continue with local time
        });
      }
      
      // Function to save only the remaining time to the database
      function saveRemainingTime(remainingSeconds) {
        // Get the answers data from localStorage
        const answers = JSON.parse(localStorage.getItem(`${STORAGE_PREFIX}answers`) || '{}');
        
        // Add remaining seconds to answers object
        answers['_remaining_seconds'] = remainingSeconds;
        
        // Save back to localStorage
        localStorage.setItem(`${STORAGE_PREFIX}answers`, JSON.stringify(answers));
        
        // Get fresh CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Send to server
        fetch('{{ route('post-test.save-answers') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            answers: answers,
            language: '{{ $language ?? "id" }}',
            completed: false,
            remaining_seconds: remainingSeconds
          })
        })
        .catch(error => {
          console.error('Error saving remaining time:', error);
          // Just log the error, don't block the test flow
        });
      }
      
      // Next and previous button handlers
      document.querySelectorAll('.btn-next').forEach(btn => {
        btn.addEventListener('click', () => {
          saveAnswers();
          if (currentQuestionIndex < totalQuestions - 1) {
            currentQuestionIndex++;
            updateNavigation();
          }
        });
      });
      
      document.querySelectorAll('.btn-prev').forEach(btn => {
        btn.addEventListener('click', () => {
          saveAnswers();
          if (currentQuestionIndex > 0) {
            currentQuestionIndex--;
            updateNavigation();
          }
        });
      });
      
      // Start test button
      if (startTestBtn) {
        startTestBtn.addEventListener('click', () => {
          // Reset any previous test state first
          resetTestState();
          
          // Start the test as new
          startTest(true);
        });
      }
      
      // Word count for essays
      document.querySelectorAll('textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
          const wordCount = this.value.trim().split(/\s+/).filter(Boolean).length;
          const wordCountElement = this.closest('.form-group').querySelector('.word-count');
          if (wordCountElement) {
            wordCountElement.textContent = wordCount;
          }
        });
      });
      
      // Submit button
      document.querySelectorAll('.btn-submit').forEach(btn => {
        btn.addEventListener('click', () => {
          submitTest();
        });
      });
      
      // Save answers to local storage and server
      function saveAnswers(isCompleted = false) {
        const answers = {};
        
        questionItems.forEach(item => {
          const questionId = item.getAttribute('data-question-id');
          const inputElement = item.querySelector(`[name="question-${questionId}"]`);
          
          if (inputElement) {
            if (inputElement.type === 'radio') {
              const checkedInput = item.querySelector(`[name="question-${questionId}"]:checked`);
              if (checkedInput) {
                answers[questionId] = checkedInput.value;
                
                // Mark as answered in the navigation
                const questionIndex = Array.from(questionItems).indexOf(item);
                const navItem = document.querySelector(`.question-nav-item[data-question-index="${questionIndex}"]`);
                if (navItem) {
                  navItem.classList.add('answered');
                }
              }
            } else {
              answers[questionId] = inputElement.value;
              
              // For text inputs and textareas, mark as answered if there's any content
              if (inputElement.value.trim()) {
                const questionIndex = Array.from(questionItems).indexOf(item);
                const navItem = document.querySelector(`.question-nav-item[data-question-index="${questionIndex}"]`);
                if (navItem) {
                  navItem.classList.add('answered');
                }
              }
            }
          }
        });
        
        // Save to localStorage as backup
        localStorage.setItem(`${STORAGE_PREFIX}answers`, JSON.stringify(answers));
        
        // Get fresh CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Always send an object, even if empty
        const submittableAnswers = Object.keys(answers).length > 0 ? answers : {};
        
        // Get current time and calculate remaining time
        const startTime = parseInt(localStorage.getItem(`${STORAGE_PREFIX}start_time`) || '0');
        const currentTime = Math.floor(Date.now() / 1000);
        const elapsedSeconds = Math.max(0, currentTime - startTime);
        const timeLimit = {{ isset($timeLimit) ? $timeLimit * 60 : 45 * 60 }};
        const remainingSeconds = isCompleted ? 0 : Math.max(0, timeLimit - elapsedSeconds);
        
        // Add metadata to answers for monitoring
        submittableAnswers['_remaining_seconds'] = remainingSeconds;
        
        if (isCompleted) {
          submittableAnswers['_completed'] = true;
        }
        
        // Save to server untuk persistensi
        fetch('{{ route('post-test.save-answers') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            answers: submittableAnswers,
            language: '{{ $language ?? "id" }}',
            completed: isCompleted,
            remaining_seconds: remainingSeconds
          })
        })
        .then(response => {
          // Check if response is JSON
          const contentType = response.headers.get('content-type');
          if (contentType && contentType.includes('application/json')) {
            return response.json();
          } else {
            // If not JSON, session might have expired - refresh CSRF token
            return refreshCsrfToken().then(() => {
              throw new Error('Session might have expired, CSRF token refreshed');
            });
          }
        })
        .then(data => {
          console.log('Answers saved to server successfully');
          serverErrorCount = 0; // Reset error count on success
        })
        .catch(error => {
          handleServerError(error, 'saving answers');
          // Don't alert the user, just keep the answers in localStorage as backup
        });
      }
      
      // Function to refresh CSRF token
      function refreshCsrfToken() {
        return fetch('{{ route('refresh-csrf-token') }}', {
          headers: {
            'Accept': 'application/json'
          }
        })
          .then(response => response.json())
          .then(data => {
            if (data.token) {
              // Update all CSRF tokens in the page
              document.querySelectorAll('meta[name="csrf-token"]').forEach(meta => {
                meta.setAttribute('content', data.token);
              });
              document.querySelectorAll('input[name="_token"]').forEach(input => {
                input.value = data.token;
              });
              console.log('CSRF token refreshed');
            }
          })
          .catch(error => {
            console.error('Failed to refresh CSRF token:', error);
          });
      }
      
      // Submit test
      function submitTest(isTimeUp = false) {
        // Save answers with completed flag
        saveAnswers(true);
        
        // Clear test in progress flag
        testInProgress = false;
        
        questionsContainer.classList.add('d-none');
        loadingContainer.classList.remove('d-none');
        
        // Hide the timer card
        const timerCard = document.getElementById('timer-card');
        if (timerCard) {
          timerCard.classList.add('d-none');
        }
        
        // Stop the timer
        if (window.timerInterval) {
          clearInterval(window.timerInterval);
          window.timerInterval = null;
        }
        
        // Get the answers data
        const answers = JSON.parse(localStorage.getItem(`${STORAGE_PREFIX}answers`) || '{}');
        
        // Debug log
        console.log('Submitting answers:', answers);
        
        // Get fresh CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Force delete any active tests first
        forceMarkTestCompleted(csrfToken)
          .then(() => {
            // Continue with submission after force marking test as completed
            continueSubmission(answers, isTimeUp, csrfToken);
          })
          .catch(error => {
            console.error('Error during force completion:', error);
            // Continue anyway
            continueSubmission(answers, isTimeUp, csrfToken);
          });
      }
      
      // Force mark test as completed (separate request)
      function forceMarkTestCompleted(csrfToken) {
        console.log('Force marking test as completed');
        
        return fetch('{{ route('post-test.save-answers') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            answers: { '_completed': true, '_remaining_seconds': 0 },
            language: '{{ $language ?? "id" }}',
            completed: true,
            remaining_seconds: 0,
            force_complete: true,  // Special flag for backend
            terminate_test: true   // New flag to ensure removal from monitoring
          })
        })
        .then(response => {
          if (!response.ok) {
            throw new Error(`Force mark completed failed: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          console.log('Force mark completed response:', data);
          return data;
        });
      }
      
      // Continue with the submission process
      function continueSubmission(answers, isTimeUp, csrfToken) {
        // Check if answers is empty and it's not a time-up submission
        const hasAnswers = Object.keys(answers).length > 0;
        if (!hasAnswers && !isTimeUp) {
          // Show warning for empty submission
          loadingContainer.classList.add('d-none');
          resultContainer.classList.remove('d-none');
          
          const resultAlert = document.getElementById('result-alert');
          const resultMessage = document.getElementById('result-message');
          
          resultAlert.className = 'alert alert-warning';
          resultMessage.textContent = 'Anda belum menjawab pertanyaan apapun. Silakan coba lagi.';
          retryButton.classList.remove('d-none');
          
          document.getElementById('result-score').textContent = '0';
          document.getElementById('result-total-points').textContent = '{{ count($questions) }}';
          document.getElementById('result-percentage').textContent = '0';
          document.getElementById('result-correct-count').textContent = '0';
          document.getElementById('result-total-questions').textContent = '{{ count($questions) }}';
          
          document.getElementById('score-progress').style.width = '0%';
          document.getElementById('correct-progress').style.width = '0%';
          
          return;
        }
        
        // Set a timeout to handle cases where the fetch might hang
        const submissionTimeout = setTimeout(() => {
          console.warn('Submission request timed out');
          loadingContainer.classList.add('d-none');
          resultContainer.classList.remove('d-none');
          
          const resultAlert = document.getElementById('result-alert');
          const resultMessage = document.getElementById('result-message');
          
          resultAlert.className = 'alert alert-danger';
          resultMessage.textContent = 'Terjadi kesalahan saat mengevaluasi jawaban. Silakan coba lagi nanti.';
          retryButton.classList.remove('d-none');
          
          document.getElementById('result-score').textContent = '0';
          document.getElementById('result-total-points').textContent = '{{ count($questions) }}';
          document.getElementById('result-percentage').textContent = '0';
          document.getElementById('result-correct-count').textContent = '0';
          document.getElementById('result-total-questions').textContent = '{{ count($questions) }}';
          
          document.getElementById('score-progress').style.width = '0%';
          document.getElementById('correct-progress').style.width = '0%';
          
          // Reset test state in local storage
          localStorage.removeItem(`${STORAGE_PREFIX}start_time`);
          localStorage.removeItem(`${STORAGE_PREFIX}is_started`);
          localStorage.removeItem(`${STORAGE_PREFIX}answers`);
        }, 20000); // 20 seconds timeout
        
        // Get current time and calculate remaining time
        const startTime = parseInt(localStorage.getItem(`${STORAGE_PREFIX}start_time`) || '0');
        const currentTime = Math.floor(Date.now() / 1000);
        const elapsedSeconds = Math.max(0, currentTime - startTime);
        const timeLimit = {{ isset($timeLimit) ? $timeLimit * 60 : 45 * 60 }};
        const remainingSeconds = Math.max(0, timeLimit - elapsedSeconds);
        
        // Always send an object, even if empty
        const submittableAnswers = hasAnswers ? answers : {};
        
        // Add metadata to answers to mark as completed
        submittableAnswers['_completed'] = true;
        submittableAnswers['_remaining_seconds'] = 0; // Set to 0 when completed
        
        // Submit answers directly without retry mechanism
        fetch('{{ route("post-test.evaluate") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({ 
            answers: submittableAnswers,
            language: '{{ $language ?? "id" }}', // Use language from view
            time_expired: isTimeUp,
            completed: true,  // Explicitly mark as completed
            remaining_seconds: 0  // Set to 0 when completed
          })
        })
        .then(response => {
          // Log the response status for debugging
          console.log('Server response status:', response.status, response.statusText);
          
          // Check if response is ok (status in the range 200-299)
          if (!response.ok) {
            throw new Error(`Server returned ${response.status}: ${response.statusText}`);
          }
          
          // Check if content type is JSON
          const contentType = response.headers.get('content-type');
          console.log('Response content type:', contentType);
          
          if (!contentType || !contentType.includes('application/json')) {
            throw new Error(`Expected JSON but got ${contentType || 'unknown content type'}`);
          }
          
          return response.json();
        })
        .then(data => {
          // Clear the timeout since we got a response
          clearTimeout(submissionTimeout);
          
          // Check if the response indicates an error
          if (!data.success) {
            console.error('Server returned error in JSON response:', data.message || 'Unknown error');
            throw new Error(data.message || 'Server error occurred during evaluation');
          }
          
          loadingContainer.classList.add('d-none');
          resultContainer.classList.remove('d-none');
          
          // Force scroll to top to ensure results are visible
          window.scrollTo(0, 0);
          document.body.scrollTop = 0;
          document.documentElement.scrollTop = 0;
          
          // Extra call to ensure test is really completed
          forceMarkTestCompleted(csrfToken)
            .then(() => console.log('Final force complete successful'))
            .catch(e => console.error('Final force complete failed:', e));
            
          // Display result
          const resultAlert = document.getElementById('result-alert');
          const resultMessage = document.getElementById('result-message');
          
          // Clear previous classes
          resultAlert.className = 'alert';
          
          if (data.passed) {
            resultAlert.classList.add('alert-success');
            resultMessage.textContent = 'Selamat! Anda lulus post-test ini.';
            
            if (data.level_up) {
              resultMessage.textContent += ' Anda telah naik ke level berikutnya!';
              
              // Redirect to dashboard after 3 seconds to show the updated level
              setTimeout(() => {
                window.location.href = '{{ route("dashboard") }}?from_post_test=true&level_up=true';
              }, 3000);
            }
          } else {
            resultAlert.classList.add('alert-danger');
            resultMessage.textContent = 'Maaf, Anda belum lulus post-test ini. Anda perlu mendapatkan minimal 70%.';
            retryButton.classList.remove('d-none');
          }
          
          document.getElementById('result-score').textContent = data.score;
          document.getElementById('result-total-points').textContent = data.total_points;
          document.getElementById('result-percentage').textContent = data.percentage;
          document.getElementById('result-correct-count').textContent = data.correct_count;
          document.getElementById('result-total-questions').textContent = data.total_questions;
          
          // Update progress bars
          const scorePercentage = data.total_points > 0 ? (data.score / data.total_points) * 100 : 0;
          const correctPercentage = data.total_questions > 0 ? (data.correct_count / data.total_questions) * 100 : 0;
          
          document.getElementById('score-progress').style.width = `${scorePercentage}%`;
          document.getElementById('correct-progress').style.width = `${correctPercentage}%`;
          
          // Make sure result container is fully visible
          resultContainer.style.display = 'block';
          resultContainer.scrollIntoView({ behavior: 'auto', block: 'start' });
          
          // Reset test state in local storage
          localStorage.removeItem(`${STORAGE_PREFIX}start_time`);
          localStorage.removeItem(`${STORAGE_PREFIX}is_started`);
          localStorage.removeItem(`${STORAGE_PREFIX}answers`);
        })
        .catch(error => {
          // Clear the timeout since we got a response (even if it's an error)
          clearTimeout(submissionTimeout);
          
          console.error('Error submitting test:', error);
          
          // Show error UI
          loadingContainer.classList.add('d-none');
          resultContainer.classList.remove('d-none');
          
          const resultAlert = document.getElementById('result-alert');
          const resultMessage = document.getElementById('result-message');
          
          resultAlert.className = 'alert alert-warning';
          resultMessage.textContent = 'Terjadi kesalahan koneksi ke server. Silakan coba lagi nanti.';
          
          // Show basic result with zeros instead of N/A
          document.getElementById('result-score').textContent = '0';
          document.getElementById('result-total-points').textContent = '{{ count($questions) }}';
          document.getElementById('result-percentage').textContent = '0';
          document.getElementById('result-correct-count').textContent = '0';
          document.getElementById('result-total-questions').textContent = '{{ count($questions) }}';
          
          // Set progress bars to 0%
          document.getElementById('score-progress').style.width = '0%';
          document.getElementById('correct-progress').style.width = '0%';
          
          retryButton.classList.remove('d-none');
          
          // Reset test state in local storage
          localStorage.removeItem(`${STORAGE_PREFIX}start_time`);
          localStorage.removeItem(`${STORAGE_PREFIX}is_started`);
          localStorage.removeItem(`${STORAGE_PREFIX}answers`);
        });
      }
      
      // Keep only fullscreen mode active, remove other anti-cheating measures
      function keepFullscreenOnlyMode() {
        // Remove event listeners for cheating detection
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        window.removeEventListener('blur', handleWindowBlur);
        document.removeEventListener('keydown', handleKeyDown);
        window.removeEventListener('resize', checkFullscreenStatus);
        document.removeEventListener('contextmenu', preventDefaultAction);
        document.removeEventListener('selectstart', preventDefaultAction);
        document.removeEventListener('copy', preventDefaultAction);
        
        // Keep the fullscreen mode active
        isTestActive = false;
      }
      
      // Reset test state but keep fullscreen mode active
      function resetTestStateKeepFullscreen() {
        // Clear test data from local storage
        localStorage.removeItem(`${STORAGE_PREFIX}start_time`);
        localStorage.removeItem(`${STORAGE_PREFIX}is_started`);
        localStorage.removeItem(`${STORAGE_PREFIX}answers`);
        
        // Reset variables
        warningCount = 0;
        visibilityWarningShown = false;
        
        // Remove warning overlay if exists
        if (warningOverlay && warningOverlay.parentNode) {
          warningOverlay.parentNode.removeChild(warningOverlay);
          warningOverlay = null;
        }
        
        // Get fresh CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Juga reset data jawaban di server
        // Force clear parameter prevents redirect
        fetch('{{ route('post-test.save-answers') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            answers: {}, // Use an empty object
            language: '{{ $language ?? "id" }}',
            force_clear: true
          })
        })
        .then(response => {
          // Check if response is JSON
          const contentType = response.headers.get('content-type');
          if (contentType && contentType.includes('application/json')) {
            return response.json();
          } else {
            // If not JSON, just log it but don't try to refresh token here
            console.log('Non-JSON response when clearing answers');
            return { success: false };
          }
        })
        .then(data => {
          console.log('Answers cleared on server while keeping fullscreen');
          serverErrorCount = 0; // Reset error count on success
        })
        .catch(error => {
          handleServerError(error, 'clearing answers on server');
          // No need to alert user or retry here
        });
      }
      
      // Restore saved answers
      function restoreAnswers(savedAnswers) {
        for (const questionId in savedAnswers) {
          const value = savedAnswers[questionId];
          const questionItem = document.querySelector(`.question-item[data-question-id="${questionId}"]`);
          
          if (!questionItem) continue;
          
          // Check if this is a multiple choice question
          const isMultipleChoice = questionItem.querySelector('.options-container') !== null;
          
          if (isMultipleChoice) {
            const radioInput = document.querySelector(`[name="question-${questionId}"][value="${value}"]`);
            if (radioInput) {
              radioInput.checked = true;
              
              // Mark as answered in the navigation
              const questionIndex = Array.from(questionItems).indexOf(questionItem);
              const navItem = document.querySelector(`.question-nav-item[data-question-index="${questionIndex}"]`);
              if (navItem) {
                navItem.classList.add('answered');
              }
            }
          } else if (questionItem.querySelector('textarea')) {
            const textarea = questionItem.querySelector('textarea');
            textarea.value = value;
            textarea.dispatchEvent(new Event('input'));
            
            // Mark as answered if there's content
            if (value.trim()) {
              const questionIndex = Array.from(questionItems).indexOf(questionItem);
              const navItem = document.querySelector(`.question-nav-item[data-question-index="${questionIndex}"]`);
              if (navItem) {
                navItem.classList.add('answered');
              }
            }
          } else {
            const inputElement = questionItem.querySelector(`[name="question-${questionId}"]`);
            if (inputElement) {
              inputElement.value = value;
              
              // Mark as answered if there's content
              if (value.trim()) {
                const questionIndex = Array.from(questionItems).indexOf(questionItem);
                const navItem = document.querySelector(`.question-nav-item[data-question-index="${questionIndex}"]`);
                if (navItem) {
                  navItem.classList.add('answered');
                }
              }
            }
          }
        }
      }
      
      // Add event listeners for question navigation
      document.querySelectorAll('.question-nav-item').forEach((item) => {
        item.addEventListener('click', () => {
          // Save current answers before navigating
          saveAnswers();
          
          // Navigate to the clicked question
          const questionIndex = parseInt(item.getAttribute('data-question-index'));
          if (!isNaN(questionIndex) && questionIndex >= 0 && questionIndex < totalQuestions) {
            currentQuestionIndex = questionIndex;
            updateNavigation();
          }
        });
      });
      
      // Handle beforeunload event to warn about leaving the page
      window.addEventListener('beforeunload', function(e) {
        if (testInProgress) {
          const message = 'Jika Anda meninggalkan halaman ini, progres tes Anda mungkin tidak tersimpan.';
          e.returnValue = message;
          return message;
        }
      });
      
      // Check if test already started
      checkTestStatus();
      
      // Add event listener for the "Kembali ke Pembelajaran" button
      const backToLearningBtn = document.getElementById('back-to-learning-btn');
      if (backToLearningBtn) {
        backToLearningBtn.addEventListener('click', function(e) {
          // Reset test state before navigating away
          resetTestState();
          
          // Disable beforeunload warning
          disableBeforeUnloadWarning();
          
          // Let the default navigation happen
          // No need to prevent default
        });
      }
      
      // Add event listener for the "Coba Lagi" button
      const retryBtn = document.getElementById('retry-button');
      if (retryBtn) {
        retryBtn.addEventListener('click', function(e) {
          // Disable beforeunload warning
          disableBeforeUnloadWarning();
          
          // Let the default navigation happen
          // No need to prevent default
        });
      }
    });
  </script>
  @endpush
</x-app-layout>