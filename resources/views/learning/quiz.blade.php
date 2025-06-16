<x-app-layout>
  <div class="container py-4">
    <div class="row">
      <div class="col-md-12">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ route('learning.materials') }}">Materi Pembelajaran</a></li>
            <li class="breadcrumb-item"><a href="{{ route('learning.material.show', $material->id) }}">{{ $material->title }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kuis</li>
          </ol>
        </nav>
        
        <!-- Show flash messages -->
        @if(session('error'))
        <div class="alert alert-danger mb-4">
          <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        </div>
        @endif
        
        @if(session('success'))
        <div class="alert alert-success mb-4">
          <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
        @endif
        
        @if(session('warning'))
        <div class="alert alert-warning mb-4">
          <i class="fas fa-exclamation-triangle me-2"></i> {{ session('warning') }}
        </div>
        @endif
        
        <!-- Show quiz result if available in session -->
        @if(session('quiz_result'))
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Hasil Kuis</h5>
          </div>
          <div class="card-body">
            <div class="text-center mb-3">
              <h5 class="{{ session('quiz_result.passed') ? 'text-success' : 'text-danger' }}">
                {{ session('quiz_result.passed') ? 'Selamat! Anda telah lulus kuis.' : 'Maaf, Anda belum lulus kuis.' }}
              </h5>
              <h1 class="display-4 {{ session('quiz_result.passed') ? 'text-success' : 'text-danger' }}">
                {{ session('quiz_result.score') !== null ? (int)session('quiz_result.score') : 0 }}%
              </h1>
              <div class="quiz-stats mt-3">
                <div class="d-flex justify-content-center gap-4 mb-2">
                  <div class="stat-item text-success">
                    <i class="fas fa-check-circle"></i> {{ session('quiz_result.correct_count') ?? 0 }} Benar
                  </div>
                  <div class="stat-item text-danger">
                    <i class="fas fa-times-circle"></i> {{ session('quiz_result.incorrect_count') ?? 0 }} Salah
                  </div>
                  <div class="stat-item text-primary">
                    <i class="fas fa-question-circle"></i> {{ session('quiz_result.total_questions') ?? 0 }} Total
                  </div>
                </div>
              </div>
              <p>Skor minimum kelulusan adalah {{ session('quiz_result.passing_score') }}%.</p>
            </div>
            
            @if(isset(session('quiz_result')['question_results']))
            <div class="mt-4 mb-4">
              <h6 class="mb-3 fw-bold">Detail Jawaban:</h6>
              @foreach(session('quiz_result.question_results') as $index => $result)
              <div class="card mb-2 {{ $result['is_correct'] ? 'border-success' : 'border-danger' }}">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Pertanyaan {{ intval($index) + 1 }}</h6>
                    <span class="badge {{ $result['is_correct'] ? 'bg-success' : 'bg-danger' }}">
                      @if($result['is_correct'])
                        <i class="fas fa-check"></i> Benar
                      @else
                        <i class="fas fa-times"></i> Salah
                      @endif
                    </span>
                  </div>
                  <p class="mt-2 mb-1"><strong>Pertanyaan:</strong> {{ $result['question_text'] }}</p>
                  <p class="mb-1"><strong>Jawaban Anda:</strong> {{ $result['user_answer_text'] ?? $result['user_answer'] ?? 'Tidak dijawab' }}</p>
                  @if(!$result['is_correct'])
                  <p class="mb-0 text-success"><strong>Jawaban Benar:</strong> {{ $result['correct_answer_text'] ?? $result['correct_answer'] }}</p>
                  @endif
                </div>
              </div>
              @endforeach
            </div>
            @endif
            
            <div class="d-grid gap-2">
              <a href="{{ route('learning.material.quiz.show', $material->id) }}" class="btn btn-warning">
                <i class="fas fa-redo me-2"></i> Coba Lagi
              </a>
              <a href="{{ route('learning.materials') }}" class="btn btn-secondary">
                <i class="fas fa-list me-2"></i> Kembali ke Daftar Materi
              </a>
              @if(session('quiz_result.passed') && session('quiz_result.next_material_id'))
              <a href="{{ route('learning.material.show', session('quiz_result.next_material_id')) }}" class="btn btn-success">
                <i class="fas fa-arrow-right me-2"></i> Lanjut ke Materi Berikutnya
              </a>
              @endif
            </div>
          </div>
        </div>
        @else
        <div class="card mb-4">
          <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">{{ $quiz->title }}</h5>
              <a href="{{ route('learning.material.show', $material->id) }}" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Materi
              </a>
            </div>
          </div>
          <div class="card-body">
            @if($quiz->description)
            <p>{{ $quiz->description }}</p>
            @endif
            
            <ul class="list-group list-group-flush mb-3">
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Jumlah Pertanyaan
                <span class="badge bg-primary">{{ count($quizQuestions) }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Skor Kelulusan
                <span class="badge bg-success">{{ $quiz->passing_score }}%</span>
              </li>
              @if($quiz->time_limit)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Batas Waktu
                <span class="badge bg-info">{{ $quiz->time_limit }} menit</span>
              </li>
              <li id="countdown-timer" class="list-group-item d-flex justify-content-between align-items-center">
                Waktu Tersisa
                <span class="badge bg-danger"><i class="fas fa-clock me-1"></i> <span id="timer-display">--:--</span></span>
              </li>
              @endif
              @if($progress->quiz_attempts > 0)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Percobaan Sebelumnya
                <span class="badge bg-secondary">{{ $progress->quiz_attempts }}x</span>
              </li>
              @if($progress->quiz_score)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Skor Tertinggi
                <span class="badge bg-{{ $progress->quiz_passed ? 'success' : 'danger' }}">{{ $progress->quiz_score }}%</span>
              </li>
              @endif
              @endif
            </ul>
            
            <div class="alert alert-info mb-4">
              <i class="fas fa-info-circle me-2"></i> Kerjakan kuis ini dengan jujur dan teliti. Anda tidak dapat kembali ke materi selama mengerjakan kuis.
              @if($quiz->time_limit)
              <br><i class="fas fa-exclamation-triangle me-2 mt-2"></i> Perhatikan waktu! Kuis akan otomatis terkirim saat waktu habis.
              @endif
            </div>
            

            
            <form id="quiz-form" action="{{ route('learning.material.quiz', $material->id) }}" method="POST">
              <div id="quiz-questions">
                @csrf
                <input type="hidden" name="fallback" value="1">
                @foreach($quizQuestions as $index => $question)
                <div class="card mb-3 quiz-question">
                  <div class="card-body">
                    <h6>{{ $index + 1 }}. {{ $question['text'] }}</h6>
                    
                    @if($question['type'] == 'multiple_choice')
                    <div class="options">
                      @foreach($question['options'] as $optIndex => $option)
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="answers[{{ $index }}]" id="quiz_{{ $index }}_{{ $optIndex }}" value="{{ $optIndex }}">
                        <label class="form-check-label" for="quiz_{{ $index }}_{{ $optIndex }}">
                          {{ $option }}
                        </label>
                      </div>
                      @endforeach
                    </div>
                    @endif
                  </div>
                </div>
                @endforeach
                
                <div class="d-grid gap-2">
                  <button type="submit" class="btn btn-primary" id="submit-quiz-btn">
                    <i class="fas fa-paper-plane me-2"></i> Kirim Jawaban
                  </button>
                </div>
              </div>
            </form>
            
            <!-- Quiz result for JavaScript AJAX display -->
            <div id="quiz-result" style="display: none;">
              <div class="text-center mb-3">
                <h5 class="result-message"></h5>
                <h1 class="display-4 score-display"></h1>
                <div class="quiz-stats mt-3">
                  <div class="d-flex justify-content-center gap-4 mb-2">
                    <div class="stat-item text-success">
                      <i class="fas fa-check-circle"></i> <span id="correct-count">0</span> Benar
                    </div>
                    <div class="stat-item text-danger">
                      <i class="fas fa-times-circle"></i> <span id="incorrect-count">0</span> Salah
                    </div>
                    <div class="stat-item text-primary">
                      <i class="fas fa-question-circle"></i> <span id="total-questions">0</span> Total
                    </div>
                  </div>
                </div>
                <p class="pass-fail-message"></p>
              </div>
              
              <div id="question-results" class="mt-4">
                <h6 class="mb-3 fw-bold">Detail Jawaban:</h6>
                <div id="result-items" class="mb-3">
                  <!-- Question results will be inserted here -->
                </div>
              </div>
              
              <div class="d-grid gap-2 mt-4">
                <button type="button" class="btn btn-warning" id="retry-quiz-btn">
                  <i class="fas fa-redo me-2"></i> Coba Lagi
                </button>
                <a href="{{ route('learning.materials') }}" class="btn btn-secondary">
                  <i class="fas fa-list me-2"></i> Kembali ke Daftar Materi
                </a>
              </div>
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Jika sudah ada hasil quiz (dari session), sembunyikan form
      @if(session('quiz_result'))
        document.getElementById('quiz-form').style.display = 'none';
        
        // Reset jawaban untuk percobaan berikutnya
        fetch('{{ route('learning.material.save-answers', $material->id) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            answers: {},
            reset: true
          })
        })
        .then(response => response.json())
        .then(data => {
          console.log('Answers reset successfully after showing result');
          
                      // Jika auto_reset diaktifkan, redirect ke halaman kuis baru setelah beberapa detik
            @if(session('auto_reset') && !session('quiz_result.passed'))
              // Beri waktu untuk melihat hasil dulu (3 detik)
              setTimeout(function() {
                // Redirect ke halaman kuis baru dengan parameter reset
                window.location.href = '{{ route('learning.material.quiz.show', $material->id) }}?reset_timer=1&reset_answers=1&_=' + Date.now();
              }, 3000); // Tunggu 3 detik agar siswa bisa melihat hasil
            @endif
        })
        .catch(error => {
          console.error('Error resetting answers after showing result:', error);
        });
      @endif
      
      // FORCE RESET ALL ANSWERS - Ini lebih agresif untuk memastikan reset benar-benar terjadi
      @if(isset($resetAnswers) && $resetAnswers)
        console.log('FORCING ANSWER RESET: Clearing all selections');
        
        // Clear all radio button selections
        const allRadios = document.querySelectorAll('input[type="radio"]');
        allRadios.forEach(radio => {
          radio.checked = false;
        });
        
        // Force browser to forget form state
        const quizForm = document.getElementById('quiz-form');
        if (quizForm) {
          quizForm.reset();
          
          // Add timestamp to prevent browser from restoring previous state
          const timestamp = document.createElement('input');
          timestamp.type = 'hidden';
          timestamp.name = '_reset';
          timestamp.value = Date.now();
          quizForm.appendChild(timestamp);
          
          // Tambahkan hidden field untuk menandai bahwa ini adalah reset
          const resetField = document.createElement('input');
          resetField.type = 'hidden';
          resetField.name = 'reset_confirmed';
          resetField.value = '1';
          quizForm.appendChild(resetField);
        }
        
        // Clear any stored answers in localStorage too
        try {
          localStorage.removeItem('quiz_{{ $material->id }}_answers');
        } catch (e) {
          console.error('Failed to clear localStorage:', e);
        }
        
        // Tambahan: Hapus semua jawaban yang tersimpan di database
        // Gunakan XMLHttpRequest synchronous untuk memastikan ini selesai sebelum halaman dirender
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route('learning.material.save-answers', $material->id) }}', false); // false = synchronous
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        xhr.send(JSON.stringify({
          answers: {},
          reset: true
        }));
        
        console.log('Database answers cleared on page load (synchronous)');
      @endif
      
      // Variables for timers
      let statusCheckInterval;
      let saveAnswersInterval;
      let lastAnswerChange = 0;
      let pendingSave = false;
      
      // Get initial timer data
      let initialServerData = {
        remaining_seconds: {{ $remainingSeconds ?? 0 }},
        display_time: "{{ sprintf('%02d:%02d', floor(($remainingSeconds ?? 0) / 60), ($remainingSeconds ?? 0) % 60) }}",
        is_time_up: {{ ($remainingSeconds ?? 0) <= 0 ? 'true' : 'false' }}
      };
      
      // TIMER MANAGEMENT - Completely client-side with occasional server sync
      let quizTimer = {
        endTimeStamp: Date.now() + (initialServerData.remaining_seconds * 1000),
        timerInterval: null,
        lastServerSync: Date.now(),
        timerDisplay: document.getElementById('timer-display'),
        countdownElement: document.getElementById('countdown-timer'),
        isTimeUp: initialServerData.is_time_up,
        syncInProgress: false,
        
        // Start local timer that runs every second
        start: function() {
          if (this.isTimeUp) return;
          
          // Clear any existing interval
          if (this.timerInterval) clearInterval(this.timerInterval);
          
          // Initial display update
          this.update();
          
          // Set timer to update every second - PURELY CLIENT SIDE
          this.timerInterval = setInterval(() => this.update(), 1000);
          
          // Schedule ONE server sync after 30 seconds
          // Each sync will schedule the next one - no intervals
          setTimeout(() => this.syncWithServer(), 30000);
          
          console.log('Quiz timer started - next sync in 30 seconds');
        },
        
        // Update timer display using local time calculation ONLY
        update: function() {
          if (this.isTimeUp) return;
          
          const now = Date.now();
          const remainingMs = this.endTimeStamp - now;
          const remainingSeconds = Math.max(0, Math.floor(remainingMs / 1000));
          
          // Format display
          const minutes = Math.floor(remainingSeconds / 60);
          const seconds = remainingSeconds % 60;
          const display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
          
          // Update display element
          if (this.timerDisplay) {
            this.timerDisplay.textContent = display;
          }
          
          // Update styles based on time remaining
          this.updateStyles(remainingSeconds);
          
          // Check if time is up
          if (remainingSeconds <= 0) {
            this.handleTimeUp();
          }
        },
        
        // Update visual styling based on remaining time
        updateStyles: function(remainingSeconds) {
          if (!this.timerDisplay || !this.countdownElement) return;
          
          if (remainingSeconds < 60) { // less than 1 minute
            this.timerDisplay.parentElement.classList.add('bg-danger');
            this.timerDisplay.parentElement.classList.add('pulse-animation');
          } else if (remainingSeconds < 300) { // less than 5 minutes
            this.timerDisplay.parentElement.classList.remove('bg-info');
            this.timerDisplay.parentElement.classList.add('bg-warning');
          }
        },
        
        // Sync with server ONCE, then schedule next sync
        syncWithServer: function() {
          if (this.isTimeUp || this.syncInProgress) return;
          
          // Prevent multiple simultaneous syncs
          this.syncInProgress = true;
          
          fetch('{{ route('learning.material.sync-timer', $material->id) }}')
            .then(response => response.json())
            .then(data => {
              // Update end time based on server data
              this.endTimeStamp = Date.now() + (data.remaining_seconds * 1000);
              this.lastServerSync = Date.now();
              this.syncInProgress = false;
              
              // Check if server says time is up
              if (data.is_time_up) {
                this.handleTimeUp();
                return;
              }
              
              // Schedule next sync in 30 seconds - NOT an interval
              setTimeout(() => this.syncWithServer(), 30000);
              console.log('Timer synced with server - next sync in 30 seconds');
            })
            .catch(error => {
              console.error('Error syncing timer:', error);
              this.syncInProgress = false;
              
              // Try again in 60 seconds if there was an error
              setTimeout(() => this.syncWithServer(), 60000);
            });
        },
        
        // Handle timer expiration
        handleTimeUp: function() {
          if (this.isTimeUp) return;
          
          this.isTimeUp = true;
          if (this.timerInterval) clearInterval(this.timerInterval);
          
          if (this.timerDisplay) {
            this.timerDisplay.textContent = "00:00";
          }
          
          if (this.countdownElement) {
            this.countdownElement.classList.add('bg-danger', 'text-white');
          }
          
          // Auto submit the form
          const submitBtn = document.getElementById('submit-quiz-btn');
          if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-clock me-2"></i> Waktu Habis! Mengirim jawaban...';
            submitBtn.disabled = true;
          }
          
          // Save answers one last time
          saveQuizAnswers(true);
          
          // Show message and redirect after a small delay
          setTimeout(function() {
            alert('Waktu pengerjaan kuis telah habis. Anda akan diarahkan ke halaman materi.');
            window.location.href = '{{ route('learning.materials') }}';
          }, 500);
        },
        
        // Called when page visibility changes
        handleVisibilityChange: function() {
          if (document.visibilityState === 'visible' && !this.isTimeUp) {
            // Force sync with server when tab becomes visible again
            this.syncWithServer();
          }
        }
      };
      
      // Function to check quiz status and redirect if necessary
      let lastStatusCheck = 0;
      let statusCheckInProgress = false;
      
      function checkQuizStatus() {
        // Skip regular checks if timer is up
        if (quizTimer.isTimeUp) return;
        
        // Prevent multiple simultaneous checks
        if (statusCheckInProgress) return;
        
        // Limit check frequency
        const now = Date.now();
        if (now - lastStatusCheck < 60000) return; // Only check once per minute
        
        statusCheckInProgress = true;
        lastStatusCheck = now;
        
        fetch('{{ route('learning.material.check-status', $material->id) }}?in_progress=1')
          .then(response => response.json())
          .then(data => {
            statusCheckInProgress = false;
            
            if (data.redirect) {
              // Show alert and redirect
              alert(data.message);
              
              // Force redirect to materials page
              if (data.redirect_url.includes('material.quiz.show')) {
                window.location.href = '{{ route('learning.materials') }}';
              } else {
                window.location.href = data.redirect_url;
              }
            }
          })
          .catch(error => {
            console.error('Error checking quiz status:', error);
            statusCheckInProgress = false;
          });
      }
      
      // Very infrequent status checks (once per minute)
      // Use setTimeout instead of setInterval to prevent queue buildup
      function scheduleNextStatusCheck() {
        setTimeout(function() {
          checkQuizStatus();
          scheduleNextStatusCheck(); // Schedule next check
        }, 60000);
      }
      
      // Start the status check cycle
      scheduleNextStatusCheck();
      
      // Check once on page load
      checkQuizStatus();
      
      // Function to stop all timers
      function stopAllTimers() {
        if (quizTimer.timerInterval) clearInterval(quizTimer.timerInterval);
        // No need to clear statusCheckInterval or saveAnswersInterval since we're using setTimeout
      }
      
      // Function to save current quiz answers to server
      let saveInProgress = false;
      let lastSaveTime = 0;
      
      function saveQuizAnswers(force = false) {
        const now = Date.now();
        
        // Prevent multiple simultaneous saves
        if (saveInProgress) {
          pendingSave = true;
          return;
        }
        
        // Only save if answers have changed or we're forcing a save
        // Kurangi interval save menjadi 5 detik jika tidak dipaksa
        if (!force && ((now - lastAnswerChange < 1000) || (now - lastSaveTime < 5000))) {
          pendingSave = true;
          return;
        }
        
        const quizForm = document.getElementById('quiz-form');
        if (!quizForm) return;
        
        // Collect answers
        const formData = new FormData(quizForm);
        const answers = {};
        
        for (const [key, value] of formData.entries()) {
          const match = key.match(/answers\[(\d+)\]/);
          if (match) {
            const index = match[1];
            answers[index] = value;
          }
        }
        
        // Selalu simpan, bahkan jika tidak ada jawaban
        // Reset pending flag
        pendingSave = false;
        saveInProgress = true;
        lastSaveTime = now;
        
        // Send to server
        fetch('{{ route('learning.material.save-answers', $material->id) }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            answers: answers
          })
        })
        .then(response => response.json())
        .then(data => {
          console.log('Answers saved successfully');
          saveInProgress = false;
        })
        .catch(error => {
          console.error('Error saving answers:', error);
          saveInProgress = false;
        });
      }
      
      // Save answers only when changed (with debounce) or on very infrequent interval
      function debouncedSave() {
        if (pendingSave) {
          saveQuizAnswers(true);
        }
      }
      
      // Use setTimeout for periodic saves instead of setInterval
      function scheduleNextSave() {
        setTimeout(function() {
          debouncedSave();
          scheduleNextSave(); // Schedule next save
        }, 10000); // Simpan setiap 10 detik untuk memastikan jawaban tersimpan
      }
      
      // Start the save cycle
      scheduleNextSave();
      
      // Simpan jawaban sekali pada awal load halaman dan setiap kali ada perubahan
      setTimeout(function() {
        saveQuizAnswers(true);
        console.log('Initial save completed');
      }, 2000);
      
      // Debug: Log status setiap 5 detik untuk memastikan jawaban tersimpan
      setInterval(function() {
        const quizForm = document.getElementById('quiz-form');
        if (!quizForm) return;
        
        const formData = new FormData(quizForm);
        const answers = {};
        
        for (const [key, value] of formData.entries()) {
          const match = key.match(/answers\[(\d+)\]/);
          if (match) {
            const index = match[1];
            answers[index] = value;
          }
        }
        
        console.log('Current answers state:', answers);
      }, 5000);
      
      // Restore previously saved answers
      function restoreSavedAnswers() {
        // Selalu coba ambil jawaban terbaru dari server terlebih dahulu
        fetch('{{ route('learning.material.get-answers', $material->id) }}')
        .then(response => response.json())
        .then(data => {
          console.log('Fetched latest answers from server:', data);
          
          if (data.success && data.answers) {
            // Loop through saved answers and select them
            Object.entries(data.answers).forEach(([index, value]) => {
              const input = document.querySelector(`input[name="answers[${index}]"][value="${value}"]`);
              if (input) {
                input.checked = true;
              }
            });
          } else {
            // Fallback ke jawaban dari template jika tidak bisa ambil dari server
            @if(!empty($tempAnswers) && !isset($resetAnswers))
            console.log('Using template answers as fallback');
            const savedAnswers = @json($tempAnswers);
            
            // Loop through saved answers and select them
            Object.entries(savedAnswers).forEach(([index, value]) => {
              const input = document.querySelector(`input[name="answers[${index}]"][value="${value}"]`);
              if (input) {
                input.checked = true;
              }
            });
            @endif
          }
        })
        .catch(error => {
          console.error('Error fetching latest answers:', error);
          
          // Fallback ke jawaban dari template jika gagal
          @if(!empty($tempAnswers) && !isset($resetAnswers))
          console.log('Using template answers as fallback due to error');
          const savedAnswers = @json($tempAnswers);
          
          // Loop through saved answers and select them
          Object.entries(savedAnswers).forEach(([index, value]) => {
            const input = document.querySelector(`input[name="answers[${index}]"][value="${value}"]`);
            if (input) {
              input.checked = true;
            }
          });
          @endif
        });
      }
      
              // Also save when tab becomes hidden or user navigates away
      document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
          saveQuizAnswers(true);
        } else if (document.visibilityState === 'visible') {
          quizTimer.handleVisibilityChange();
          checkQuizStatus();
        }
      });
      
      // Simpan jawaban saat pengguna akan meninggalkan halaman
      window.addEventListener('beforeunload', function(e) {
        // Gunakan synchronous XMLHttpRequest untuk memastikan data tersimpan sebelum halaman ditutup
        const xhr = new XMLHttpRequest();
        const quizForm = document.getElementById('quiz-form');
        if (!quizForm) return;
        
        // Collect answers
        const formData = new FormData(quizForm);
        const answers = {};
        
        for (const [key, value] of formData.entries()) {
          const match = key.match(/answers\[(\d+)\]/);
          if (match) {
            const index = match[1];
            answers[index] = value;
          }
        }
        
        // Kirim request secara synchronous
        xhr.open('POST', '{{ route('learning.material.save-answers', $material->id) }}', false); // false = synchronous
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        xhr.send(JSON.stringify({answers: answers}));
        
        // Tampilkan pesan konfirmasi (opsional)
        e.returnValue = 'Jawaban Anda telah disimpan.';
      });
      
      // Reset answers button handler
      const resetAnswersBtn = document.getElementById('reset-answers-btn');
      if (resetAnswersBtn) {
        resetAnswersBtn.addEventListener('click', function() {
          if (confirm('Apakah Anda yakin ingin mengosongkan semua jawaban?')) {
            // Reset form locally
            const quizForm = document.getElementById('quiz-form');
            if (quizForm) {
              quizForm.reset();
            }
            
            // Reset answers in database and reload
            resetAllSavedAnswers();
          }
        });
      }
      
      // Restore saved answers on page load
      restoreSavedAnswers();
      
      // Start timer if quiz has time limit
      @if($quiz && $quiz->time_limit)
        quizTimer.start();
      @endif
      
      // Quiz submission
      const quizForm = document.getElementById('quiz-form');
      const quizQuestions = document.getElementById('quiz-questions');
      const quizResult = document.getElementById('quiz-result');
      
      if (quizForm) {
        quizForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          // Stop all timers when submitting
          stopAllTimers();
          
          // Disable submit button to prevent multiple submissions
          const submitBtn = document.getElementById('submit-quiz-btn');
          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses...';
          }
          
          // Collect answers
          const formData = new FormData(quizForm);
          const answers = {};
          
          for (const [key, value] of formData.entries()) {
            const match = key.match(/answers\[(\d+)\]/);
            if (match) {
              const index = match[1];
              answers[index] = value;
            }
          }
          
          // Submit quiz
          fetch('{{ route("learning.material.quiz", $material->id) }}?ajax=1', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
              answers: answers
            })
          })
          .then(response => {
            return response.json();
          })
          .then(data => {
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Kirim Jawaban';
            }
            
            if (quizQuestions && quizResult) {
              quizQuestions.style.display = 'none';
              quizResult.style.display = 'block';
              
              const scoreDisplay = document.querySelector('.score-display');
              const resultMessage = document.querySelector('.result-message');
              const passFailMessage = document.querySelector('.pass-fail-message');
              
              // Ensure score is always a number, default to 0 if undefined
              const score = data.score !== undefined ? parseInt(data.score) : 0;
              if (scoreDisplay) scoreDisplay.textContent = score + '%';
              
              // Update statistics
              document.getElementById('correct-count').textContent = data.correct_count || 0;
              document.getElementById('incorrect-count').textContent = data.incorrect_count || 0;
              document.getElementById('total-questions').textContent = data.total_questions || 0;
              
              // Display question results
              const resultItemsContainer = document.getElementById('result-items');
              if (resultItemsContainer && data.question_results) {
                resultItemsContainer.innerHTML = '';
                
                Object.entries(data.question_results).forEach(([index, result]) => {
                  const questionNumber = parseInt(index) + 1;
                  const isCorrect = result.is_correct;
                  
                  const resultItem = document.createElement('div');
                  resultItem.className = `card mb-2 ${isCorrect ? 'border-success' : 'border-danger'}`;
                  
                  let optionText = '';
                  if (result.type === 'multiple_choice' && result.user_answer !== null) {
                    // Get the option text from the form
                    const questionElement = document.querySelector(`.quiz-question:nth-child(${questionNumber})`);
                    if (questionElement) {
                      const optionElement = questionElement.querySelector(`label[for="quiz_${index}_${result.user_answer}"]`);
                      if (optionElement) {
                        optionText = optionElement.textContent.trim();
                      }
                    }
                  }
                  
                  resultItem.innerHTML = `
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Pertanyaan ${questionNumber}</h6>
                        <span class="badge ${isCorrect ? 'bg-success' : 'bg-danger'}">
                          ${isCorrect ? '<i class="fas fa-check"></i> Benar' : '<i class="fas fa-times"></i> Salah'}
                        </span>
                      </div>
                      <p class="mt-2 mb-1"><strong>Pertanyaan:</strong> ${result.question_text}</p>
                      <p class="mb-1"><strong>Jawaban Anda:</strong> ${result.user_answer_text || optionText || result.user_answer || 'Tidak dijawab'}</p>
                      ${!isCorrect ? `<p class="mb-0 text-success"><strong>Jawaban Benar:</strong> ${result.correct_answer_text || result.correct_answer}</p>` : ''}
                    </div>
                  `;
                  
                  resultItemsContainer.appendChild(resultItem);
                });
              }
              
              if (data.passed) {
                if (resultMessage) {
                  resultMessage.textContent = 'Selamat! Anda telah lulus kuis.';
                  resultMessage.className = 'text-success';
                }
                if (scoreDisplay) scoreDisplay.className = 'display-4 score-display text-success';
                if (passFailMessage) passFailMessage.textContent = `Skor minimum kelulusan adalah ${data.passing_score}%. Anda dapat melanjutkan ke materi berikutnya.`;
                
                // Hide retry button if passed
                const retryBtn = document.getElementById('retry-quiz-btn');
                if (retryBtn) retryBtn.style.display = 'none';
                
                // If can proceed to next material, add a button
                if (data.next_material_id) {
                  const nextButton = document.createElement('a');
                  nextButton.href = `/learning-material/${data.next_material_id}`;
                  nextButton.className = 'btn btn-success';
                  nextButton.innerHTML = '<i class="fas fa-arrow-right me-2"></i> Lanjut ke Materi Berikutnya';
                  
                  const retryQuizBtn = document.getElementById('retry-quiz-btn');
                  if (retryQuizBtn) retryQuizBtn.insertAdjacentElement('afterend', nextButton);
                }
                
                // Show post-test button if eligible
                if (data.can_take_post_test) {
                  const postTestButton = document.createElement('a');
                  postTestButton.href = "{{ route('post-test.language', session('language', 'id')) }}";
                  postTestButton.className = 'btn btn-primary mt-2';
                  postTestButton.innerHTML = '<i class="fas fa-file-alt me-2"></i> Mulai Post-Test';
                  
                  const quizResultGrid = document.querySelector('#quiz-result .d-grid');
                  if (quizResultGrid) quizResultGrid.appendChild(postTestButton);
                }
              } else {
                if (resultMessage) {
                  resultMessage.textContent = 'Maaf, Anda belum lulus kuis.';
                  resultMessage.className = 'text-danger';
                }
                if (scoreDisplay) scoreDisplay.className = 'display-4 score-display text-danger';
                if (passFailMessage) passFailMessage.textContent = `Skor minimum kelulusan adalah ${data.passing_score}%. Silakan coba lagi.`;
                
                // Ensure retry button is visible and has event handler
                const retryBtn = document.getElementById('retry-quiz-btn');
                if (retryBtn) {
                  retryBtn.style.display = 'block';
                  // Clear any existing event listeners
                  const newRetryBtn = retryBtn.cloneNode(true);
                  retryBtn.parentNode.replaceChild(newRetryBtn, retryBtn);
                  
                  // Add event listener
                  newRetryBtn.addEventListener('click', function() {
                    // Reset answers and reload
                    resetAllSavedAnswers();
                  });
                }
              }
              
              // Reset temporary answers in the database to ensure a clean slate for next attempt
              // Reset form locally first
              const quizForm = document.getElementById('quiz-form');
              if (quizForm) {
                quizForm.reset();
                
                // Clear all radio button selections
                const allRadios = document.querySelectorAll('input[type="radio"]');
                allRadios.forEach(radio => {
                  radio.checked = false;
                });
              }
              
              // Kemudian reset di database
              if (!data.passed) {
                // Beri waktu untuk melihat hasil dulu (3 detik)
                setTimeout(function() {
                  // Gunakan XMLHttpRequest synchronous untuk memastikan data benar-benar tereset
                  const xhr = new XMLHttpRequest();
                  xhr.open('POST', '{{ route('learning.material.save-answers', $material->id) }}', false); // false = synchronous
                  xhr.setRequestHeader('Content-Type', 'application/json');
                  xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                  xhr.send(JSON.stringify({
                    answers: {},
                    reset: true
                  }));
                  
                  console.log('Answers cleared after submission (synchronous)');
                  
                  // Redirect ke halaman kuis baru dengan parameter reset
                  window.location.href = '{{ route('learning.material.quiz.show', $material->id) }}?reset_timer=1&reset_answers=1&force_clear=1&_=' + Date.now();
                }, 3000); // Tunggu 3 detik agar siswa bisa melihat hasil
              } else {
                // Jika lulus, hanya reset jawaban tanpa redirect
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '{{ route('learning.material.save-answers', $material->id) }}', false); // false = synchronous
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                xhr.send(JSON.stringify({
                  answers: {},
                  reset: true
                }));
                
                console.log('Answers cleared after successful submission (synchronous)');
              }
            }
          })
          .catch(error => {
            console.error('Error submitting quiz:', error);
            alert('Terjadi kesalahan saat mengirim jawaban kuis.');
            
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Kirim Jawaban';
            }
          });
        });
        
        // Add change event listeners to all radio buttons to track answer changes
        const radioButtons = quizForm.querySelectorAll('input[type="radio"]');
        radioButtons.forEach(radio => {
          radio.addEventListener('change', function() {
            lastAnswerChange = Date.now();
            pendingSave = true;
            
            // Simpan segera saat ada perubahan jawaban
            saveQuizAnswers(true);
          });
        });
      }
      
      // Retry quiz
      const retryQuizBtn = document.getElementById('retry-quiz-btn');
      
      if (retryQuizBtn && quizForm && quizResult && quizQuestions) {
        retryQuizBtn.addEventListener('click', function() {
          // Reset form
          quizForm.reset();
          
          // Show questions, hide result
          quizResult.style.display = 'none';
          quizQuestions.style.display = 'block';
          
          // Hapus semua jawaban yang tersimpan sebelumnya
          resetAllSavedAnswers();
        });
      }
      
      // Fungsi untuk menghapus semua jawaban tersimpan
      function resetAllSavedAnswers() {
        // Reset form locally first
        const quizForm = document.getElementById('quiz-form');
        if (quizForm) {
          quizForm.reset();
          
          // Clear all radio button selections
          const allRadios = document.querySelectorAll('input[type="radio"]');
          allRadios.forEach(radio => {
            radio.checked = false;
          });
        }
        
        // Clear saved answers in database before refreshing - use synchronous request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route('learning.material.save-answers', $material->id) }}', false); // false = synchronous
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        xhr.send(JSON.stringify({
          answers: {},
          reset: true
        }));
        
        console.log('Answers reset successfully (synchronous)');
        
        // Redirect to a fresh quiz page with reset parameters
        window.location.href = '{{ route('learning.material.quiz.show', $material->id) }}?reset_timer=1&reset_answers=1&force_clear=1&_=' + Date.now();
      }
    });
  </script>
  
  <style>
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    
    .pulse-animation {
      animation: pulse 1s infinite;
    }
  </style>
  @endpush
</x-app-layout> 