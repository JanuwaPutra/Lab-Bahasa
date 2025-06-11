<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Edit Materi Pembelajaran</h1>
          <a href="{{ route('teacher.materials') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Materi
          </a>
        </div>
      </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit Materi: {{ $material->title }}</h5>
          </div>
          <div class="card-body">
            <form action="{{ route('teacher.materials.update', $material->id) }}" method="POST">
              @csrf
              @method('PUT')
              
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="title" class="form-label">Judul Materi <span class="text-danger">*</span></label>
                  <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $material->title) }}" required>
                </div>
                
                <div class="col-md-6">
                  <label for="material_type" class="form-label">Tipe Materi <span class="text-danger">*</span></label>
                  <select name="material_type" id="material_type" class="form-select" required>
                    <option value="">Pilih Tipe Materi</option>
                    <option value="text" {{ old('material_type', $material->type) == 'text' ? 'selected' : '' }}>Teks</option>
                    <option value="video" {{ old('material_type', $material->type) == 'video' ? 'selected' : '' }}>Video</option>
                    <option value="audio" {{ old('material_type', $material->type) == 'audio' ? 'selected' : '' }}>Audio</option>
                    <option value="document" {{ old('material_type', $material->type) == 'document' ? 'selected' : '' }}>Dokumen</option>
                  </select>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea name="description" id="description" class="form-control" rows="2">{{ old('description', $material->description) }}</textarea>
                <small class="text-muted">Berikan deskripsi singkat tentang materi ini.</small>
              </div>
              
              <div class="mb-3 media-url-container" style="display: none;">
                <label for="media_url" class="form-label">URL Media</label>
                <input type="url" name="media_url" id="media_url" class="form-control" value="{{ old('media_url', $material->url) }}">
                <small class="text-muted">Masukkan URL untuk video (YouTube), audio, atau dokumen.</small>
              </div>
              
              <div class="mb-3 duration-container" style="display: none;">
                <label for="duration" class="form-label">Durasi (menit)</label>
                <input type="number" name="duration" id="duration" class="form-control" min="1" value="{{ old('duration', $material->metadata['duration'] ?? 10) }}">
                <small class="text-muted">Perkiraan durasi dalam menit untuk menyelesaikan materi ini.</small>
              </div>
              
              <div class="mb-3">
                <label for="content" class="form-label">Konten Materi <span class="text-danger">*</span></label>
                <textarea name="content" id="content" class="form-control" rows="10" required>{{ old('content', $material->content) }}</textarea>
                <small class="text-muted">Isi materi pembelajaran. Untuk materi teks, isi dengan konten lengkap. Untuk materi video/audio/dokumen, isi dengan deskripsi dan petunjuk.</small>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-4">
                  <label for="level" class="form-label">Level <span class="text-danger">*</span></label>
                  <select name="level" id="level" class="form-select" required>
                    <option value="">Pilih Level</option>
                    <option value="1" {{ old('level', $material->level) == 1 ? 'selected' : '' }}>Level 1 (Beginner)</option>
                    <option value="2" {{ old('level', $material->level) == 2 ? 'selected' : '' }}>Level 2 (Intermediate)</option>
                    <option value="3" {{ old('level', $material->level) == 3 ? 'selected' : '' }}>Level 3 (Advanced)</option>
                  </select>
                </div>
                
                <div class="col-md-4">
                  <label for="language" class="form-label">Bahasa <span class="text-danger">*</span></label>
                  <select name="language" id="language" class="form-select" required>
                    <option value="id" {{ old('language', $material->language) == 'id' ? 'selected' : '' }}>Indonesia</option>
                    <option value="en" {{ old('language', $material->language) == 'en' ? 'selected' : '' }}>English</option>
                  </select>
                </div>
                
                <div class="col-md-4">
                  <label for="order" class="form-label">Urutan</label>
                  <input type="number" name="order" id="order" class="form-control" min="0" value="{{ old('order', $material->order) }}">
                  <small class="text-muted">Untuk mengurutkan materi (0 = pertama)</small>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="tags" class="form-label">Tag</label>
                <input type="text" name="tags" id="tags" class="form-control" value="{{ old('tags', is_array($material->metadata['tags'] ?? '') ? implode(', ', $material->metadata['tags']) : ($material->metadata['tags'] ?? '')) }}">
                <small class="text-muted">Pisahkan tag dengan koma, misal: grammar, vocabulary, speaking</small>
              </div>
              
              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="active" id="active" value="1" {{ old('active', $material->active) ? 'checked' : '' }}>
                <label class="form-check-label" for="active">Aktif</label>
                <small class="text-muted d-block">Materi yang tidak aktif tidak akan ditampilkan kepada siswa.</small>
              </div>
              
              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="{{ route('teacher.materials.quiz.create', $material->id) }}" class="btn btn-info me-2">
                  <i class="fas fa-question-circle me-2"></i> Kelola Kuis
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-2"></i> Perbarui Materi
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const materialTypeSelect = document.getElementById('material_type');
      const mediaUrlContainer = document.querySelector('.media-url-container');
      const durationContainer = document.querySelector('.duration-container');
      
      // Function to toggle fields based on material type
      function toggleFields() {
        const selectedType = materialTypeSelect.value;
        
        // Show/hide media URL field
        if (selectedType === 'video' || selectedType === 'audio' || selectedType === 'document') {
          mediaUrlContainer.style.display = 'block';
        } else {
          mediaUrlContainer.style.display = 'none';
        }
        
        // Show/hide duration field
        if (selectedType === 'video' || selectedType === 'audio') {
          durationContainer.style.display = 'block';
        } else {
          durationContainer.style.display = 'none';
        }
      }
      
      // Add event listener
      materialTypeSelect.addEventListener('change', toggleFields);
      
      // Initialize fields on page load
      toggleFields();
    });
  </script>
  @endpush
</x-app-layout> 