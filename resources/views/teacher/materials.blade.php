<x-app-layout>
  <div class="container mt-4">
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
                  <option value="">Semua Level</option>
                  <option value="1" {{ $level == 1 ? 'selected' : '' }}>Level 1 (Beginner)</option>
                  <option value="2" {{ $level == 2 ? 'selected' : '' }}>Level 2 (Intermediate)</option>
                  <option value="3" {{ $level == 3 ? 'selected' : '' }}>Level 3 (Advanced)</option>
                </select>
              </div>
              
              <div class="col-md-4">
                <label for="language" class="form-label">Bahasa</label>
                <select name="language" id="language" class="form-select" onchange="this.form.submit()">
                  <option value="id" {{ $language == 'id' ? 'selected' : '' }}>Indonesia</option>
                  <option value="en" {{ $language == 'en' ? 'selected' : '' }}>English</option>
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
                      <span class="badge rounded-pill bg-{{ $material->level == 1 ? 'success' : ($material->level == 2 ? 'warning' : 'danger') }}">
                        Level {{ $material->level }}
                      </span>
                    </td>
                    <td>
                      @if($material->language == 'id')
                        <span class="badge bg-danger">Indonesia</span>
                      @elseif($material->language == 'en')
                        <span class="badge bg-primary">English</span>
                      @endif
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