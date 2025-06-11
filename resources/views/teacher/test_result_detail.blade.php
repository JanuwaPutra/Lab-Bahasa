<x-app-layout>
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Detail Hasil Test</h4>
                    <div>
                        <a href="{{ route('teacher.test.results', ['type' => $result->type]) }}" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                        </a>
                        <a href="{{ route('teacher.student.detail', $result->user->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-user me-1"></i> Profil Siswa
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Informasi Test</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">Jenis Test</small>
                                    <p class="mb-0 fw-bold">{{ ucfirst(str_replace('_', ' ', $result->type)) }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Tanggal Pengerjaan</small>
                                    <p class="mb-0">{{ $result->created_at->format('d M Y H:i') }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Bahasa</small>
                                    <p class="mb-0">{{ $result->language === 'en' ? 'English' : 'Bahasa Indonesia' }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Siswa</small>
                                    <p class="mb-0">{{ $result->user->name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Hasil Test</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-4 text-center mb-3">
                                        <div class="card shadow-sm">
                                            <div class="card-body">
                                                <h6 class="text-muted">Skor</h6>
                                                <h3 class="mb-0">
                                                    @if($result->correct_count)
                                                        {{ $result->correct_count }} dari {{ $result->total_questions ?? ($result->total_points ? round($result->total_points) : 100) }} ({{ $result->percentage }}%)
                                                    @else
                                                        {{ $result->score }} dari {{ $result->total_points ?? 100 }} ({{ $result->percentage }}%)
                                                    @endif
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 text-center mb-3">
                                        <div class="card shadow-sm">
                                            <div class="card-body">
                                                <h6 class="text-muted">Level</h6>
                                                <h3 class="mb-0">{{ $result->level }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6>Detail Jawaban</h6>
                                    @php
                                        $detailsData = null;
                                        if (isset($result->details)) {
                                            $detailsData = $result->details;
                                            if (is_string($detailsData)) {
                                                try {
                                                    $detailsData = json_decode($detailsData, true);
                                                } catch (\Exception $e) {
                                                    $detailsData = null;
                                                }
                                            }
                                        }
                                        
                                        // Jika details tidak ada, coba gunakan jawaban
                                        if (empty($detailsData) && isset($result->answers)) {
                                            $answersData = $result->answers;
                                            if (is_string($answersData)) {
                                                try {
                                                    $answersData = json_decode($answersData, true);
                                                } catch (\Exception $e) {
                                                    $answersData = null;
                                                }
                                            }
                                            
                                            // Jika ada jawaban tetapi tidak ada detail
                                            if (!empty($answersData) && is_array($answersData)) {
                                                $detailsData = [];
                                                foreach ($answersData as $questionId => $answer) {
                                                    $detailsData[] = [
                                                        'question' => 'Pertanyaan #' . $questionId,
                                                        'user_answer' => $answer,
                                                        'correct_answer' => '(tidak tersedia)',
                                                        'is_correct' => null
                                                    ];
                                                }
                                            }
                                        }
                                    @endphp
                                    
                                    @if(!empty($detailsData) && is_array($detailsData) && count($detailsData) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Pertanyaan</th>
                                                        <th>Jawaban Siswa</th>
                                                        <th>Jawaban Benar</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($detailsData as $index => $detail)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ $detail['question'] ?? '-' }}</td>
                                                        <td>
                                                            @php
                                                                $userAnswer = $detail['user_answer'] ?? '-';
                                                                
                                                                // Konversi jawaban angka menjadi huruf untuk pilihan ganda
                                                                if (isset($detail['type']) && ($detail['type'] == 'multiple_choice') && is_numeric($userAnswer)) {
                                                                    $options = ['A', 'B', 'C', 'D', 'E'];
                                                                    $userAnswer = $options[intval($userAnswer)] ?? $userAnswer;
                                                                }
                                                            @endphp
                                                            {{ $userAnswer }}
                                                        </td>
                                                        <td>
                                                            @php
                                                                $correctAnswer = $detail['correct_answer'] ?? '-';
                                                                
                                                                // Konversi jawaban angka menjadi huruf untuk pilihan ganda
                                                                if (isset($detail['type']) && ($detail['type'] == 'multiple_choice') && is_numeric($correctAnswer)) {
                                                                    $options = ['A', 'B', 'C', 'D', 'E'];
                                                                    $correctAnswer = $options[intval($correctAnswer)] ?? $correctAnswer;
                                                                }
                                                            @endphp
                                                            {{ $correctAnswer }}
                                                        </td>
                                                        <td>
                                                            @if(isset($detail['is_correct']) && $detail['is_correct'])
                                                                <span class="badge bg-success">Benar</span>
                                                            @else
                                                                <span class="badge bg-danger">Salah</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            @if($result->answers)
                                                @php
                                                    $answersData = $result->answers;
                                                    if (is_string($answersData)) {
                                                        $answersData = json_decode($answersData, true);
                                                    }
                                                @endphp
                                                @if(is_array($answersData) && count($answersData) > 0)
                                                    <i class="fas fa-info-circle me-2"></i> Jawaban tersimpan tetapi detail tidak tersedia untuk ditampilkan.
                                                @else
                                                    <i class="fas fa-info-circle me-2"></i> Detail jawaban tidak tersedia
                                                @endif
                                            @else
                                                <i class="fas fa-info-circle me-2"></i> Detail jawaban tidak tersedia
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                
                                @if($result->feedback)
                                <div class="mb-4">
                                    <h6>Feedback</h6>
                                    <div class="card">
                                        <div class="card-body">
                                            {{ $result->feedback }}
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 