<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Import Soal</h1>
          <a href="{{ route('questions.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Soal
          </a>
        </div>
      </div>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Unggah File Excel</h5>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle me-2"></i> 
              <strong>Informasi:</strong> Saat ini sistem mendukung import soal pilihan ganda dan benar/salah saja.
              <div class="mt-2">
                <a href="{{ route('questions.template.download') }}" class="btn btn-sm btn-primary">
                  <i class="fas fa-download me-1"></i> Download Template CSV
                </a>
              </div>
            </div>

            <form action="{{ route('questions.import') }}" method="POST" enctype="multipart/form-data">
              @csrf
              
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="assessment_type" class="form-label">Tipe Tes</label>
                  <select name="assessment_type" id="assessment_type" class="form-select" required>
                    <option value="">Pilih Tipe Tes</option>
                    <option value="pretest">Pretest</option>
                    <option value="post_test">Post-test</option>
                    <option value="placement">Placement Test</option>
                    <option value="listening">Listening Test</option>
                    <option value="reading">Reading Test</option>
                    <option value="speaking">Speaking Test</option>
                    <option value="grammar">Grammar Test</option>
                  </select>
                  @error('assessment_type')
                    <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
                
                <div class="col-md-6">
                  <label for="language" class="form-label">Bahasa</label>
                  <select name="language" id="language" class="form-select" required>
                    <option value="id">Indonesia</option>
                    <option value="en">English</option>
                  </select>
                  @error('language')
                    <div class="text-danger">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              
              <div class="mb-3">
                <label for="file" class="form-label">File CSV (.csv)</label>
                <input type="file" name="file" id="file" class="form-control" required accept=".csv">
                @error('file')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>
              
              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-file-import me-2"></i> Import Soal
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    @if(session('import_errors'))
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Error Import</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>Baris</th>
                    <th>Error</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach(session('import_errors') as $error)
                  <tr>
                    <td>{{ $error['row'] }}</td>
                    <td>{{ $error['message'] }}</td>
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
  </div>
</x-app-layout> 