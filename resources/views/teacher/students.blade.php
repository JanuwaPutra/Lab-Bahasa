<x-app-layout>
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Data Siswa</h4>
                        <div>
                            <form class="d-flex" role="search" method="GET" action="{{ route('teacher.students') }}">
                                <input class="form-control form-control-sm me-2" type="search" name="search" placeholder="Cari siswa..." value="{{ request('search') }}">
                                <button class="btn btn-sm btn-light" type="submit">Cari</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Level (EN)</th>
                                        <th>Level (ID)</th>
                                        <th>Pretest</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($students as $index => $student)
                                    <tr>
                                        <td>{{ $students->firstItem() + $index }}</td>
                                        <td>{{ $student->name }}</td>
                                        <td>{{ $student->email }}</td>
                                        <td>
                                            <span class="badge rounded-pill bg-primary">{{ $student->getCurrentLevel('en') }}</span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-info">{{ $student->getCurrentLevel('id') }}</span>
                                        </td>
                                        <td>
                                            @if($student->hasCompletedPretest())
                                                <span class="badge bg-success">Selesai</span>
                                                <small class="text-muted d-block">{{ $student->pretestDate() }}</small>
                                            @else
                                                <span class="badge bg-danger">Belum</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('teacher.student.detail', $student->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data siswa</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-4">
                            {{ $students->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 