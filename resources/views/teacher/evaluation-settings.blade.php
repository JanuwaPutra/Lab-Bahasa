<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Pengaturan Evaluasi Siswa</h1>
          <a href="{{ route('teacher.students') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Data Siswa
          </a>
        </div>
        <p class="text-muted">Atur jenis evaluasi yang tersedia untuk setiap siswa.</p>
      </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(Auth::user()->hasRole('teacher') && isset($teacherLanguageSettings) && is_countable($teacherLanguageSettings) && count($teacherLanguageSettings) > 0)
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card shadow-sm">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0">Pengaturan Bahasa & Level Anda</h5>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i>
              Anda hanya dapat melihat dan mengelola siswa dengan bahasa dan level yang sesuai dengan pengaturan Anda.
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

    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Daftar Siswa</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="studentsTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama Siswa</th>
                    <th>Email</th>
                    <th>Bahasa</th>
                    <th>Level</th>
                    <th>Jenis Evaluasi Aktif</th>
                    <th>Pengajar</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($students as $index => $student)
                  @php
                    $latestAssessment = \App\Models\Assessment::where('user_id', $student->id)
                      ->whereIn('type', ['pretest', 'post_test', 'placement', 'level_change'])
                      ->orderBy('created_at', 'desc')
                      ->first();
                      
                    $studentLanguage = $latestAssessment ? $latestAssessment->language : '-';
                    $studentLevel = $latestAssessment ? $latestAssessment->level : '-';
                    
                    $languages = ['id' => 'Indonesia', 'en' => 'Inggris', 'ru' => 'Rusia'];
                    $levels = [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
                    
                    $languageName = isset($languages[$studentLanguage]) ? $languages[$studentLanguage] : $studentLanguage;
                    $levelName = isset($levels[$studentLevel]) ? $levels[$studentLevel] : '-';
                  @endphp
                  <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->email }}</td>
                    <td>{{ $languageName }}</td>
                    <td>
                      @if($studentLevel != '-')
                        <span class="badge rounded-pill bg-primary">
                          {{ $studentLevel }} - {{ $levelName }}
                        </span>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      @php
                        $settings = $student->evaluationSettings;
                        $activeEvaluations = [];
                        
                        if(!$settings || $settings->show_placement_test) $activeEvaluations[] = 'Placement';
                        if(!$settings || $settings->show_listening_test) $activeEvaluations[] = 'Listening';
                        if(!$settings || $settings->show_reading_test) $activeEvaluations[] = 'Reading';
                        if(!$settings || $settings->show_speaking_test) $activeEvaluations[] = 'Speaking';
                        if(!$settings || $settings->show_grammar_test) $activeEvaluations[] = 'Grammar';
                      @endphp
                      
                      @if(count($activeEvaluations) > 0)
                        @foreach($activeEvaluations as $eval)
                          <span class="badge bg-success">{{ $eval }}</span>
                        @endforeach
                      @else
                        <span class="badge bg-danger">Tidak Ada</span>
                      @endif
                    </td>
                    <td>
                      @if($settings && $settings->teacher)
                        {{ $settings->teacher->name }}
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      <a href="{{ route('teacher.evaluation.student', $student->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-cog me-1"></i> Konfigurasi
                      </a>
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="8" class="text-center">Tidak ada data siswa.</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <!-- DataTables -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function() {
      try {
        // Check if the table exists
        if ($('#studentsTable').length > 0) {
          // Suppress DataTables warnings by overriding the warning function
          $.fn.dataTable.ext.errMode = 'none';
          
          // Initialize DataTable with more careful options
          var table = $('#studentsTable').DataTable({
            "language": {
              "search": "Cari:",
              "lengthMenu": "Tampilkan _MENU_ data per halaman",
              "zeroRecords": "Tidak ada data yang ditemukan",
              "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
              "infoEmpty": "Tidak ada data yang tersedia",
              "infoFiltered": "(difilter dari _MAX_ total data)",
              "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
              }
            },
            "pageLength": 10,
            "responsive": true,
            "ordering": true,
            "processing": true,
            "columnDefs": [
              { "orderable": false, "targets": [7] } // Disable sorting on action column
            ]
          });
          
          // Suppress specific warnings by handling the error event
          table.on('error.dt', function(e, settings, techNote, message) {
            console.log('DataTables error occurred but suppressed:', message);
            return true; // Suppress error message
          });
          
          console.log('DataTable initialized successfully');
        } else {
          console.log('Table #studentsTable not found');
        }
      } catch (error) {
        console.error('Error initializing DataTable:', error);
      }
    });
  </script>
  @endpush
  
  @push('styles')
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  @endpush
</x-app-layout> 