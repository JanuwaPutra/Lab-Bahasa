<x-app-layout>
    <div class="container">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Pengaturan Guru & Bahasa
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Pada halaman ini, Anda dapat mengatur level bahasa yang diajarkan oleh setiap guru.
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Tambah/Edit Pengaturan</h6>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.teacher-language.update') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="teacher_id" class="form-label">Guru</label>
                                        <select class="form-select @error('teacher_id') is-invalid @enderror" id="teacher_id" name="teacher_id" required>
                                            <option value="">Pilih Guru</option>
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('teacher_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="language" class="form-label">Bahasa</label>
                                        <select class="form-select @error('language') is-invalid @enderror" id="language" name="language" required>
                                            <option value="">Pilih Bahasa</option>
                                            @foreach($languages as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        @error('language')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="level" class="form-label">Level</label>
                                        <select class="form-select @error('level') is-invalid @enderror" id="level" name="level" required>
                                            <option value="">Pilih Level</option>
                                            @foreach($levels as $code => $name)
                                                <option value="{{ $code }}">{{ $code }} - {{ $name }}</option>
                                            @endforeach
                                        </select>
                                        @error('level')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Simpan Pengaturan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Pengaturan Saat Ini</h6>
                            </div>
                            <div class="card-body">
                                @if($teacherLanguages->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Guru</th>
                                                    <th>Bahasa</th>
                                                    <th>Level</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($teacherLanguages as $setting)
                                                    <tr class="teacher-row" data-teacher-id="{{ $setting->teacher_id }}" data-language="{{ $setting->language }}" data-level="{{ $setting->level }}">
                                                        <td>{{ $setting->teacher->name }}</td>
                                                        <td>{{ $languages[$setting->language] ?? $setting->language }}</td>
                                                        <td>
                                                            <span class="badge rounded-pill bg-primary">
                                                                {{ $setting->level }} - {{ $levels[$setting->level] ?? 'Unknown' }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-danger delete-setting" data-id="{{ $setting->id }}" data-teacher="{{ $setting->teacher->name }}" data-language="{{ $languages[$setting->language] ?? $setting->language }}">
                                                                <i class="fas fa-trash"></i> Hapus
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Belum ada pengaturan bahasa untuk guru.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const teacherSelect = document.getElementById('teacher_id');
            const languageSelect = document.getElementById('language');
            const levelSelect = document.getElementById('level');
            
            // When teacher is selected, highlight their rows
            teacherSelect.addEventListener('change', function() {
                const teacherId = this.value;
                
                // Reset all rows
                document.querySelectorAll('.teacher-row').forEach(row => {
                    row.classList.remove('table-primary');
                });
                
                // Highlight rows for selected teacher
                if (teacherId) {
                    document.querySelectorAll(`.teacher-row[data-teacher-id="${teacherId}"]`).forEach(row => {
                        row.classList.add('table-primary');
                    });
                }
            });
            
            // When both teacher and language are selected, pre-fill the level if it exists
            languageSelect.addEventListener('change', function() {
                const teacherId = teacherSelect.value;
                const language = this.value;
                
                if (teacherId && language) {
                    const row = document.querySelector(`.teacher-row[data-teacher-id="${teacherId}"][data-language="${language}"]`);
                    if (row) {
                        const level = row.getAttribute('data-level');
                        levelSelect.value = level;
                    } else {
                        levelSelect.value = '';
                    }
                }
            });
        });
    </script>
    @endpush
    
    <!-- Delete Form (Hidden) -->
    <form id="delete-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus pengaturan bahasa ini?</p>
                    <p>Guru: <strong id="delete-teacher-name"></strong></p>
                    <p>Bahasa: <strong id="delete-language-name"></strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-btn">Hapus</button>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Delete setting handlers
            const deleteButtons = document.querySelectorAll('.delete-setting');
            const deleteForm = document.getElementById('delete-form');
            const confirmDeleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            let deleteId = null;
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    deleteId = this.getAttribute('data-id');
                    const teacherName = this.getAttribute('data-teacher');
                    const languageName = this.getAttribute('data-language');
                    
                    // Set modal content
                    document.getElementById('delete-teacher-name').textContent = teacherName;
                    document.getElementById('delete-language-name').textContent = languageName;
                    
                    // Show modal
                    confirmDeleteModal.show();
                });
            });
            
            // Handle confirm delete
            confirmDeleteBtn.addEventListener('click', function() {
                if (deleteId) {
                    deleteForm.action = "{{ route('admin.teacher-language.settings') }}/" + deleteId;
                    deleteForm.submit();
                }
                confirmDeleteModal.hide();
            });
        });
    </script>
    @endpush
</x-app-layout> 