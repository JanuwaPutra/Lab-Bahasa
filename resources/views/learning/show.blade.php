<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>{{ $material->title }}</h1>
          <a href="{{ route('learning.materials') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Materi
          </a>
        </div>
        <p class="text-muted">
          <span class="badge bg-primary">Level {{ $material->level }}</span>

        </p>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Materi Pembelajaran</h5>
          </div>
          <div class="card-body">
            @if($material->description)
            <div class="alert alert-info">
              <p class="mb-0">{{ $material->description }}</p>
            </div>
            @endif
            
            <div class="material-content mb-4">
              {!! $material->content !!}
            </div>
            
            @if($material->video_url)
            <div class="ratio ratio-16x9 mb-4">
              <iframe src="{{ $material->video_url }}" title="{{ $material->title }}" allowfullscreen></iframe>
            </div>
            @endif
            
            @if($material->exercises)
            <div class="mt-4">
              <h5>Latihan</h5>
              <div class="exercises-container">
                @foreach($material->exercises as $index => $exercise)
                <div class="card mb-3 exercise">
                  <div class="card-body">
                    <h6>{{ $index + 1 }}. {{ $exercise['question'] }}</h6>
                    
                    @if($exercise['type'] == 'multiple_choice')
                    <div class="options">
                      @foreach($exercise['options'] as $optIndex => $option)
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="exercise{{ $material->id }}_{{ $index }}" id="exercise{{ $material->id }}_{{ $index }}_{{ $optIndex }}" value="{{ $optIndex }}">
                        <label class="form-check-label" for="exercise{{ $material->id }}_{{ $index }}_{{ $optIndex }}">
                          {{ $option }}
                        </label>
                      </div>
                      @endforeach
                    </div>
                    @elseif($exercise['type'] == 'text_input')
                    <div class="mb-3">
                      <input type="text" class="form-control" id="exercise{{ $material->id }}_{{ $index }}_answer" placeholder="Ketik jawaban Anda">
                    </div>
                    @endif
                    
                    <button class="btn btn-sm btn-primary check-answer" data-question-id="{{ $index }}" data-material-id="{{ $material->id }}">Periksa Jawaban</button>
                    <div class="feedback mt-2 d-none"></div>
                  </div>
                </div>
                @endforeach
              </div>
            </div>
            @endif
            
            @if(!$progress->completed)
            <div class="d-grid gap-2 mt-4">
              <button type="button" class="btn btn-success mark-complete-btn" data-material-id="{{ $material->id }}">
                <i class="fas fa-check-circle me-2"></i> Tandai Materi Selesai
              </button>
            </div>
            @endif
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Status</h5>
          </div>
          <div class="card-body">
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Status Materi
                @if($progress->completed)
                <span class="badge bg-success">Selesai</span>
                @else
                <span class="badge bg-warning">Belum Selesai</span>
                @endif
              </li>
              
              @if($quiz)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Status Kuis
                @if($progress->quiz_passed)
                <span class="badge bg-success">Lulus</span>
                @elseif($progress->quiz_attempts > 0)
                <span class="badge bg-warning">Belum Lulus</span>
                @else
                <span class="badge bg-info">Belum Dikerjakan</span>
                @endif
              </li>
              
              @if($progress->quiz_attempts > 0)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Nilai Kuis Terakhir
                <span class="badge bg-{{ $progress->quiz_passed ? 'success' : 'danger' }}">{{ $progress->quiz_score }}%</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                Percobaan Kuis
                <span class="badge bg-secondary">{{ $progress->quiz_attempts }}x</span>
              </li>
              @endif
              @endif
            </ul>

            
            @if($quiz && $progress->completed && $progress->quiz_passed)
            <div class="alert alert-success mt-3 mb-0">
              <i class="fas fa-check-circle me-2"></i> Anda telah lulus kuis ini. Anda dapat melanjutkan ke materi berikutnya.
            </div>
            @endif
          </div>
        </div>
        
        @if($quiz && $progress->completed)
        <div class="card mb-4">
          <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Informasi Kuis</h5>
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
            
            @if($progress->quiz_attempts > 0)
            <div class="mt-3">
              <a href="{{ route('quiz.history.detail', $progress->id) }}" class="btn btn-sm btn-info">
                <i class="fas fa-history me-1"></i> Lihat Riwayat Kuis Terakhir
              </a>
              <a href="{{ route('quiz.history.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-list me-1"></i> Semua Riwayat Kuis
              </a>
            </div>
            @endif
            
            @if($progress->quiz_passed)
            <div class="alert alert-success mb-0 mt-3">
              <i class="fas fa-check-circle me-2"></i> Anda telah lulus kuis untuk materi ini.
            </div>
            @else
            <div class="d-grid gap-2 mt-3">
              <a href="{{ route('learning.material.quiz.show', $material->id) }}?start_new=1" class="btn btn-warning">
                <i class="fas fa-question-circle me-2"></i> 
                  Mulai Kuis
              </a>
              @if($progress->quiz_attempts > 0 && $progress->quiz_end_time)
              <a href="{{ route('learning.material.quiz.show', $material->id) }}?start_new=1&reset_timer=1" class="btn btn-outline-danger mt-2">
                <i class="fas fa-redo me-2"></i> Mulai Ulang Timer
              </a>
              @endif
            </div>
            @endif
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Handle material completion
      const markCompleteBtn = document.querySelector('.mark-complete-btn');
      if (markCompleteBtn) {
        markCompleteBtn.addEventListener('click', function() {
          const materialId = this.dataset.materialId;
          
          fetch('{{ route("learning.material.complete", $material->id) }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
              material_id: materialId
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              this.disabled = true;
              this.innerHTML = '<i class="fas fa-check-circle me-2"></i> Materi Ditandai Selesai';
              
              // Reload page to update status
              window.location.reload();
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menandai materi selesai.');
          });
        });
      }
      
      // Handle check answer buttons for exercises
      const checkAnswerButtons = document.querySelectorAll('.check-answer');
      checkAnswerButtons.forEach(button => {
        button.addEventListener('click', function() {
          const questionId = this.dataset.questionId;
          const materialId = this.dataset.materialId;
          const exerciseContainer = this.closest('.exercise');
          const feedbackContainer = exerciseContainer.querySelector('.feedback');
          
          let answer;
          const radioInputs = exerciseContainer.querySelectorAll('input[type="radio"]');
          const textInput = exerciseContainer.querySelector('input[type="text"]');
          
          if (radioInputs.length > 0) {
            // Multiple choice
            radioInputs.forEach((input, index) => {
              if (input.checked) {
                answer = index;
              }
            });
          } else if (textInput) {
            // Text input
            answer = textInput.value;
          }
          
          if (answer === undefined || answer === '') {
            feedbackContainer.textContent = 'Silakan pilih atau masukkan jawaban terlebih dahulu.';
            feedbackContainer.classList.remove('d-none', 'alert-success', 'alert-danger');
            feedbackContainer.classList.add('alert', 'alert-warning');
            return;
          }
          
          // Send answer to server for evaluation
          fetch('{{ route("learning.evaluate") }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
              question_id: questionId,
              material_id: materialId,
              answer: answer
            })
          })
          .then(response => response.json())
          .then(data => {
            feedbackContainer.textContent = data.feedback;
            feedbackContainer.classList.remove('d-none', 'alert-warning', 'alert-danger', 'alert-success');
            feedbackContainer.classList.add('alert', data.is_correct ? 'alert-success' : 'alert-danger');
          })
          .catch(error => {
            console.error('Error:', error);
            feedbackContainer.textContent = 'Terjadi kesalahan saat memeriksa jawaban.';
            feedbackContainer.classList.remove('d-none', 'alert-success', 'alert-warning');
            feedbackContainer.classList.add('alert', 'alert-danger');
          });
        });
      });
    });
  </script>
  @endpush
</x-app-layout> 