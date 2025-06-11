<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Detail Soal</h1>
          <div>
            <a href="{{ route('questions.edit', $question->id) }}" class="btn btn-warning me-2">
              <i class="fas fa-edit me-2"></i> Edit
            </a>
            <a href="{{ route('questions.index', ['type' => $question->assessment_type]) }}" class="btn btn-secondary">
              <i class="fas fa-arrow-left me-2"></i> Kembali
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Informasi Soal</h5>
          </div>
          <div class="card-body">
            <div class="row mb-4">
              <div class="col-md-6">
                <h6 class="text-muted">Tipe Tes</h6>
                <p class="fs-5">{{ ucfirst(str_replace('_', ' ', $question->assessment_type)) }}</p>
              </div>
              <div class="col-md-6">
                <h6 class="text-muted">Tipe Pertanyaan</h6>
                <p class="fs-5">
                  @if($question->type == 'multiple_choice')
                    <span class="badge bg-primary">Pilihan Ganda</span>
                  @elseif($question->type == 'true_false')
                    <span class="badge bg-success">Benar/Salah</span>
                  @elseif($question->type == 'essay')
                    <span class="badge bg-warning">Esai</span>
                  @elseif($question->type == 'fill_blank')
                    <span class="badge bg-info">Isian</span>
                  @endif
                </p>
              </div>
            </div>
            
            <div class="mb-4">
              <h6 class="text-muted">Teks Pertanyaan</h6>
              <div class="p-3 bg-light rounded">
                <p class="mb-0">{{ $question->text }}</p>
              </div>
            </div>
            
            <div class="row mb-4">
              <div class="col-md-4">
                <h6 class="text-muted">Level</h6>
                <p class="fs-5">{{ $question->level }}</p>
              </div>
              <div class="col-md-4">
                <h6 class="text-muted">Poin</h6>
                <p class="fs-5">{{ $question->points }}</p>
              </div>
              <div class="col-md-4">
                <h6 class="text-muted">Status</h6>
                <p class="fs-5">
                  @if($question->active)
                    <span class="badge bg-success">Aktif</span>
                  @else
                    <span class="badge bg-danger">Nonaktif</span>
                  @endif
                </p>
              </div>
            </div>
            
            @if($question->type == 'multiple_choice')
            <div class="mb-4">
              <h6 class="text-muted">Pilihan Jawaban</h6>
              <div class="list-group">
                @foreach($question->options as $index => $option)
                <div class="list-group-item {{ $index == $question->correct_answer ? 'list-group-item-success' : '' }}">
                  <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">{{ chr(65 + $index) }}. {{ $option }}</h6>
                    @if($index == $question->correct_answer)
                    <span class="badge bg-success">Jawaban Benar</span>
                    @endif
                  </div>
                </div>
                @endforeach
              </div>
            </div>
            @endif
            
            @if($question->type == 'true_false')
            <div class="mb-4">
              <h6 class="text-muted">Jawaban Benar</h6>
              <p class="fs-5">
                @if($question->correct_answer == 'true')
                <span class="badge bg-success">Benar</span>
                @else
                <span class="badge bg-danger">Salah</span>
                @endif
              </p>
            </div>
            @endif
            
            @if($question->type == 'essay')
            <div class="mb-4">
              <h6 class="text-muted">Jumlah Kata Minimal</h6>
              <p class="fs-5">{{ $question->min_words }} kata</p>
            </div>
            @endif
            
            @if($question->type == 'fill_blank')
            <div class="mb-4">
              <h6 class="text-muted">Jawaban Benar</h6>
              <p class="fs-5">{{ $question->correct_answer }}</p>
            </div>
            @endif
            
            <div class="row">
              <div class="col-md-6">
                <h6 class="text-muted">Bahasa</h6>
                <p class="fs-5">
                  @if($question->language == 'id')
                    Indonesia
                  @elseif($question->language == 'en')
                    English
                  @else
                    {{ $question->language }}
                  @endif
                </p>
              </div>
              <div class="col-md-6">
                <h6 class="text-muted">Terakhir Diperbarui</h6>
                <p class="fs-5">{{ $question->updated_at->format('d M Y H:i') }}</p>
              </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
              <form action="{{ route('questions.destroy', $question->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus soal ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                  <i class="fas fa-trash me-2"></i> Hapus Soal
                </button>
              </form>
              
              <a href="{{ route('questions.edit', $question->id) }}" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i> Edit Soal
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout> 