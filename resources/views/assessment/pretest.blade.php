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
    .warning-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(220, 53, 69, 0.95);
      z-index: 10000;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: white;
      font-size: 1.5rem;
      text-align: center;
      padding: 2rem;
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
      background-color: #0d6efd;
      color: white;
      border-color: #0d6efd;
    }
    .question-nav-item.answered {
      background-color: #d1e7dd;
      border-color: #badbcc;
    }
    .question-nav-item.active.answered {
      background-color: #0d6efd;
      color: white;
    }
  </style>
  @endpush
  
  <div class="container mt-4" id="pretest-container">
    <div class="row mb-4">
      <div class="col-md-12 text-center">
        <h1>Pretest Kemampuan Bahasa</h1>
        <p class="lead">
          Selesaikan tes ini untuk menentukan level kemampuan Anda dan mendapatkan materi pembelajaran yang sesuai.
        </p>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8 col-md-10 mx-auto">
        <!-- Konfirmasi Pretest -->
        <div class="card mb-4" id="confirmation-card">
          <div class="card-header bg-primary text-white">
            <span>Konfirmasi Pretest - {{ $languageName ?? 'Bahasa Indonesia' }}</span>
          </div>
          <div class="card-body">
            <div class="alert alert-warning">
              <h5><i class="fas fa-exclamation-triangle mr-2"></i> Perhatian!</h5>
              <p>Sebelum memulai pretest {{ $languageName ?? 'Bahasa Indonesia' }}, pastikan hal-hal berikut:</p>
              <ul>
                <li>Pretest ini akan menentukan level awal Anda dalam {{ $languageName ?? 'Bahasa Indonesia' }}</li>
                <li>Pretest ini memiliki batas waktu <strong>{{ $timeLimit ?? 30 }} menit</strong></li>
                <li>Timer akan terus berjalan bahkan jika halaman di-refresh</li>
                <li>Jawaban Anda akan tersimpan otomatis</li>
              </ul>
            </div>
            <div class="d-grid gap-2">
              <button id="start-test-btn" class="btn btn-primary btn-lg">
                <i class="fas fa-play-circle me-2"></i> Mulai Pretest
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
                  <span id="timer-minutes">10</span>:<span id="timer-seconds">00</span>
                </div>
              </div>
              <div class="progress mt-1" style="height: 5px;">
                <div id="timer-progress" class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
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
              @if(isset($questions) && count($questions) > 0)
                @foreach($questions as $index => $question)
                <div class="question-nav-item {{ $index === 0 ? 'active' : '' }}" data-question-index="{{ $index }}">
                  {{ $index + 1 }}
                </div>
                @endforeach
              @endif
            </div>
          </div>
        </div>

        <div class="card d-none" id="test-card">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span>Soal Pretest</span>
            <span class="badge bg-light text-primary" id="question-counter">1 / {{ count($questions ?? []) }}</span>
          </div>
          <div class="card-body">
            <form id="pretest-form" action="{{ route('pretest.evaluate') }}" method="POST">
              @csrf
              <div id="questions-container">
                @if(isset($questions) && count($questions) > 0)
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
                @else
                <!-- Fallback for when no questions are available -->
                <div class="alert alert-warning">
                  <p>Tidak ada pertanyaan yang tersedia. Silahkan coba lagi nanti atau hubungi administrator.</p>
                </div>
                @endif
              </div>
            </form>
            
            <!-- Loading spinner -->
            <div id="loading-container" class="text-center d-none">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2">Mengevaluasi jawaban Anda...</p>
            </div>
            
            <!-- Results container, initially hidden -->
            <div id="result-container" class="d-none">
              <h4 class="mb-3">Hasil Pretest</h4>
              <div class="alert alert-info">
                <p>Berdasarkan jawaban Anda:</p>
                <p>Level kemampuan: <span id="result-level" class="fw-bold"></span></p>
                <div class="row mt-3">
                  <div class="col-md-6">
                    <div class="card bg-light mb-3">
                      <div class="card-body p-3">
                        <h5 class="card-title mb-2">Skor Perolehan</h5>
                        <h2 class="mb-0"><span id="result-score" class="fw-bold"></span> / <span id="result-total-points"></span></h2>
                        <div class="progress mt-2" style="height: 10px;">
                          <div id="score-progress" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
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
              <p>Anda akan mendapatkan materi pembelajaran yang sesuai dengan level Anda.</p>
              <a id="continue-button" href="{{ route('learning.materials') }}" class="btn btn-primary">Lanjutkan ke Pembelajaran</a>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-4 col-md-10 mx-auto mt-4 mt-lg-0">
        <div class="card mb-4">
          <div class="card-header bg-info text-white">Panduan Pretest</div>
          <div class="card-body">
            <p>Tes ini terdiri dari beberapa jenis soal:</p>
            <ul>
              <li><strong>Pilihan Ganda</strong> - Pilih satu jawaban yang benar</li>
              <li><strong>Benar/Salah</strong> - Tentukan apakah pernyataan benar atau salah</li>
              <li><strong>Esai</strong> - Tuliskan jawaban dengan jumlah kata minimal yang ditentukan</li>
              <li><strong>Isian</strong> - Isi bagian rumpang dengan kata yang tepat</li>
            </ul>
            <p>Hasil tes ini akan menentukan level kemampuan bahasa Anda dan materi pembelajaran yang akan diberikan.</p>
          </div>
        </div>
        
        <div class="card">
          <div class="card-header bg-warning text-white">Tips</div>
          <div class="card-body">
            <ul>
              <li>Pastikan Anda menjawab semua pertanyaan</li>
              <li>Untuk soal esai, perhatikan jumlah kata minimal</li>
              <li>Bacalah setiap pertanyaan dengan teliti</li>
              <li><strong>Ada batas waktu 10 menit</strong> untuk menyelesaikan pretest</li>
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
      const totalQuestions = questionItems.length;
      const questionCounter = document.getElementById('question-counter');
      const pretestForm = document.getElementById('pretest-form');
      const loadingContainer = document.getElementById('loading-container');
      const resultContainer = document.getElementById('result-container');
      const questionsContainer = document.getElementById('questions-container');
      const continueButton = document.getElementById('continue-button');
      const confirmationCard = document.getElementById('confirmation-card');
      const testCard = document.getElementById('test-card');
      const timerCard = document.getElementById('timer-card');
      const startTestBtn = document.getElementById('start-test-btn');
      const timerMinutes = document.getElementById('timer-minutes');
      const timerSeconds = document.getElementById('timer-seconds');
      const timerProgress = document.getElementById('timer-progress');
      
      // Waktu tes dalam detik, ambil dari database atau gunakan default
      const TEST_TIME = {{ isset($timeLimit) ? $timeLimit * 60 : 30 * 60 }}; // minutes in seconds
      const STORAGE_PREFIX = 'pretest_';
      
      // Anti-cheating variables
      let warningCount = 0;
      const MAX_WARNINGS = 2;
      let isTestActive = false;
      let isFullscreenMode = false;
      let visibilityWarningShown = false;
      let warningOverlay = null;
      
      // Error handling variables
      let serverErrorCount = 0;
      const MAX_SERVER_ERRORS = 3;
      let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      
      // Function to safely handle server responses
      function handleServerResponse(response) {
        // Check if response is ok (status in the range 200-299)
        if (!response.ok) {
          throw new Error(`Server returned ${response.status}: ${response.statusText}`);
        }
        
        // Check if content type is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          // If not JSON, try to parse it anyway but in a try-catch
          return response.text().then(text => {
            try {
              // Try to parse as JSON anyway
              return JSON.parse(text);
            } catch (e) {
              // If it's not JSON, throw an error
              throw new Error(`Expected JSON but got ${contentType || 'unknown content type'}`);
            }
          });
        }
        
        // If it's JSON, parse it normally
        return response.json().catch(error => {
          throw new Error(`Failed to parse JSON: ${error.message}`);
        });
      }
      
      // Function to refresh CSRF token
      function refreshCsrfToken() {
        return fetch('{{ route("refresh-csrf") }}')
          .then(response => response.json())
          .then(data => {
            if (data && data.token) {
              csrfToken = data.token;
              console.log('CSRF token refreshed');
            }
            return csrfToken;
          })
          .catch(error => {
            console.error('Failed to refresh CSRF token:', error);
            return csrfToken; // Return the old token as fallback
          });
      }
      
      // Function to handle server errors
      function handleServerError(error, operation) {
        console.error(`Error during ${operation}:`, error);
        
        serverErrorCount++;
        
        // If we've had too many server errors, show a warning but continue
        if (serverErrorCount >= MAX_SERVER_ERRORS) {
          console.warn(`Reached maximum server errors (${MAX_SERVER_ERRORS}). Switching to offline mode.`);
          
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
        
        if (isStarted && startTime) {
          // Test sudah dimulai sebelumnya
          startTest(false);
          
          // Selalu coba ambil jawaban terbaru dari server terlebih dahulu
          fetch('{{ route('pretest.get-answers') }}?language={{ $language ?? 'id' }}', {
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json'
            }
          })
          .then(handleServerResponse)
          .then(data => {
            console.log('Fetched latest answers from server:', data);
            
            if (data.success && data.answers) {
              // Restore jawaban dari server
              restoreAnswers(data.answers);
            } else if (answers) {
              // Fallback ke localStorage jika server tidak punya data
              console.log('Using localStorage answers as fallback');
              restoreAnswers(JSON.parse(answers));
            }
          })
          .catch(error => {
            handleServerError(error, 'fetching answers');
            
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
        localStorage.removeItem(`${STORAGE_PREFIX}warning_count`);
        
        // Reset variables
        warningCount = 0;
        isTestActive = false;
        isFullscreenMode = false;
        visibilityWarningShown = false;
        
        // Remove warning overlay if exists
        if (warningOverlay && warningOverlay.parentNode) {
          warningOverlay.parentNode.removeChild(warningOverlay);
          warningOverlay = null;
        }
        
        // Juga reset data jawaban di server
        // Force clear parameter prevents redirect
        fetch('{{ route('pretest.save-answers') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            answers: {},
            language: '{{ $language ?? "id" }}',
            force_clear: true
          })
        })
        .then(handleServerResponse)
        .then(data => {
          console.log('Answers cleared on server');
          serverErrorCount = 0; // Reset error count on success
        })
        .catch(error => {
          handleServerError(error, 'clearing answers');
          // Continue anyway - don't block the user experience
        });
      }
      
      // Start the test
      function startTest(isNew = true) {
        confirmationCard.classList.add('d-none');
        testCard.classList.remove('d-none');
        timerCard.classList.remove('d-none');
        document.getElementById('question-nav-card').classList.remove('d-none');
        
        // Hide information and tips cards
        const infoCard = document.querySelector('.card:has(.card-header.bg-info)');
        const tipsCard = document.querySelector('.card:has(.card-header.bg-warning)');
        
        if (infoCard) infoCard.classList.add('d-none');
        if (tipsCard) tipsCard.classList.add('d-none');
        
        if (isNew) {
          // Reset any previous test state
          resetTestState();
          
          // Set start time
          const currentTime = Math.floor(Date.now() / 1000);
          localStorage.setItem(`${STORAGE_PREFIX}start_time`, currentTime);
          localStorage.setItem(`${STORAGE_PREFIX}is_started`, 'true');
          localStorage.setItem(`${STORAGE_PREFIX}answers`, JSON.stringify({}));
          localStorage.setItem(`${STORAGE_PREFIX}warning_count`, '0');
          
          // Juga simpan waktu mulai ke server
          fetch('{{ route('pretest.save-answers') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              answers: {},
              language: '{{ $language ?? "id" }}'
            })
          })
          .then(handleServerResponse)
          .then(data => {
            console.log('Start time saved to server');
            serverErrorCount = 0; // Reset error count on success
          })
          .catch(error => {
            handleServerError(error, 'saving start time')
              .then(() => {
                // If we got a new CSRF token, try again with the new token
                if (error.message && (error.message.includes('419') || error.message.includes('CSRF'))) {
                  console.log('Retrying with new CSRF token');
                  fetch('{{ route('pretest.save-answers') }}', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': csrfToken,
                      'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                      answers: {},
                      language: '{{ $language ?? "id" }}'
                    })
                  })
                  .then(handleServerResponse)
                  .then(data => {
                    console.log('Start time saved to server (retry successful)');
                    serverErrorCount = 0;
                  })
                  .catch(error => {
                    console.error('Retry failed:', error);
                    // Continue anyway
                  });
                }
              });
          });
        } else {
          // Restore warning count if exists
          const savedWarningCount = localStorage.getItem(`${STORAGE_PREFIX}warning_count`);
          if (savedWarningCount) {
            warningCount = parseInt(savedWarningCount);
          }
        }
        
        // Setup anti-cheating measures but don't enter fullscreen yet
        setupAntiCheating();
        
        startTimer();
        updateNavigation();
      }
      
      // Setup anti-cheating measures without entering fullscreen
      function setupAntiCheating() {
        isTestActive = true;
        
        // Add event listeners for anti-cheating detection
        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('blur', handleWindowBlur);
        document.addEventListener('keydown', handleKeyDown);
        window.addEventListener('resize', checkFullscreenStatus);
        
        // Disable right-click
        document.addEventListener('contextmenu', preventDefaultAction);
        
        // Disable selection
        document.addEventListener('selectstart', preventDefaultAction);
        
        // Disable copying
        document.addEventListener('copy', preventDefaultAction);
        
        // Check fullscreen status periodically
        setInterval(checkFullscreenStatus, 1000);
      }
      
      // Disable anti-cheating measures
      function disableAntiCheating() {
        isTestActive = false;
        
        // Remove event listeners first
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        window.removeEventListener('blur', handleWindowBlur);
        document.removeEventListener('keydown', handleKeyDown);
        window.removeEventListener('resize', checkFullscreenStatus);
        document.removeEventListener('contextmenu', preventDefaultAction);
        document.removeEventListener('selectstart', preventDefaultAction);
        document.removeEventListener('copy', preventDefaultAction);
        
        // Exit fullscreen mode - do this after removing event listeners
        try {
          exitFullscreenMode();
        } catch (error) {
          console.error('Error exiting fullscreen:', error);
        }
      }
      
      // Enter fullscreen mode
      function enterFullscreenMode() {
        const container = document.getElementById('pretest-container');
        if (!container) return;
        
        // Create fullscreen container
        const fullscreenContainer = document.createElement('div');
        fullscreenContainer.id = 'fullscreen-wrapper';
        fullscreenContainer.className = 'fullscreen-container';
        
        // Move test content to fullscreen container
        document.body.appendChild(fullscreenContainer);
        fullscreenContainer.appendChild(container);
        
        // Add fullscreen class to body
        document.body.classList.add('fullscreen-mode');
        
        // Request browser fullscreen with options to prevent ESC key from exiting
        try {
          // Try to use newer options to lock keyboard
          if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(error => {
              console.error('Standard fullscreen request failed:', error);
            });
          } else if (document.documentElement.mozRequestFullScreen) {
            document.documentElement.mozRequestFullScreen();
          } else if (document.documentElement.webkitRequestFullscreen) {
            document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
          } else if (document.documentElement.msRequestFullscreen) {
            document.documentElement.msRequestFullscreen();
          }
        } catch (error) {
          console.error('Fullscreen error:', error);
        }
        
        isFullscreenMode = true;
      }
      
      // Exit fullscreen mode
      function exitFullscreenMode() {
        const container = document.getElementById('pretest-container');
        const wrapper = document.getElementById('fullscreen-wrapper');
        
        // First check if we're actually in fullscreen mode
        const isCurrentlyFullscreen = !!(
          document.fullscreenElement ||
          document.mozFullScreenElement ||
          document.webkitFullscreenElement ||
          document.msFullscreenElement
        );
        
        if (isCurrentlyFullscreen) {
          try {
            // Exit browser fullscreen
            if (document.exitFullscreen) {
              document.exitFullscreen().catch(error => {
                console.error('Error exiting fullscreen:', error);
              });
            } else if (document.mozCancelFullScreen) {
              document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
              document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
              document.msExitFullscreen();
            }
          } catch (error) {
            console.error('Error exiting fullscreen:', error);
          }
        }
        
        // Move content back regardless of fullscreen state
        if (container && wrapper) {
          try {
            // Move test content back to original position
            wrapper.parentNode.insertBefore(container, wrapper);
            wrapper.remove();
          } catch (error) {
            console.error('Error moving content back:', error);
          }
        }
        
        // Remove fullscreen class from body
        document.body.classList.remove('fullscreen-mode');
        
        isFullscreenMode = false;
      }
      
      // Check fullscreen status
      function checkFullscreenStatus() {
        if (!isTestActive) return;
        
        const isCurrentlyFullscreen = !!(
          document.fullscreenElement ||
          document.mozFullScreenElement ||
          document.webkitFullscreenElement ||
          document.msFullscreenElement
        );
        
        // Only show warning if we were in fullscreen and now we're not
        if (isFullscreenMode && !isCurrentlyFullscreen) {
          isFullscreenMode = false; // Update to prevent multiple warnings
          showCheatingWarning('Anda keluar dari mode fullscreen. Ini dianggap sebagai upaya kecurangan.');
        }
      }
      
      // Handle visibility change (tab switching)
      function handleVisibilityChange() {
        if (!isTestActive) return;
        
        if (document.visibilityState === 'hidden') {
          visibilityWarningShown = true;
          // User switched tabs or minimized window
          showCheatingWarning('Anda beralih ke tab/aplikasi lain. Ini dianggap sebagai upaya kecurangan.');
        }
      }
      
      // Handle window blur (clicking outside the window)
      function handleWindowBlur() {
        if (!isTestActive || visibilityWarningShown) return;
        
        // User clicked outside the window
        showCheatingWarning('Anda mengakses aplikasi lain. Ini dianggap sebagai upaya kecurangan.');
        
        // Reset visibility warning flag
        visibilityWarningShown = false;
      }
      
      // Handle keyboard shortcuts
      function handleKeyDown(e) {
        if (!isTestActive) return;
        
        // Only capture keyboard shortcuts but NOT ESC key
        // Let the fullscreen event handler deal with ESC key to prevent double counting
        if ((e.altKey || e.ctrlKey || e.metaKey || e.key === 'F12') && e.key !== 'Escape') {
          e.preventDefault();
          showCheatingWarning('Penggunaan shortcut keyboard terdeteksi. Ini dianggap sebagai upaya kecurangan.');
          return false;
        }
      }
      
      // Prevent default action
      function preventDefaultAction(e) {
        if (isTestActive) {
          e.preventDefault();
          return false;
        }
      }
      
      // Show cheating warning
      function showCheatingWarning(message) {
        // Get current warning count from storage if available
        let currentWarningCount = parseInt(localStorage.getItem(`${STORAGE_PREFIX}warning_count`)) || 0;
        
        // Only increment if not already at max
        if (currentWarningCount < MAX_WARNINGS) {
          currentWarningCount++;
          warningCount = currentWarningCount;
          // Save warning count to localStorage
          localStorage.setItem(`${STORAGE_PREFIX}warning_count`, currentWarningCount.toString());
        }
        
        // Remove any existing warning overlay first to prevent stacking
        if (warningOverlay && warningOverlay.parentNode) {
          warningOverlay.parentNode.removeChild(warningOverlay);
          warningOverlay = null;
        }
        
        // Create new warning overlay
        warningOverlay = document.createElement('div');
        warningOverlay.className = 'warning-overlay';
        document.body.appendChild(warningOverlay);
        
        // Update warning message
        warningOverlay.innerHTML = `
          <h2><i class="fas fa-exclamation-triangle mb-3"></i></h2>
          <h3>PERINGATAN!</h3>
          <p>${message}</p>
          <p>Peringatan ${Math.min(currentWarningCount, MAX_WARNINGS)} dari ${MAX_WARNINGS}</p>
          <p class="mt-3">${currentWarningCount >= MAX_WARNINGS ? 'Anda telah mencapai batas maksimum peringatan. Tes akan disubmit dengan nilai 0.' : 'Jika terjadi sekali lagi, tes akan otomatis disubmit dengan nilai 0.'}</p>
          ${currentWarningCount < MAX_WARNINGS ? '<button id="continue-test-btn" class="btn btn-light mt-3">Lanjutkan Tes</button>' : ''}
        `;
        
        // Show warning
        warningOverlay.style.display = 'flex';
        
        // Add event listener to continue button if available
        const continueButton = document.getElementById('continue-test-btn');
        if (continueButton) {
          continueButton.addEventListener('click', function() {
            if (warningOverlay) {
              warningOverlay.style.display = 'none';
              
              // Remove from DOM completely to prevent stacking
              if (warningOverlay.parentNode) {
                warningOverlay.parentNode.removeChild(warningOverlay);
                warningOverlay = null;
              }
            }
            
            // Re-enter fullscreen directly from user interaction (button click)
            try {
              enterFullscreenMode();
            } catch (error) {
              console.error('Error re-entering fullscreen from continue button:', error);
            }
          });
        }
        
        // If max warnings reached, auto-submit with zero score
        if (currentWarningCount >= MAX_WARNINGS) {
          setTimeout(() => {
            submitTestWithZeroScore();
          }, 3000);
        }
      }
      
      // Submit test with zero score due to cheating
      function submitTestWithZeroScore() {
        // Remove any existing warning overlay first
        if (warningOverlay && warningOverlay.parentNode) {
          warningOverlay.parentNode.removeChild(warningOverlay);
          warningOverlay = null;
        }
        
        // Create new warning overlay for submission message
        warningOverlay = document.createElement('div');
        warningOverlay.className = 'warning-overlay';
        document.body.appendChild(warningOverlay);
        
        // Update warning overlay
        warningOverlay.innerHTML = `
          <h2><i class="fas fa-ban mb-3"></i></h2>
          <h3>KECURANGAN TERDETEKSI!</h3>
          <p>Anda telah mencapai batas maksimum peringatan.</p>
          <p>Tes akan disubmit dengan nilai 0.</p>
          <div class="spinner-border text-light mt-3" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        `;
        
        // Show warning
        warningOverlay.style.display = 'flex';
        
        // Disable anti-cheating first
        try {
          disableAntiCheating();
        } catch (error) {
          console.error('Error disabling anti-cheating:', error);
        }
        
        // Reset test state completely
        resetTestState();
        
        // Submit with zero score
        fetch('{{ route("pretest.evaluate") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({ 
            answers: {},
            language: '{{ $language ?? "id" }}',
            cheating_detected: true
          })
        })
        .then(handleServerResponse)
        .then(data => {
          // Redirect to result page
          window.location.href = '{{ route("dashboard") }}?from_pretest=true&cheating=true';
        })
        .catch(error => {
          handleServerError(error, 'submitting zero score')
            .then(() => {
              // Redirect anyway
              window.location.href = '{{ route("dashboard") }}?from_pretest=true&cheating=true';
            });
        });
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
        const startTime = parseInt(localStorage.getItem(`${STORAGE_PREFIX}start_time`));
        const currentTime = Math.floor(Date.now() / 1000);
        let elapsed = currentTime - startTime;
        let remaining = TEST_TIME - elapsed;
        
        if (remaining <= 0) {
          // Time's up, submit the test
          submitTest();
          return;
        }
        
        updateTimerDisplay(remaining);
        // Store timer interval in a global variable so it can be cleared from anywhere
        window.timerInterval = setInterval(() => {
          remaining--;
          updateTimerDisplay(remaining);
          
          // Save progress every 10 seconds
          if (remaining % 10 === 0) {
            saveAnswers();
          }
          
          // Tambahan: setiap 30 detik, coba cek waktu dari server (sinkronisasi)
          if (remaining % 30 === 0) {
            syncTimeWithServer();
          }
          
          if (remaining <= 0) {
            clearInterval(window.timerInterval);
            window.timerInterval = null;
            submitTest();
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
          timerProgress.className = 'progress-bar bg-primary';
        }
      }
      
      // Sync time with server
      function syncTimeWithServer() {
        // Fetch current server time and update timer accordingly
        fetch('{{ route('pretest.get-time') }}?language={{ $language ?? 'id' }}', {
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          }
        })
        .then(handleServerResponse)
        .then(data => {
          if (data.success && data.start_time) {
            const serverStartTime = parseInt(data.start_time);
            const currentTime = Math.floor(Date.now() / 1000);
            const elapsedFromServer = currentTime - serverStartTime;
            
            // If server has a valid start time, update our timer
            if (serverStartTime > 0) {
              // Update localStorage with server's start time
              localStorage.setItem(`${STORAGE_PREFIX}start_time`, serverStartTime);
              
              // Calculate new remaining time
              const newRemaining = TEST_TIME - elapsedFromServer;
              
              // If there's a significant difference (more than 10 seconds)
              // between our timer and server's timer, update it
              if (Math.abs(newRemaining - remaining) > 10) {
                console.log('Syncing time with server. Old remaining:', remaining, 'New remaining:', newRemaining);
                
                // Update the remaining time globally
                remaining = Math.max(0, newRemaining);
                
                // Update display immediately
                updateTimerDisplay(remaining);
                
                // If time's up according to server, submit the test
                if (remaining <= 0) {
                  clearInterval(window.timerInterval);
                  window.timerInterval = null;
                  submitTest();
                }
              }
            }
            serverErrorCount = 0; // Reset error count on success
          }
        })
        .catch(error => {
          handleServerError(error, 'syncing time');
          // Continue with client-side timer - don't block the user experience
        });
      }
      
      // Next and previous button handlers
      if (questionItems.length > 0) {
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
      }
      
      // Start test button
      if (startTestBtn) {
        startTestBtn.addEventListener('click', () => {
          // Start the test
          startTest(true);
          
          // Request fullscreen directly from the user interaction
          try {
            enterFullscreenMode();
          } catch (error) {
            console.error('Error entering fullscreen from button click:', error);
          }
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
      if (pretestForm) {
        pretestForm.addEventListener('submit', function(e) {
          e.preventDefault();
          submitTest();
        });
      }
      
      // Selesai & Kirim button
      document.querySelectorAll('.btn-submit').forEach(btn => {
        btn.addEventListener('click', () => {
          saveAnswers();
          submitTest();
        });
      });
      
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
      
      // Save answers to local storage and server
      function saveAnswers() {
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
        
        // Save to server untuk persistensi
        fetch('{{ route('pretest.save-answers') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            answers: answers,
            language: '{{ $language ?? "id" }}'
          })
        })
        .then(handleServerResponse)
        .then(data => {
          console.log('Answers saved to server successfully');
          serverErrorCount = 0; // Reset error count on success
        })
        .catch(error => {
          handleServerError(error, 'saving answers');
          // Continue anyway - don't block the user experience
        });
      }
      
      // Submit test
      function submitTest() {
        saveAnswers();
        
        questionsContainer.classList.add('d-none');
        loadingContainer.classList.remove('d-none');
        
        // Hide the timer card
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
        
        // Keep fullscreen mode active, but remove other anti-cheating measures
        keepFullscreenOnlyMode();
        
        // Reset test state completely but keep fullscreen
        resetTestStateKeepFullscreen();
        
        // Check if we have any answers
        const hasAnswers = Object.keys(answers).length > 0;
        
        // If we've had too many server errors or no answers, show a warning
        if (serverErrorCount >= MAX_SERVER_ERRORS || !hasAnswers) {
          if (!hasAnswers) {
            // If no answers, show warning and allow user to go back
            loadingContainer.classList.add('d-none');
            questionsContainer.classList.remove('d-none');
            
            alert('Anda belum menjawab pertanyaan apapun. Silakan jawab setidaknya satu pertanyaan sebelum mengirimkan tes.');
            
            // Re-enable the submit button
            const submitBtn = document.querySelector('.btn-submit');
            if (submitBtn) {
              submitBtn.disabled = false;
            }
            
            return;
          }
        }
        
        // Submit answers via AJAX
        fetch('{{ route("pretest.evaluate") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({ 
            answers: answers,
            language: '{{ $language ?? "id" }}',
            start_time: localStorage.getItem(`${STORAGE_PREFIX}start_time`),
            duration: getElapsedTimeInMinutes()
          })
        })
        .then(handleServerResponse)
        .then(data => {
          loadingContainer.classList.add('d-none');
          resultContainer.classList.remove('d-none');
          
          // Force scroll to top to ensure results are visible
          window.scrollTo(0, 0);
          document.body.scrollTop = 0;
          document.documentElement.scrollTop = 0;
          
          // Display result
          document.getElementById('result-level').textContent = data.level;
          document.getElementById('result-score').textContent = data.score !== undefined ? Math.max(0, parseInt(data.score)) : 0;
          document.getElementById('result-total-points').textContent = data.total_points !== undefined ? Math.max(0, parseInt(data.total_points)) : 0;
          document.getElementById('result-percentage').textContent = data.percentage !== undefined ? Math.max(0, parseInt(data.percentage)) : 0;
          document.getElementById('result-correct-count').textContent = data.correct_count !== undefined ? Math.max(0, parseInt(data.correct_count)) : 0;
          document.getElementById('result-total-questions').textContent = data.total_questions !== undefined ? Math.max(0, parseInt(data.total_questions)) : 0;
          
          // Update progress bars
          const scorePercentage = data.total_points > 0 ? (Math.max(0, data.score) / data.total_points) * 100 : 0;
          const correctPercentage = data.total_questions > 0 ? (Math.max(0, data.correct_count) / data.total_questions) * 100 : 0;
          
          document.getElementById('score-progress').style.width = `${scorePercentage}%`;
          document.getElementById('correct-progress').style.width = `${correctPercentage}%`;
          
          // Make sure result container is fully visible within fullscreen mode
          resultContainer.style.display = 'block';
          resultContainer.scrollIntoView({ behavior: 'auto', block: 'start' });
          
          serverErrorCount = 0; // Reset error count on success
        })
        .catch(error => {
          handleServerError(error, 'submitting test')
            .then(() => {
              // If we've had too many server errors, try to handle locally
              if (serverErrorCount >= MAX_SERVER_ERRORS) {
                // Create a basic result based on client-side data
                loadingContainer.classList.add('d-none');
                resultContainer.classList.remove('d-none');
                
                // Set default values for results
                document.getElementById('result-level').textContent = "1";
                document.getElementById('result-score').textContent = "0";
                document.getElementById('result-total-points').textContent = totalQuestions.toString();
                document.getElementById('result-percentage').textContent = "0";
                document.getElementById('result-correct-count').textContent = "0";
                document.getElementById('result-total-questions').textContent = totalQuestions.toString();
                
                // Set progress bars to 0
                document.getElementById('score-progress').style.width = "0%";
                document.getElementById('correct-progress').style.width = "0%";
                
                // Show a warning
                alert('Tidak dapat menghubungi server untuk mengevaluasi jawaban. Silakan simpan jawaban Anda dan coba lagi nanti.');
                
                // Make sure result container is visible
                resultContainer.style.display = 'block';
                resultContainer.scrollIntoView({ behavior: 'auto', block: 'start' });
              } else {
                // Show error and allow retry
                loadingContainer.classList.add('d-none');
                questionsContainer.classList.remove('d-none');
                
                alert('Terjadi kesalahan saat mengirim data. Silakan coba lagi. Jika masalah berlanjut, simpan jawaban Anda dan muat ulang halaman.');
                
                // Re-enable the submit button
                const submitBtn = document.querySelector('.btn-submit');
                if (submitBtn) {
                  submitBtn.disabled = false;
                }
              }
            });
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
        localStorage.removeItem(`${STORAGE_PREFIX}warning_count`);
        
        // Reset variables
        warningCount = 0;
        visibilityWarningShown = false;
        
        // Remove warning overlay if exists
        if (warningOverlay && warningOverlay.parentNode) {
          warningOverlay.parentNode.removeChild(warningOverlay);
          warningOverlay = null;
        }
        
        // Juga reset data jawaban di server
        // Force clear parameter prevents redirect
        fetch('{{ route('pretest.save-answers') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            answers: {},
            language: '{{ $language ?? "id" }}',
            force_clear: true
          })
        })
        .then(handleServerResponse)
        .then(data => {
          console.log('Answers cleared on server while keeping fullscreen');
          serverErrorCount = 0; // Reset error count on success
        })
        .catch(error => {
          handleServerError(error, 'clearing answers');
          // Continue anyway - don't block the user experience
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
      
      // Handle beforeunload event to warn about leaving the page
      window.addEventListener('beforeunload', function(e) {
        if (isTestActive) {
          const message = 'Jika Anda meninggalkan halaman ini, tes akan dianggap sebagai kecurangan dan nilai Anda akan menjadi 0.';
          e.returnValue = message;
          return message;
        }
      });
      
      // Calculate elapsed time in minutes
      function getElapsedTimeInMinutes() {
        const startTime = parseInt(localStorage.getItem(`${STORAGE_PREFIX}start_time`) || '0');
        if (!startTime) return 0.1; // Default minimum
        
        const currentTime = Math.floor(Date.now() / 1000);
        const elapsedSeconds = currentTime - startTime;
        
        // Ensure we don't return a negative value
        return Math.max(0.05, (elapsedSeconds / 60)).toFixed(2); // In minutes with 2 decimal places
      }
      
      // Check if test already started
      checkTestStatus();
    });
  </script>
  @endpush
</x-app-layout>