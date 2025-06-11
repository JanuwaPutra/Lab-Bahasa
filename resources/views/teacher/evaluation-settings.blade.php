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

    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Daftar Siswa</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="studentsTable">
                <thead class="table-light">
                  <tr>
                    <th>Nama Siswa</th>
                    <th>Email</th>
                    <th>Jenis Evaluasi Aktif</th>
                    <th>Pengajar</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($students as $student)
                  <tr>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->email }}</td>
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
                    <td colspan="5" class="text-center">Tidak ada data siswa.</td>
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
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize DataTable if jQuery and DataTable are available
      if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
        jQuery('#studentsTable').DataTable({
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
          "responsive": true
        });
      } else {
        console.log('DataTable or jQuery not available');
      }
    });
  </script>
  @endpush
  
  @push('styles')
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  @endpush
</x-app-layout> 