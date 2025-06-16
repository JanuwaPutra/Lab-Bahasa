<x-app-layout>
  <div class="container py-4">
    <div class="row">
      <div class="col-md-12">

        
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
        
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Riwayat Kuis {{ $language == 'id' ? 'Bahasa Indonesia' : ($language == 'en' ? 'Bahasa Inggris' : 'Bahasa Rusia') }}</h5>
              
              <div class="btn-group">

                <a href="{{ route('learning.materials') }}" class="btn btn-sm btn-light">
                  <i class="fas fa-book me-1"></i> Materi
                </a>
              </div>
            </div>
          </div>
          <div class="card-body">
            @if($quizAttempts->count() > 0)
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Materi</th>
                      <th>Level</th>
                      <th>Tanggal</th>
                      <th>Percobaan</th>
                      <th>Skor</th>
                      <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($quizAttempts as $attempt)
                      <tr>
                        <td>{{ $attempt->learningMaterial->title }}</td>
                        <td>
                          <span class="badge rounded-pill bg-{{ $attempt->learningMaterial->level == 1 ? 'success' : ($attempt->learningMaterial->level == 2 ? 'warning' : 'danger') }}">
                            Level {{ $attempt->learningMaterial->level }}
                          </span>
                        </td>
                        <td>{{ $attempt->updated_at->format('d M Y, H:i') }}</td>
                        <td>{{ $attempt->quiz_attempts }}x</td>
                        <td>{{ $attempt->quiz_score }}%</td>
                        <td>
                          @if($attempt->quiz_passed)
                            <span class="badge bg-success">Lulus</span>
                          @else
                            <span class="badge bg-danger">Gagal</span>
                          @endif
                        </td>
                        <td>
                          <a href="{{ route('quiz.history.detail', $attempt->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye me-1"></i> Detail
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Anda belum memiliki riwayat kuis.
              </div>
              
              <div class="text-center mt-3">
                <a href="{{ route('learning.materials') }}" class="btn btn-primary">
                  <i class="fas fa-book me-2"></i> Lihat Materi Pembelajaran
                </a>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout> 