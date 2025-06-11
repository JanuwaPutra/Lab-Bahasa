<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Pengaturan Evaluasi untuk {{ $student->name }}</h1>
          <a href="{{ route('teacher.evaluation.settings') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar
          </a>
        </div>
        <p class="text-muted">Sesuaikan jenis evaluasi yang tersedia untuk siswa ini.</p>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Konfigurasi Evaluasi</h5>
          </div>
          <div class="card-body">
            <form action="{{ route('teacher.evaluation.update', $student->id) }}" method="POST">
              @csrf
              
              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Centang jenis evaluasi yang ingin Anda tampilkan untuk siswa ini. Evaluasi yang tidak dicentang tidak akan muncul di halaman evaluasi siswa.
              </div>
              
              <div class="row mb-4">
                <div class="col-md-12">
                  <div class="card border">
                    <div class="card-header bg-light">
                      <h6 class="mb-0">Jenis Evaluasi yang Tersedia</h6>
                    </div>
                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-6 mb-3">
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="show_placement_test" name="show_placement_test" {{ (!$settings || $settings->show_placement_test) ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_placement_test">
                              <span class="fw-bold">Tes Penempatan</span>
                              <p class="text-muted small mb-0">Menentukan level awal kemampuan bahasa</p>
                            </label>
                          </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="show_listening_test" name="show_listening_test" {{ (!$settings || $settings->show_listening_test) ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_listening_test">
                              <span class="fw-bold">Tes Mendengarkan</span>
                              <p class="text-muted small mb-0">Kemampuan memahami bahasa lisan</p>
                            </label>
                          </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="show_reading_test" name="show_reading_test" {{ (!$settings || $settings->show_reading_test) ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_reading_test">
                              <span class="fw-bold">Tes Membaca</span>
                              <p class="text-muted small mb-0">Kemampuan memahami teks tertulis</p>
                            </label>
                          </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="show_speaking_test" name="show_speaking_test" {{ (!$settings || $settings->show_speaking_test) ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_speaking_test">
                              <span class="fw-bold">Tes Berbicara</span>
                              <p class="text-muted small mb-0">Kemampuan berbicara bahasa asing</p>
                            </label>
                          </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="show_grammar_test" name="show_grammar_test" {{ (!$settings || $settings->show_grammar_test) ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_grammar_test">
                              <span class="fw-bold">Tes Tata Bahasa</span>
                              <p class="text-muted small mb-0">Pemahaman struktur dan tata bahasa</p>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="notes" class="form-label">Catatan untuk Siswa</label>
                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Catatan akan ditampilkan kepada siswa di halaman evaluasi...">{{ $settings->notes ?? '' }}</textarea>
                <div class="form-text">Catatan ini akan ditampilkan di halaman evaluasi siswa.</div>
              </div>
              
              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-2"></i> Simpan Pengaturan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card mb-4">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0">Informasi Siswa</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <p class="mb-1"><strong>Nama:</strong> {{ $student->name }}</p>
              <p class="mb-1"><strong>Email:</strong> {{ $student->email }}</p>
              <p class="mb-1"><strong>Level Bahasa:</strong> 
                @php
                  $currentLevel = $student->getCurrentLevel(session('language', 'id')) ?? 1;
                  $levelNames = [
                    1 => 'Beginner',
                    2 => 'Intermediate',
                    3 => 'Advanced'
                  ];
                  $levelName = $levelNames[$currentLevel] ?? '';
                @endphp
                {{ $currentLevel }} ({{ $levelName }})
              </p>
            </div>
          </div>
        </div>
        
        @if($settings && $settings->teacher && $settings->teacher->id != auth()->id())
        <div class="card mb-4">
          <div class="card-header bg-warning text-white">
            <h5 class="mb-0">Perhatian</h5>
          </div>
          <div class="card-body">
            <p class="mb-0">Pengaturan evaluasi siswa ini saat ini dikelola oleh guru lain: <strong>{{ $settings->teacher->name }}</strong>.</p>
            <p class="mb-0">Jika Anda menyimpan perubahan, Anda akan menjadi pengajar yang mengelola pengaturan evaluasi siswa ini.</p>
          </div>
        </div>
        @endif
        
        <div class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0">Cara Penggunaan</h5>
          </div>
          <div class="card-body">
            <ul class="mb-0">
              <li>Centang evaluasi yang ingin Anda tampilkan kepada siswa</li>
              <li>Evaluasi yang tidak dicentang tidak akan muncul di halaman siswa</li>
              <li>Tambahkan catatan jika ada instruksi khusus</li>
              <li>Klik "Simpan Pengaturan" untuk menerapkan perubahan</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout> 