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
    
    <!-- Debug Info -->
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card border-danger">
          <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Debug Information</h5>
          </div>
          <div class="card-body">
            <h6>Teacher Language Settings (Count: {{ count($teacherLanguageSettings ?? []) }}):</h6>
            <pre>{{ json_encode($teacherLanguageSettings ?? [], JSON_PRETTY_PRINT) }}</pre>
            
            <h6>Auth User:</h6>
            <pre>{{ Auth::check() ? json_encode(Auth::user()->only(['id', 'name', 'email', 'role']), JSON_PRETTY_PRINT) : 'Not logged in' }}</pre>
            
            <h6>Direct DB Check:</h6>
            @php
            $directDbCheck = DB::table('teacher_languages')->where('teacher_id', Auth::id())->get();
            @endphp
            <p>Count: {{ $directDbCheck->count() }}</p>
            <ul>
            @foreach($directDbCheck as $record)
                <li>Teacher ID: {{ $record->teacher_id }}, Language: {{ $record->language }}, Level: {{ $record->level }}</li>
            @endforeach
            </ul>
          </div>
        </div>
      </div>
    </div>

    @if(isset($teacherLanguageSettings) && is_countable($teacherLanguageSettings) && count($teacherLanguageSettings) > 0)
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card shadow-sm">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0">Pengaturan Bahasa & Level Anda</h5>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              Anda hanya dapat mengedit materi pembelajaran dengan bahasa dan level yang sesuai dengan pengaturan Anda.
            </div>
            
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead class="table-light">
                  <tr>
                    <th>Bahasa</th>
                    <th>Level</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($teacherLanguageSettings as $setting)
                  <tr>
                    <td>{{ $setting['language'] }} ({{ $setting['language_code'] }})</td>
                    <td>
                      <span class="badge rounded-pill bg-primary">
                        {{ $setting['level'] }} - {{ $setting['level_name'] }}
                      </span>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endif

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
                    @if(count($teacherLanguageSettings ?? []) > 0)
                      <!-- Use only teacher's assigned language levels -->
                      @php
                      // Get unique levels from teacher settings
                      $uniqueLevels = [];
                      foreach($teacherLanguageSettings as $setting) {
                          $uniqueLevels[$setting['level']] = $setting['level_name'];
                      }
                      @endphp
                      
                      @foreach($uniqueLevels as $level => $levelName)
                        <option value="{{ $level }}" {{ old('level', $material->level) == $level ? 'selected' : '' }}>
                          Level {{ $level }} ({{ $levelName }})
                        </option>
                      @endforeach
                    @else
                      <!-- Fallback if no settings found -->
                      @php
                      $levels = [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
                      @endphp
                      @foreach($levels as $level => $levelName)
                        <option value="{{ $level }}" {{ old('level', $material->level) == $level ? 'selected' : '' }}>
                          Level {{ $level }} ({{ $levelName }})
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>
                
                <div class="col-md-4">
                  <label for="language" class="form-label">Bahasa <span class="text-danger">*</span></label>
                  <select name="language" id="language" class="form-select" required>
                    <option value="">Pilih Bahasa</option>
                    @if(count($teacherLanguageSettings ?? []) > 0)
                      <!-- Use only teacher's assigned languages -->
                      @php
                      // Get unique languages from teacher settings
                      $uniqueLanguages = [];
                      foreach($teacherLanguageSettings as $setting) {
                          $uniqueLanguages[$setting['language_code']] = $setting['language'];
                      }
                      @endphp
                      
                      @foreach($uniqueLanguages as $code => $name)
                        <option value="{{ $code }}" {{ old('language', $material->language) == $code ? 'selected' : '' }}>
                          {{ $name }}
                        </option>
                      @endforeach
                    @else
                      <!-- Fallback if no settings found -->
                      @php
                      $languages = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
                      @endphp
                      @foreach($languages as $code => $name)
                        <option value="{{ $code }}" {{ old('language', $material->language) == $code ? 'selected' : '' }}>
                          {{ $name }}
                        </option>
                      @endforeach
                    @endif
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
      const levelSelect = document.getElementById('level');
      const languageSelect = document.getElementById('language');
      
      // Debug output
      console.log('Level select options:', levelSelect.options.length);
      for (let i = 0; i < levelSelect.options.length; i++) {
        console.log(`Level option ${i}:`, levelSelect.options[i].value, levelSelect.options[i].text);
      }
      
      console.log('Language select options:', languageSelect.options.length);
      for (let i = 0; i < languageSelect.options.length; i++) {
        console.log(`Language option ${i}:`, languageSelect.options[i].value, languageSelect.options[i].text);
      }
      
      // Get the teacher's language settings from the server
      const teacherLanguageSettings = @json($teacherLanguageSettings ?? []);
      console.log('Teacher language settings from server:', teacherLanguageSettings);
      
      // Only apply filtering if we have settings
      if (teacherLanguageSettings.length > 0) {
        // Clear all existing options in level select except the first placeholder
        while (levelSelect.options.length > 1) {
          levelSelect.remove(1);
        }
        
        // Clear all existing options in language select except the first placeholder
        while (languageSelect.options.length > 1) {
          languageSelect.remove(1);
        }
        
        // Get unique levels from teacher settings
        const uniqueLevels = {};
        teacherLanguageSettings.forEach(setting => {
          uniqueLevels[setting.level] = setting.level_name;
        });
        
        // Add filtered level options
        Object.keys(uniqueLevels).forEach(level => {
          const option = document.createElement('option');
          option.value = level;
          option.textContent = `Level ${level} (${uniqueLevels[level]})`;
          // Select the current material's level
          if (level == {{ $material->level }}) {
            option.selected = true;
          }
          levelSelect.appendChild(option);
        });
        
        // Get unique languages from teacher settings
        const uniqueLanguages = {};
        teacherLanguageSettings.forEach(setting => {
          uniqueLanguages[setting.language_code] = setting.language;
        });
        
        // Add filtered language options
        Object.keys(uniqueLanguages).forEach(code => {
          const option = document.createElement('option');
          option.value = code;
          option.textContent = uniqueLanguages[code];
          // Select the current material's language
          if (code === "{{ $material->language }}") {
            option.selected = true;
          }
          languageSelect.appendChild(option);
        });
      }
      
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
      
      // Debug output after filtering
      console.log('Level select options after filtering:', levelSelect.options.length);
      for (let i = 0; i < levelSelect.options.length; i++) {
        console.log(`Level option ${i} after filtering:`, levelSelect.options[i].value, levelSelect.options[i].text);
      }
      
      console.log('Language select options after filtering:', languageSelect.options.length);
      for (let i = 0; i < languageSelect.options.length; i++) {
        console.log(`Language option ${i} after filtering:`, languageSelect.options[i].value, languageSelect.options[i].text);
      }
    });
  </script>
  @endpush
</x-app-layout> 