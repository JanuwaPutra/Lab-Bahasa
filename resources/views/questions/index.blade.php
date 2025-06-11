<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Manajemen Soal</h1>
          <a href="{{ route('questions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i> Tambah Soal Baru
          </a>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Filter Soal</h5>
          </div>
          <div class="card-body">
            <form action="{{ route('questions.index') }}" method="GET" class="row g-3">
              <div class="col-md-6">
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
              <div class="col-md-6 d-flex align-items-end">
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
                <option value="id" {{ request('language') == 'id' || !request('language') ? 'selected' : '' }}>Bahasa Indonesia</option>
                <option value="en" {{ request('language') == 'en' ? 'selected' : '' }}>English</option>
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