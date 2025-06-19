<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Manajemen Soal</h1>
          <div>
            <a href="{{ route('questions.create') }}" class="btn btn-primary me-2">
              <i class="fas fa-plus-circle me-2"></i> Tambah Soal Baru
            </a>
            <div class="btn-group">
              <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-file-csv me-1"></i> Import/Export
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('questions.import.form') }}"><i class="fas fa-file-upload me-2"></i> Import Soal</a></li>
                <li><a class="dropdown-item" href="{{ route('questions.template.download') }}"><i class="fas fa-download me-2"></i> Download Template CSV</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('questions.export', ['type' => $type, 'language' => $language ?? 'id']) }}"><i class="fas fa-file-export me-2"></i> Export Soal CSV</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if(Auth::user()->hasRole('teacher'))
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card shadow-sm">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0">Pengaturan Bahasa & Level Anda</h5>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              Anda hanya dapat melihat dan mengelola soal dengan bahasa dan level yang sesuai dengan pengaturan Anda.
            </div>
            
            @php
              $teacherLanguages = \App\Models\TeacherLanguage::where('teacher_id', Auth::id())
                ->get()
                ->map(function($setting) {
                    $languages = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
                    $levels = [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
                    
                    return [
                        'language' => $languages[$setting->language] ?? $setting->language,
                        'level' => $setting->level,
                        'level_name' => $levels[$setting->level] ?? 'Unknown'
                    ];
                });
            @endphp
            
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead class="table-light">
                  <tr>
                    <th>Bahasa</th>
                    <th>Level</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($teacherLanguages as $setting)
                  <tr>
                    <td>{{ $setting['language'] }}</td>
                    <td>
                      <span class="badge rounded-pill bg-primary">
                        {{ $setting['level'] }} - {{ $setting['level_name'] }}
                      </span>
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="2" class="text-center">Tidak ada pengaturan bahasa. Hubungi admin untuk mengatur akses bahasa dan level Anda.</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endif

    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Filter Soal</h5>
          </div>
          <div class="card-body">
            <form action="{{ route('questions.index') }}" method="GET" class="row g-3">
              <div class="col-md-3">
                <label for="type" class="form-label">Tipe Tes</label>
                <select name="type" id="type" class="form-select" onchange="this.form.submit()">
                  <option value="pretest" {{ $type == 'pretest' ? 'selected' : '' }}>Pretest</option>
                  <option value="post_test" {{ $type == 'post_test' ? 'selected' : '' }}>Post-test</option>
                  <option value="placement" {{ $type == 'placement' ? 'selected' : '' }}>Placement Test</option>
                  <option value="listening" {{ $type == 'listening' ? 'selected' : '' }}>Listening Test</option>
                  <option value="reading" {{ $type == 'reading' ? 'selected' : '' }}>Reading Test</option>
                  <option value="speaking" {{ $type == 'speaking' ? 'selected' : '' }}>Speaking Test</option>
                  <option value="grammar" {{ $type == 'grammar' ? 'selected' : '' }}>Grammar Test</option>
                </select>
              </div>
              <div class="col-md-3">
                <label for="filter_language" class="form-label">Bahasa</label>
                <select name="language" id="filter_language" class="form-select" onchange="this.form.submit()">
                  @foreach($availableLanguages as $lang)
                  <option value="{{ $lang['code'] }}" {{ $language == $lang['code'] ? 'selected' : '' }}>{{ $lang['name'] }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label for="filter_level" class="form-label">Level</label>
                <select name="level" id="filter_level" class="form-select" onchange="this.form.submit()">
                  <option value="">Semua Level</option>
                  @foreach($availableLevels as $lvl)
                  <option value="{{ $lvl }}" {{ isset($level) && $level == $lvl ? 'selected' : '' }}>Level {{ $lvl }} ({{ $levelNames[$lvl] ?? '' }})</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#testSettingsModal">
                  <i class="fas fa-clock me-1"></i> Pengaturan Waktu Ujian
                </button>
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
            <h5 class="mb-0">Daftar Soal {{ ucfirst(str_replace('_', ' ', $type)) }}</h5>
            <span class="badge bg-light text-primary">{{ $questions->total() }} Soal</span>
          </div>
          <div class="card-body">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            
            @if($questions->count() > 0)
            <!-- Debug: Bulk Delete URL: {{ route('questions.bulk-delete') }} -->
            <form action="{{ url('/questions/bulk-delete') }}" method="POST" id="bulk-delete-form">
              @csrf
              
              <div class="mb-3">
                <button type="button" class="btn btn-danger" id="bulk-delete-btn" disabled>
                  <i class="fas fa-trash me-2"></i> Hapus Soal Terpilih
                </button>
                <button type="button" class="btn btn-secondary" id="select-all-btn">
                  <i class="fas fa-check-square me-2"></i> Pilih Semua
                </button>
                <button type="button" class="btn btn-secondary" id="deselect-all-btn">
                  <i class="fas fa-square me-2"></i> Batalkan Pilihan
                </button>
              </div>
              
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th width="5%">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="select-all-checkbox">
                        </div>
                      </th>
                      <th width="5%">#</th>
                      <th width="40%">Pertanyaan</th>
                      <th width="15%">Tipe</th>
                      <th width="10%">Level</th>
                      <th width="10%">Poin</th>
                      <th width="10%">Status</th>
                      <th width="10%">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($questions as $index => $question)
                    <tr>
                      <td>
                        <div class="form-check">
                          <input class="form-check-input question-checkbox" type="checkbox" name="question_ids[]" value="{{ $question->id }}">
                        </div>
                      </td>
                      <td>{{ $questions->firstItem() + $index }}</td>
                      <td>{{ Str::limit($question->text, 100) }}</td>
                      <td>
                        @if($question->type == 'multiple_choice')
                          <span class="badge bg-primary">Pilihan Ganda</span>
                        @elseif($question->type == 'true_false')
                          <span class="badge bg-success">Benar/Salah</span>
                        @elseif($question->type == 'essay')
                          <span class="badge bg-warning">Esai</span>
                        @elseif($question->type == 'fill_blank')
                          <span class="badge bg-info">Isian</span>
                        @endif
                      </td>
                      <td>{{ $question->level }}</td>
                      <td>{{ $question->points }}</td>
                      <td>
                        @if($question->active)
                          <span class="badge bg-success">Aktif</span>
                        @else
                          <span class="badge bg-danger">Nonaktif</span>
                        @endif
                      </td>
                      <td>
                        <div class="btn-group" role="group">
                          <a href="{{ route('questions.edit', $question->id) }}" class="btn btn-sm btn-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                          </a>
                          <a href="{{ route('questions.show', $question->id) }}" class="btn btn-sm btn-info" title="Lihat">
                            <i class="fas fa-eye"></i>
                          </a>
                          <form action="{{ route('questions.destroy', $question->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus soal ini?')">
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
            </form>
            
            <div class="d-flex justify-content-center mt-4">
              {{ $questions->links() }}
            </div>
            @else
            <div class="alert alert-info">
              <p class="mb-0">Belum ada soal untuk tipe tes ini. <a href="{{ route('questions.create') }}">Tambahkan soal baru</a>.</p>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
  
  @if($type == 'pretest' || $type == 'placement')
  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // For teacher role only - set up language/level filtering
      @if(Auth::user()->hasRole('teacher'))
      const languageSelect = document.getElementById('filter_language');
      const levelSelect = document.getElementById('filter_level');
      
      // Store teacher language settings
      const teacherSettings = @json($teacherLanguages ?? []);
      
      // Filter level options based on selected language
      languageSelect.addEventListener('change', function() {
        // Don't submit form yet - we'll do it after updating level options
        event.preventDefault();
        
        const selectedLanguage = this.value;
        const currentLevel = levelSelect.value;
        
        // Clear current options except the first one
        while (levelSelect.options.length > 1) {
          levelSelect.remove(1);
        }
        
        // Get languages from PHP-rendered settings
        const languageMap = {
          'id': 'Indonesia',
          'en': 'Inggris',
          'ru': 'Rusia'
        };
        
        // Add levels for the selected language
        const filteredSettings = teacherSettings.filter(setting => 
          languageMap[selectedLanguage] === setting.language);
        
        filteredSettings.forEach(setting => {
          const option = document.createElement('option');
          option.value = setting.level;
          option.textContent = `Level ${setting.level} (${setting.level_name})`;
          levelSelect.appendChild(option);
        });
        
        // Submit the form after updating options
        this.form.submit();
      });
      @endif
      
      // Add CSRF meta tag if it doesn't exist
      if (!document.querySelector('meta[name="csrf-token"]')) {
        const meta = document.createElement('meta');
        meta.name = 'csrf-token';
        meta.content = '{{ csrf_token() }}';
        document.head.appendChild(meta);
      }
      
      const bulkDeleteForm = document.getElementById('bulk-delete-form');
      const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
      const selectAllBtn = document.getElementById('select-all-btn');
      const deselectAllBtn = document.getElementById('deselect-all-btn');
      const selectAllCheckbox = document.getElementById('select-all-checkbox');
      const questionCheckboxes = document.querySelectorAll('.question-checkbox');
      
      // Function to update bulk delete button state
      function updateBulkDeleteButton() {
        const checkedBoxes = document.querySelectorAll('.question-checkbox:checked');
        bulkDeleteBtn.disabled = checkedBoxes.length === 0;
        
        // Update select all checkbox
        selectAllCheckbox.checked = checkedBoxes.length === questionCheckboxes.length && questionCheckboxes.length > 0;
      }
      
      // Add event listeners to all checkboxes
      questionCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateBulkDeleteButton);
      });
      
      // Select all checkbox functionality
      selectAllCheckbox.addEventListener('change', function() {
        questionCheckboxes.forEach(function(checkbox) {
          checkbox.checked = selectAllCheckbox.checked;
        });
        updateBulkDeleteButton();
      });
      
      // Select all button
      selectAllBtn.addEventListener('click', function() {
        questionCheckboxes.forEach(function(checkbox) {
          checkbox.checked = true;
        });
        selectAllCheckbox.checked = true;
        updateBulkDeleteButton();
      });
      
      // Deselect all button
      deselectAllBtn.addEventListener('click', function() {
        questionCheckboxes.forEach(function(checkbox) {
          checkbox.checked = false;
        });
        selectAllCheckbox.checked = false;
        updateBulkDeleteButton();
      });
      
      // Bulk delete button
      bulkDeleteBtn.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.question-checkbox:checked');
        if (checkedBoxes.length === 0) {
          alert('Silakan pilih minimal satu soal untuk dihapus.');
          return;
        }
        
        if (confirm(`Apakah Anda yakin ingin menghapus ${checkedBoxes.length} soal yang dipilih?`)) {
          // Create a dynamic form
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = '/questions/bulk-delete';
          form.style.display = 'none';
          
          // Add CSRF token
          const csrfInput = document.createElement('input');
          csrfInput.type = 'hidden';
          csrfInput.name = '_token';
          csrfInput.value = '{{ csrf_token() }}';
          form.appendChild(csrfInput);
          
          // Add selected question IDs
          Array.from(checkedBoxes).forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'question_ids[]';
            input.value = checkbox.value;
            form.appendChild(input);
          });
          
          // Add form to document and submit
          document.body.appendChild(form);
          form.submit();
        }
      });
      
      // Initial update
      updateBulkDeleteButton();
    });
  </script>
  @endpush
  @endif
  
  <!-- Modal Pengaturan Waktu Ujian -->
  <div class="modal fade" id="testSettingsModal" tabindex="-1" aria-labelledby="testSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="{{ route('questions.update.settings') }}">
          @csrf
          <input type="hidden" name="test_type" value="{{ $type }}">
          
          <div class="modal-header">
            <h5 class="modal-title" id="testSettingsModalLabel">Pengaturan Waktu {{ ucfirst(str_replace('_', ' ', $type)) }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i> Pengaturan ini akan menentukan berapa lama waktu yang diberikan kepada siswa untuk menyelesaikan ujian.
            </div>
            
            <div class="mb-3">
              <label for="language" class="form-label">Bahasa</label>
              <select class="form-select" id="language" name="language">
                @foreach($availableLanguages as $lang)
                <option value="{{ $lang['code'] }}" {{ $language == $lang['code'] ? 'selected' : '' }}>{{ $lang['name'] }}</option>
                @endforeach
              </select>
            </div>
            
            <div class="mb-3">
              <label for="time_limit" class="form-label">Batas Waktu (menit)</label>
              <input type="number" class="form-control" id="time_limit" name="time_limit" min="0" max="180" value="{{ $timeLimit ?? 0 }}" required>
              <div class="form-text">Atur batas waktu untuk test ini. Jika 0, tidak ada batas waktu.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-app-layout> 