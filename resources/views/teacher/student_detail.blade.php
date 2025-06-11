<x-app-layout>
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Detail Siswa</h4>
                    <a href="{{ route('teacher.students') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Informasi Siswa</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="avatar-placeholder mb-3">
                                        <span class="display-4">{{ substr($student->name, 0, 1) }}</span>
                                    </div>
                                    <h4>{{ $student->name }}</h4>
                                    <p class="text-muted">{{ $student->email }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">ID Siswa</small>
                                    <p class="mb-0">{{ $student->id }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Bergabung Pada</small>
                                    <p class="mb-0">{{ $student->created_at->format('d M Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8 mb-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Kemajuan Belajar</h5>
                            </div>
                            <div class="card-body">
                                <h6>Level Bahasa</h6>
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>English</span>
                                            <span>Level {{ $student->getCurrentLevel('en') }}</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $student->getCurrentLevel('en') * 10 }}%" aria-valuenow="{{ $student->getCurrentLevel('en') * 10 }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Bahasa Indonesia</span>
                                            <span>Level {{ $student->getCurrentLevel('id') }}</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $student->getCurrentLevel('id') * 10 }}%" aria-valuenow="{{ $student->getCurrentLevel('id') * 10 }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <h6>Status Pretest</h6>
                                <div class="mb-4">
                                    @if($student->hasCompletedPretest())
                                        <div class="alert alert-success mb-0">
                                            <i class="fas fa-check-circle me-2"></i> Siswa telah menyelesaikan pretest pada {{ $student->pretestDate() }}
                                        </div>
                                    @else
                                        <div class="alert alert-warning mb-0">
                                            <i class="fas fa-exclamation-triangle me-2"></i> Siswa belum menyelesaikan pretest
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Riwayat Assessment</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Jenis</th>
                                                <th>Bahasa</th>
                                                <th>Skor (Persentase)</th>
                                                <th>Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($student->assessments()->latest()->take(10)->get() as $assessment)
                                            <tr>
                                                <td>{{ $assessment->created_at->format('d M Y H:i') }}</td>
                                                <td>{{ ucfirst(str_replace('_', ' ', $assessment->type)) }}</td>
                                                <td>{{ $assessment->language === 'en' ? 'English' : 'Bahasa Indonesia' }}</td>
                                                <td>{{ $assessment->score }} dari {{ $assessment->total_points ?? 100 }} ({{ $assessment->percentage }}%)</td>
                                                <td>
                                                    <span class="badge rounded-pill bg-primary">{{ $assessment->level }}</span>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="5" class="text-center">Tidak ada data assessment</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                
                                @if($student->assessments()->count() > 10)
                                <div class="text-center mt-3">
                                    <a href="{{ route('teacher.test.results') }}?student_id={{ $student->id }}" class="btn btn-sm btn-outline-primary">
                                        Lihat Semua Assessment
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .avatar-placeholder {
            width: 100px;
            height: 100px;
            background-color: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
    </style>
</x-app-layout> 