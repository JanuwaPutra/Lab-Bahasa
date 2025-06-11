<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12 text-center">
        <h1>Evaluasi Kemampuan Bahasa</h1>
        <p class="lead">
          Pilih jenis tes di bawah ini untuk mengevaluasi kemampuan bahasa Anda.
        </p>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card mb-4">
          <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">Jenis Evaluasi</h2>
          </div>
          <div class="card-body">
            <div class="row">
              @if(!$settings || $settings->show_placement_test)
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-header bg-info text-white">
                    <h3 class="h6 mb-0">Tes Penempatan</h3>
                  </div>
                  <div class="card-body">
                    <p class="small">Tes ini akan menentukan level awal kemampuan bahasa Anda.</p>
                    <div class="d-grid">
                      <a href="{{ route('placement-test') }}" class="btn btn-info btn-sm">Mulai Tes</a>
                    </div>
                  </div>
                </div>
              </div>
              @endif
              
              @if(!$settings || $settings->show_listening_test)
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-header bg-success text-white">
                    <h3 class="h6 mb-0">Tes Mendengarkan</h3>
                  </div>
                  <div class="card-body">
                    <p class="small">Mengevaluasi kemampuan Anda dalam memahami bahasa lisan.</p>
                    <div class="d-grid">
                      <a href="{{ route('listening-test') }}" class="btn btn-success btn-sm">Mulai Tes</a>
                    </div>
                  </div>
                </div>
              </div>
              @endif
              
              @if(!$settings || $settings->show_reading_test)
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-header bg-warning text-white">
                    <h3 class="h6 mb-0">Tes Membaca</h3>
                  </div>
                  <div class="card-body">
                    <p class="small">Mengevaluasi kemampuan Anda dalam memahami teks tertulis.</p>
                    <div class="d-grid">
                      <a href="{{ route('reading-test') }}" class="btn btn-warning btn-sm">Mulai Tes</a>
                    </div>
                  </div>
                </div>
              </div>
              @endif
              
              @if(!$settings || $settings->show_speaking_test)
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-header bg-danger text-white">
                    <h3 class="h6 mb-0">Tes Berbicara</h3>
                  </div>
                  <div class="card-body">
                    <p class="small">Mengevaluasi kemampuan Anda dalam mengekspresikan diri secara lisan.</p>
                    <div class="d-grid">
                      <a href="{{ route('speaking-test') }}" class="btn btn-danger btn-sm">Mulai Tes</a>
                    </div>
                  </div>
                </div>
              </div>
              @endif
              
              @if(!$settings || $settings->show_grammar_test)
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-header bg-primary text-white">
                    <h3 class="h6 mb-0">Tes Tata Bahasa</h3>
                  </div>
                  <div class="card-body">
                    <p class="small">Mengevaluasi pemahaman Anda tentang tata bahasa.</p>
                    <div class="d-grid">
                      <a href="{{ route('grammar-test') }}" class="btn btn-primary btn-sm">Mulai Tes</a>
                    </div>
                  </div>
                </div>
              </div>
              @endif
              
              @if($settings && $settings->teacher)
              <div class="col-12">
                <div class="alert alert-info mb-0">
                  <i class="fas fa-info-circle me-2"></i> Evaluasi ini telah disesuaikan oleh pengajar: <strong>{{ $settings->teacher->name }}</strong>
                  @if($settings->notes)
                  <hr>
                  <p class="mb-0"><strong>Catatan dari pengajar:</strong> {{ $settings->notes }}</p>
                  @endif
                </div>
              </div>
              @endif
            </div>
          </div>
        </div>
        
        @if(isset($assessments) && count($assessments) > 0)
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h2 class="h5 mb-0">Laporan Evaluasi Terakhir</h2>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped table-bordered">
                <thead class="table-primary">
                  <tr>
                    <th>Tanggal</th>
                    <th>Jenis Tes</th>
                    <th>Bahasa</th>
                    <th>Skor</th>
                    <th>Level</th>
                    <th>Hasil</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($assessments->take(5) as $assessment)
                  <tr>
                    <td>{{ $assessment->created_at->format('d M Y') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $assessment->type)) }}</td>
                    <td>{{ strtoupper($assessment->language) }}</td>
                    <td>{{ $assessment->score }} ({{ $assessment->percentage }}%)</td>
                    <td>{{ $assessment->level }}</td>
                    <td>
                      @if($assessment->passed)
                      <span class="badge bg-success">Lulus</span>
                      @else
                      <span class="badge bg-danger">Gagal</span>
                      @endif
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            
            <div class="mt-3">
              <a href="{{ route('progress-report') }}" class="btn btn-primary">Lihat Laporan Lengkap</a>
            </div>
          </div>
        </div>
        @else
        <div class="alert alert-info">
          <p>Anda belum mengambil tes evaluasi apa pun. Silakan pilih salah satu tes di atas untuk memulai evaluasi kemampuan bahasa Anda.</p>
        </div>
        @endif
      </div>
      
      <div class="col-lg-4 col-md-10 mx-auto mt-4 mt-lg-0">
        <div class="card mb-4">
          <div class="card-header bg-info text-white">
            <h3 class="h6 mb-0">Tentang Evaluasi</h3>
          </div>
          <div class="card-body">
            <p>Evaluasi kemampuan bahasa dilakukan melalui berbagai jenis tes yang mencakup keterampilan berbahasa utama:</p>
            <ul>
              <li><strong>Listening</strong> - Kemampuan mendengarkan dan memahami percakapan.</li>
              <li><strong>Reading</strong> - Kemampuan membaca dan memahami teks.</li>
              <li><strong>Speaking</strong> - Kemampuan berbicara dan mengekspresikan diri.</li>
            </ul>
            <p>Hasil evaluasi akan membantu menentukan materi pembelajaran yang sesuai dengan level kemampuan Anda.</p>
          </div>
        </div>
        
        <div class="card">
          <div class="card-header bg-warning text-white">
            <h3 class="h6 mb-0">Tips Evaluasi</h3>
          </div>
          <div class="card-body">
            <ul>
              <li>Pastikan Anda berada di tempat yang tenang saat mengambil tes.</li>
              <li>Untuk tes berbicara, gunakan headphone atau mikrofon untuk hasil yang lebih baik.</li>
              <li>Jawab setiap pertanyaan dengan teliti dan jangan terburu-buru.</li>
              <li>Beberapa tes memiliki batas waktu, jadi perhatikan timer yang tersedia.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout> 