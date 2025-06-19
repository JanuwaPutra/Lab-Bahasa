<x-app-layout>
    <div class="container">
        @if(Auth::user()->role === 'teacher' && isset($teacherLanguageSettings) && is_countable($teacherLanguageSettings) && count($teacherLanguageSettings) > 0)
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Pengaturan Bahasa & Level Anda</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Anda hanya dapat melihat hasil test siswa dengan bahasa dan level yang sesuai dengan pengaturan Anda.
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
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Hasil Test</h4>
                        <div class="d-flex">
                            <div class="dropdown me-2">
                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="testTypeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="testTypeDropdown">
                                    <li><a class="dropdown-item {{ $type == 'pretest' ? 'active' : '' }}" href="{{ route('teacher.test.results', ['type' => 'pretest']) }}">Pretest</a></li>
                                    <li><a class="dropdown-item {{ $type == 'post_test' ? 'active' : '' }}" href="{{ route('teacher.test.results', ['type' => 'post_test']) }}">Post Test</a></li>
                                    <li><a class="dropdown-item {{ $type == 'placement' ? 'active' : '' }}" href="{{ route('teacher.test.results', ['type' => 'placement']) }}">Placement</a></li>
                                    <li><a class="dropdown-item {{ $type == 'reading' ? 'active' : '' }}" href="{{ route('teacher.test.results', ['type' => 'reading']) }}">Reading</a></li>
                                    <li><a class="dropdown-item {{ $type == 'listening' ? 'active' : '' }}" href="{{ route('teacher.test.results', ['type' => 'listening']) }}">Listening</a></li>
                                    <li><a class="dropdown-item {{ $type == 'speaking' ? 'active' : '' }}" href="{{ route('teacher.test.results', ['type' => 'speaking']) }}">Speaking</a></li>
                                </ul>
                            </div>
                            <form class="d-flex" role="search" method="GET" action="{{ route('teacher.test.results') }}">
                                <input type="hidden" name="type" value="{{ $type }}">
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
                                        <th>Tanggal</th>
                                        <th>Nama Siswa</th>
                                        <th>Bahasa</th>
                                        <th>Skor (Persentase)</th>
                                        <th>Level</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($results as $index => $result)
                                    <tr>
                                        <td>{{ $results->firstItem() + $index }}</td>
                                        <td>{{ $result->created_at->format('d M Y H:i') }}</td>
                                        <td>
                                            <a href="{{ route('teacher.student.detail', $result->user->id) }}">
                                                {{ $result->user->name }}
                                            </a>
                                        </td>
                                        <td>
                                            @php
                                                $languageNames = [
                                                    'id' => 'Bahasa Indonesia',
                                                    'en' => 'English',
                                                    'ru' => 'Russian'
                                                ];
                                                $languageName = $languageNames[$result->language] ?? $result->language;
                                            @endphp
                                            {{ $languageName }}
                                        </td>
                                        <td>{{ $result->score }} dari {{ $result->total_points ?? 100 }} ({{ $result->percentage }}%)</td>
                                        <td>
                                            @php
                                                $levelNames = [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced'];
                                                $levelName = $levelNames[$result->level] ?? '';
                                            @endphp
                                            <span class="badge rounded-pill bg-primary">{{ $result->level }} - {{ $levelName }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('teacher.test.result.detail', $result->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data hasil test</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-4">
                            {{ $results->appends(['type' => $type, 'search' => request('search')])->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 