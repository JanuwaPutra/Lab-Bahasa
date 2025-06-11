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
                {{ session('quiz_result.score') }}%
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
            
            @if($quiz->time_limit)
            <div class="alert alert-warning mb-4" id="timer-container">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <i class="fas fa-clock me-2"></i> Waktu Tersisa
                </div>
                <div id="quiz-timer" class="fw-bold">{{ $quiz->time_limit }}:00</div>
              </div>
              <div class="progress mt-2" style="height: 5px;">
                <div id="timer-progress" class="progress-bar bg-warning" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
            @endif
            
            <div class="alert alert-info mb-4">
              <i class="fas fa-info-circle me-2"></i> Kerjakan kuis ini dengan jujur dan teliti. Anda tidak dapat kembali ke materi selama mengerjakan kuis.
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
      @endif
      
      // Quiz timer functionality
      @if($quiz->time_limit && !session('quiz_result'))
      const timerElement = document.getElementById('quiz-timer');
      const timerProgressBar = document.getElementById('timer-progress');
      const submitBtn = document.getElementById('submit-quiz-btn');
      
      if (timerElement && timerProgressBar && submitBtn) {
        const quizTimeLimit = {{ $quiz->time_limit }}; // In minutes
        const totalSeconds = quizTimeLimit * 60;
        let secondsRemaining = totalSeconds;
        let timerInterval;
        
        // Function to format time as MM:SS
        function formatTime(seconds) {
          const mins = Math.floor(seconds / 60);
          const secs = seconds % 60;
          return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
        }
        
        // Function to update timer display
        function updateTimer() {
          secondsRemaining--;
          
          if (secondsRemaining <= 0) {
            clearInterval(timerInterval);
            timerElement.textContent = '0:00';
            timerProgressBar.style.width = '0%';
            timerElement.classList.add('text-danger');
            
            // Auto-submit the quiz
            alert('Waktu habis! Jawaban Anda akan dikirim secara otomatis.');
            document.getElementById('quiz-form').submit();
            return;
          }
          
          // Update timer display
          timerElement.textContent = formatTime(secondsRemaining);
          
          // Update progress bar
          const percentRemaining = (secondsRemaining / totalSeconds) * 100;
          timerProgressBar.style.width = `${percentRemaining}%`;
          
          // Change color when time is running low
          if (secondsRemaining <= 60) { // Last minute
            timerElement.classList.add('text-danger');
            timerProgressBar.classList.remove('bg-warning');
            timerProgressBar.classList.add('bg-danger');
          } else if (secondsRemaining <= 180) { // Last 3 minutes
            timerElement.classList.add('text-warning');
          }
        }
        
        // Start the timer
        timerInterval = setInterval(updateTimer, 1000);
        
        // Save timer state to session storage to handle page refreshes
        window.addEventListener('beforeunload', function() {
          sessionStorage.setItem('quizTimer', secondsRemaining);
          sessionStorage.setItem('quizStartTime', Date.now() - ((totalSeconds - secondsRemaining) * 1000));
        });
        
        // Check if there's a saved timer state
        const savedTime = sessionStorage.getItem('quizTimer');
        const savedStartTime = sessionStorage.getItem('quizStartTime');
        
        if (savedTime && savedStartTime) {
          const elapsedSince = Math.floor((Date.now() - savedStartTime) / 1000);
          secondsRemaining = Math.max(0, savedTime - elapsedSince);
          
          // Update display immediately
          timerElement.textContent = formatTime(secondsRemaining);
          const percentRemaining = (secondsRemaining / totalSeconds) * 100;
          timerProgressBar.style.width = `${percentRemaining}%`;
          
          if (secondsRemaining <= 0) {
            // Time already expired
            timerElement.textContent = '0:00';
            timerProgressBar.style.width = '0%';
            timerElement.classList.add('text-danger');
            alert('Waktu habis! Jawaban Anda akan dikirim secara otomatis.');
            document.getElementById('quiz-form').submit();
          }
        }
      }
      @endif
      
      // Quiz submission
      const quizForm = document.getElementById('quiz-form');
      const quizQuestions = document.getElementById('quiz-questions');
      const quizResult = document.getElementById('quiz-result');
      
      // Clear timer data from sessionStorage when quiz is submitted
      function clearTimerData() {
        sessionStorage.removeItem('quizTimer');
        sessionStorage.removeItem('quizStartTime');
      }
      
      if (quizForm) {
        quizForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
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
          
                      console.log('Submitting answers:', answers);
          
          // Clear timer data when submitting
          clearTimerData();
          
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
            console.log('Response status:', response.status);
            return response.json();
          })
          .then(data => {
            console.log('Quiz submission response:', data);
            
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
              
              if (scoreDisplay) scoreDisplay.textContent = data.score + '%';
              
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
          
          // Reset timer if exists
          @if($quiz->time_limit && !session('quiz_result'))
          // Reset timer state
          if (timerElement && timerProgressBar) {
            // Clear any existing interval
            if (timerInterval) {
              clearInterval(timerInterval);
            }
            
            // Reset timer display
            const quizTimeLimit = {{ $quiz->time_limit }}; // In minutes
            const totalSeconds = quizTimeLimit * 60;
            secondsRemaining = totalSeconds;
            
            timerElement.textContent = formatTime(secondsRemaining);
            timerElement.classList.remove('text-danger', 'text-warning');
            
            timerProgressBar.style.width = '100%';
            timerProgressBar.classList.add('bg-warning');
            timerProgressBar.classList.remove('bg-danger');
            
            // Restart timer
            timerInterval = setInterval(updateTimer, 1000);
            
            // Update session storage
            sessionStorage.setItem('quizTimer', secondsRemaining);
            sessionStorage.setItem('quizStartTime', Date.now());
          }
          @endif
        });
      }
    });
  </script>
  @endpush
</x-app-layout> 