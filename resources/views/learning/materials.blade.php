<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <h1>Materi Pembelajaran</h1>
        <p class="lead">Materi pembelajaran untuk level {{ $level }}</p>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Daftar Materi</h5>
          </div>
          <div class="card-body">
            @if($materials->count() > 0)
              @if($canTakePostTest)
              <div class="alert alert-success mb-4">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h5><i class="fas fa-award me-2"></i> Selamat!</h5>
                    <p class="mb-0">Anda telah menyelesaikan semua materi dan kuis pada level ini. Anda dapat mengambil post-test untuk melanjutkan ke level berikutnya.</p>
                  </div>
                  <a href="{{ route('post-test.language', session('language', 'id')) }}" class="btn btn-success">
                    <i class="fas fa-file-alt me-2"></i> Mulai Post-Test
                  </a>
                </div>
              </div>
              @else
              <div class="alert alert-info mb-4">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h5><i class="fas fa-info-circle me-2"></i> Informasi Post-Test</h5>
                    <p class="mb-0">Anda harus menyelesaikan semua materi dan lulus semua kuis pada level ini sebelum dapat mengambil Post-Test.</p>
                  </div>
                  <button class="btn btn-secondary" disabled>
                    <i class="fas fa-lock me-2"></i> Post-Test
                  </button>
                </div>
              </div>
              @endif
              <div class="list-group">
                @foreach($materials as $material)
                <div class="list-group-item list-group-item-action {{ isset($userProgress[$material->id]) && !$material->canUserAccess(Auth::id()) ? 'disabled' : '' }}">
                  <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">{{ $material->title }}</h5>
                    <div>
                      @if(isset($userProgress[$material->id]))
                        @if($userProgress[$material->id]->completed && $userProgress[$material->id]->quiz_passed)
                          <span class="badge bg-success me-2"><i class="fas fa-check-circle me-1"></i> Selesai & Lulus</span>
                        @elseif($userProgress[$material->id]->completed && !$userProgress[$material->id]->quiz_passed && $material->quiz)
                          <span class="badge bg-warning text-dark me-2"><i class="fas fa-exclamation-circle me-1"></i> Belum Lulus Kuis</span>
                        @elseif($userProgress[$material->id]->completed)
                          <span class="badge bg-info me-2"><i class="fas fa-check me-1"></i> Selesai</span>
                        @elseif(!$material->canUserAccess(Auth::id()))
                          <span class="badge bg-secondary me-2"><i class="fas fa-lock me-1"></i> Terkunci</span>
                        @else
                          <span class="badge bg-light text-dark me-2"><i class="fas fa-book me-1"></i> Belum Selesai</span>
                        @endif
                      @endif
                      <span class="badge bg-primary">Level {{ $material->level }}</span>
                    </div>
                  </div>
                  <p class="mb-1">{{ $material->description }}</p>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted">{{ $material->duration }} menit</small>
                    <div>
                        <a href="{{ route('learning.material.quiz.show', $material->id) }}?start_new=1" class="btn btn-sm btn-warning me-2">
                          <i class="fas fa-question-circle me-1"></i> 
                            Kerjakan Kuis
                        </a>

                      
                      @if(!$material->canUserAccess(Auth::id()))
                        <button class="btn btn-sm btn-secondary" disabled>
                          <i class="fas fa-lock me-1"></i> Selesaikan Materi Sebelumnya
                        </button>
                      @else
                        <a href="{{ route('learning.material.show', $material->id) }}" class="btn btn-sm btn-primary">
                          <i class="fas fa-book-open me-1"></i> Pelajari
                        </a>
                      @endif
                    </div>
                  </div>
                </div>
                
                <!-- Modal for Material Content -->
                <div class="modal fade" id="materialModal{{ $material->id }}" tabindex="-1" aria-labelledby="materialModalLabel{{ $material->id }}" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="materialModalLabel{{ $material->id }}">{{ $material->title }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-4">
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
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="button" class="btn btn-primary mark-complete" data-material-id="{{ $material->id }}">Tandai Selesai</button>
                      </div>
                    </div>
                  </div>
                </div>
                @endforeach
              </div>
            @else
              <div class="alert alert-info">
                <p class="mb-0">Belum ada materi pembelajaran untuk level {{ $level }}. Silakan kembali nanti.</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Handle check answer buttons
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
      
      // Handle mark complete buttons
      const markCompleteButtons = document.querySelectorAll('.mark-complete');
      markCompleteButtons.forEach(button => {
        button.addEventListener('click', function() {
          const materialId = this.dataset.materialId;
          this.textContent = 'Ditandai Selesai';
          this.classList.remove('btn-primary');
          this.classList.add('btn-success');
          this.disabled = true;
          
          // Here you would typically send a request to the server to mark the material as completed
          // This is a placeholder for that functionality
          console.log(`Material ${materialId} marked as complete`);
        });
      });
    });
  </script>
  @endpush
</x-app-layout> 