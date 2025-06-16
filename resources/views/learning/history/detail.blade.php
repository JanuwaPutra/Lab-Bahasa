<x-app-layout>
  <div class="container py-4">
    <div class="row">
      <div class="col-md-12">

        
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Detail Kuis: {{ $material->title }}</h5>
              <a href="{{ route('quiz.history.index') }}" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left me-1"></i> Kembali
              </a>
            </div>
          </div>
          <div class="card-body">
            <div class="quiz-info mb-4">
              <div class="row">
                <div class="col-md-6">
                  <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      Judul Kuis
                      <span>{{ $quiz->title }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      Materi
                      <span>{{ $material->title }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      Level
                      <span class="badge rounded-pill bg-{{ $material->level == 1 ? 'success' : ($material->level == 2 ? 'warning' : 'danger') }}">
                        Level {{ $material->level }}
                      </span>
                    </li>
                  </ul>
                </div>
                <div class="col-md-6">
                  <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      Tanggal Pengerjaan
                      <span>{{ $progress->updated_at->format('d M Y, H:i') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      Skor
                      <span class="badge rounded-pill bg-{{ $progress->quiz_passed ? 'success' : 'danger' }}">
                        {{ $progress->quiz_score }}%
                      </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      Status
                      <span class="badge rounded-pill bg-{{ $progress->quiz_passed ? 'success' : 'danger' }}">
                        {{ $progress->quiz_passed ? 'Lulus' : 'Gagal' }}
                      </span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            
            <h5 class="mb-3">Detail Jawaban</h5>
            
            @php
              $correctCount = count(array_filter($questionResults, function($result) {
                return $result['is_correct'];
              }));
              
              $incorrectCount = count($questionResults) - $correctCount;
            @endphp
            
            <div class="quiz-stats mb-4 text-center">
              <div class="d-flex justify-content-center gap-4 mb-2">
                <div class="stat-item text-success">
                  <i class="fas fa-check-circle"></i> {{ $correctCount }} Benar
                </div>
                <div class="stat-item text-danger">
                  <i class="fas fa-times-circle"></i> {{ $incorrectCount }} Salah
                </div>
                <div class="stat-item text-primary">
                  <i class="fas fa-question-circle"></i> {{ count($questionResults) }} Total
                </div>
              </div>
              <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ ($correctCount / count($questionResults)) * 100 }}%" 
                  aria-valuenow="{{ ($correctCount / count($questionResults)) * 100 }}" aria-valuemin="0" aria-valuemax="100">
                  {{ $correctCount }}/{{ count($questionResults) }}
                </div>
              </div>
            </div>
            
            <div class="question-results">
              @foreach($questionResults as $index => $result)
                <div class="card mb-3 {{ $result['is_correct'] ? 'border-success' : 'border-danger' }}">
                  <div class="card-header {{ $result['is_correct'] ? 'bg-success text-white' : 'bg-danger text-white' }}">
                    <div class="d-flex justify-content-between align-items-center">
                      <h6 class="mb-0">Pertanyaan {{ $index + 1 }}</h6>
                      <span class="badge {{ $result['is_correct'] ? 'bg-light text-success' : 'bg-light text-danger' }}">
                        @if($result['is_correct'])
                          <i class="fas fa-check"></i> Benar
                        @else
                          <i class="fas fa-times"></i> Salah
                        @endif
                      </span>
                    </div>
                  </div>
                  <div class="card-body">
                    <p class="question-text fw-bold">{{ $result['question_text'] }}</p>
                    
                    @if($result['type'] === 'multiple_choice')
                      <div class="answer-section mt-2">
                        <p>
                          <span class="fw-bold">Jawaban Anda:</span> 
                          @if($result['user_answer'] === null)
                            <span class="text-muted">Tidak dijawab</span>
                          @else
                            <span class="{{ $result['is_correct'] ? 'text-success' : 'text-danger' }}">
                              {{ $result['user_answer_text'] ?: 'Opsi ' . $result['user_answer'] }}
                            </span>
                          @endif
                        </p>
                        
                        @if(!$result['is_correct'])
                          <p class="text-success">
                            <span class="fw-bold">Jawaban Benar:</span> 
                            {{ $result['correct_answer_text'] ?: 'Opsi ' . $result['correct_answer'] }}
                          </p>
                        @endif
                      </div>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
              <a href="{{ route('quiz.history.index') }}" class="btn btn-secondary">
                <i class="fas fa-list me-2"></i> Kembali ke Daftar
              </a>
              <a href="{{ route('learning.material.show', $material->id) }}" class="btn btn-primary">
                <i class="fas fa-book me-2"></i> Lihat Materi
              </a>
              <a href="{{ route('learning.material.quiz.show', $material->id) }}" class="btn btn-warning">
                <i class="fas fa-redo me-2"></i> Coba Lagi
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout> 