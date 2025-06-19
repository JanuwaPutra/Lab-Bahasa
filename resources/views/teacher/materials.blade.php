<x-app-layout>
  <div class="container mt-4">
    @if(Auth::user()->role === 'teacher' && isset($teacherLanguageSettings) && is_countable($teacherLanguageSettings) && count($teacherLanguageSettings) > 0)
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card shadow-sm">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0">Pengaturan Bahasa & Level Anda</h5>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              Anda hanya dapat melihat dan mengelola materi pembelajaran dengan bahasa dan level yang sesuai dengan pengaturan Anda.
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
                    <td>{{ $setting['language'] }}</td>
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
    
    <!-- Debug Information
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
    </div> -->
    
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Manajemen Materi Pembelajaran</h1>
          <a href="{{ route('teacher.materials.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i> Tambah Materi Baru
          </a>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Filter Materi</h5>
          </div>
          <div class="card-body">
            <form action="{{ route('teacher.materials') }}" method="GET" class="row g-3">
              <div class="col-md-4">
                <label for="level" class="form-label">Level</label>
                <select name="level" id="level" class="form-select" onchange="this.form.submit()">
     
                  @if(isset($teacherLanguageSettings) && is_countable($teacherLanguageSettings) && count($teacherLanguageSettings) > 0)
                    @php
                      // Get unique levels from teacher settings
                      $uniqueLevels = [];
                      foreach($teacherLanguageSettings as $setting) {
                          $uniqueLevels[$setting['level']] = $setting['level_name'];
                      }
                    @endphp
                    
                    @foreach($uniqueLevels as $levelValue => $levelName)
                      <option value="{{ $levelValue }}" {{ $level == $levelValue ? 'selected' : '' }}>
                        Level {{ $levelValue }} ({{ $levelName }})
                      </option>
                    @endforeach
                  @else
                    <option value="1" {{ $level == 1 ? 'selected' : '' }}>Level 1 (Beginner)</option>
                    <option value="2" {{ $level == 2 ? 'selected' : '' }}>Level 2 (Intermediate)</option>
                    <option value="3" {{ $level == 3 ? 'selected' : '' }}>Level 3 (Advanced)</option>
                  @endif
                </select>
              </div>
              
              <div class="col-md-4">
                <label for="language" class="form-label">Bahasa</label>
                <select name="language" id="language" class="form-select" onchange="this.form.submit()">
                  @if(isset($teacherLanguageSettings) && is_countable($teacherLanguageSettings) && count($teacherLanguageSettings) > 0)
                    @php
                      // Get unique languages from teacher settings
                      $uniqueLanguages = [];
                      foreach($teacherLanguageSettings as $setting) {
                          $uniqueLanguages[$setting['language_code']] = $setting['language'];
                      }
                    @endphp
                    
                    @foreach($uniqueLanguages as $langCode => $langName)
                      <option value="{{ $langCode }}" {{ $language == $langCode ? 'selected' : '' }}>
                        {{ $langName }}
                      </option>
                    @endforeach
                  @else
                    <option value="id" {{ $language == 'id' ? 'selected' : '' }}>Indonesia</option>
                    <option value="en" {{ $language == 'en' ? 'selected' : '' }}>English</option>
                    <option value="ru" {{ $language == 'ru' ? 'selected' : '' }}>Russian</option>
                  @endif
                </select>
              </div>
              
              <div class="col-md-4">
                <label for="search" class="form-label">Cari</label>
                <div class="input-group">
                  <input type="text" name="search" id="search" class="form-control" placeholder="Cari judul atau deskripsi..." value="{{ request('search') }}">
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Materi Pembelajaran</h5>
            <span class="badge bg-light text-primary">{{ $materials->total() }} Materi</span>
          </div>
          <div class="card-body">
            @if($materials->count() > 0)
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th width="5%">#</th>
                    <th width="25%">Judul</th>
                    <th width="15%">Tipe</th>
                    <th width="10%">Level</th>
                    <th width="10%">Bahasa</th>
                    <th width="10%">Urutan</th>
                    <th width="10%">Status</th>
                    <th width="15%">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($materials as $index => $material)
                  <tr>
                    <td>{{ $materials->firstItem() + $index }}</td>
                    <td>
                      <div class="fw-bold">{{ $material->title }}</div>
                      @if($material->description)
                      <small class="text-muted">{{ Str::limit($material->description, 50) }}</small>
                      @endif
                    </td>
                    <td>
                      @if($material->type == 'text')
                        <span class="badge bg-primary">Teks</span>
                      @elseif($material->type == 'video')
                        <span class="badge bg-danger">Video</span>
                      @elseif($material->type == 'audio')
                        <span class="badge bg-info">Audio</span>
                      @elseif($material->type == 'document')
                        <span class="badge bg-secondary">Dokumen</span>
                      @endif
                    </td>
                    <td>
                      @php
                        $levelNames = [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
                        $levelName = $levelNames[$material->level] ?? '';
                      @endphp
                      <span class="badge rounded-pill bg-{{ $material->level == 1 ? 'success' : ($material->level == 2 ? 'warning' : 'danger') }}">
                        Level {{ $material->level }} ({{ $levelName }})
                      </span>
                    </td>
                    <td>
                      @php
                        $languageNames = [
                          'id' => 'Indonesia',
                          'en' => 'English',
                          'ru' => 'Russian'
                        ];
                        $languageName = $languageNames[$material->language] ?? $material->language;
                        $bgColor = $material->language == 'id' ? 'danger' : ($material->language == 'en' ? 'primary' : 'secondary');
                      @endphp
                      <span class="badge bg-{{ $bgColor }}">{{ $languageName }}</span>
                    </td>
                    <td>{{ $material->order }}</td>
                    <td>
                      @if($material->active)
                        <span class="badge bg-success">Aktif</span>
                      @else
                        <span class="badge bg-danger">Nonaktif</span>
                      @endif
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <a href="{{ route('teacher.materials.edit', $material->id) }}" class="btn btn-sm btn-warning" title="Edit">
                          <i class="fas fa-edit"></i>
                        </a>
                        <a href="{{ route('teacher.materials.quiz.create', $material->id) }}" class="btn btn-sm btn-info" title="Kelola Kuis">
                          <i class="fas fa-question-circle"></i>
                        </a>
                        <form action="{{ route('teacher.materials.destroy', $material->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi ini?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
              {{ $materials->appends(request()->query())->links() }}
            </div>
            @else
            <div class="alert alert-info">
              <p class="mb-0">Belum ada materi pembelajaran. <a href="{{ route('teacher.materials.create') }}">Tambahkan materi baru</a>.</p>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const levelSelect = document.getElementById('level');
    const languageSelect = document.getElementById('language');
    
    // Get teacher language settings from the server
    const teacherLanguageSettings = @json($teacherLanguageSettings ?? []);
    console.log('Teacher language settings:', teacherLanguageSettings);
    
    // Only apply filtering if we have settings and user is a teacher
    if (teacherLanguageSettings.length > 0 && "{{ Auth::user()->role }}" === 'teacher') {
      console.log('Applying dropdown filters for teacher');
      
      // Clear all existing options in level select except the first placeholder
      while (levelSelect.options.length > 1) {
        levelSelect.remove(1);
      }
      
      // Clear all existing options in language select
      while (languageSelect.options.length > 0) {
        languageSelect.remove(0);
      }
      
      // Add "All Levels" option
      const allLevelsOption = document.createElement('option');
      allLevelsOption.value = "";
      allLevelsOption.textContent = "Semua Level";
      if ("{{ $level }}" === "") {
        allLevelsOption.selected = true;
      }
      levelSelect.appendChild(allLevelsOption);
      
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
        if (level === "{{ $level }}") {
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
        if (code === "{{ $language }}") {
          option.selected = true;
        }
        languageSelect.appendChild(option);
      });
    }
  });
</script>
@endpush 