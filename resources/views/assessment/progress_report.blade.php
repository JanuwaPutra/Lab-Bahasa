<x-app-layout>
    @push('styles')
    <style>
      .skill-card {
        margin-bottom: 20px;
      }
      .skill-score {
        font-size: 1.5rem;
        font-weight: bold;
      }
      .progress {
        height: 20px;
        margin-bottom: 10px;
      }
      .recommendations-list {
        margin-top: 20px;
      }
      .level-indicator {
        font-size: 0.9rem;
        color: #777;
      }
      .skill-detail {
        margin-top: 15px;
      }
    </style>
    @endpush

    <div class="container mt-4">
      <h1 class="text-center mb-4">Laporan Perkembangan Bahasa</h1>

      <div class="row justify-content-center">
        <div class="col-md-10">
          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
              <h2 class="h4 mb-0">Ringkasan</h2>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <p>
                    <strong>Tanggal Evaluasi:</strong> {{ date('d M Y') }}
                  </p>
                  @if(auth()->user())
                  <p>
                    <strong>Level Keseluruhan:</strong>
                    <span
                      class="badge {{ auth()->user()->getCurrentLevel() <= 2 ? 'bg-secondary' : (auth()->user()->getCurrentLevel() <= 4 ? 'bg-primary' : 'bg-success') }}"
                    >
                      {{ auth()->user()->getCurrentLevel() <= 2 ? 'Basic' : (auth()->user()->getCurrentLevel() <= 4 ? 'Intermediate' : 'Advanced') }}
                    </span>
                  </p>
                  @endif
                </div>
                <div class="col-md-6">
                  @if(isset($assessments) && count($assessments) > 0)
                  <p>
                    <strong>Hasil Tes Penempatan:</strong> {{ $assessments->where('type', 'placement_test')->first() ? $assessments->where('type', 'placement_test')->first()->percentage : 0 }}%
                  </p>
                  <div class="progress">
                    <div
                      class="progress-bar"
                      role="progressbar"
                      style="width: {{ $assessments->where('type', 'placement_test')->first() ? $assessments->where('type', 'placement_test')->first()->percentage : 0 }}%"
                      aria-valuenow="{{ $assessments->where('type', 'placement_test')->first() ? $assessments->where('type', 'placement_test')->first()->percentage : 0 }}"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    ></div>
                  </div>
                  @else
                  <div class="alert alert-warning">
                    Anda belum mengambil tes penempatan.
                    <a href="{{ route('placement-test') }}" class="alert-link"
                      >Ambil Tes Sekarang</a
                    >
                  </div>
                  @endif
                </div>
              </div>
            </div>
          </div>

          <h3 class="text-center mb-3">Kemampuan Bahasa</h3>

          <div class="row">
            @if(isset($assessments) && $assessments->where('type', 'listening_test')->count() > 0)
            <div class="col-md-4">
              <div class="card skill-card">
                <div class="card-header bg-success text-white">
                  <h2 class="h4 mb-0">Listening</h2>
                </div>
                <div class="card-body text-center">
                  <div class="skill-score">
                    {{ $assessments->where('type', 'listening_test')->first()->percentage }}%
                  </div>
                  <div class="progress">
                    <div
                      class="progress-bar bg-success"
                      role="progressbar"
                      style="width: {{ $assessments->where('type', 'listening_test')->first()->percentage }}%"
                      aria-valuenow="{{ $assessments->where('type', 'listening_test')->first()->percentage }}"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    ></div>
                  </div>
                  <div class="level-indicator">
                    Level: {{ $assessments->where('type', 'listening_test')->first()->level }}
                  </div>
                  <div class="skill-detail">
                    <small>{{ $assessments->where('type', 'listening_test')->first()->feedback ?? 'Keterampilan mendengarkan Anda menunjukkan kemampuan yang baik dalam memahami percakapan.' }}</small>
                  </div>
                </div>
              </div>
            </div>
            @else
            <div class="col-md-4">
              <div class="card skill-card">
                <div class="card-header bg-success text-white">
                  <h2 class="h4 mb-0">Listening</h2>
                </div>
                <div class="card-body text-center">
                  <div class="alert alert-light">
                    Belum diambil
                    <div class="mt-2">
                      <a
                        href="{{ route('listening-test') }}"
                        class="btn btn-sm btn-success"
                        >Ambil Tes</a
                      >
                    </div>
                  </div>
                </div>
              </div>
            </div>
            @endif

            @if(isset($assessments) && $assessments->where('type', 'reading_test')->count() > 0)
            <div class="col-md-4">
              <div class="card skill-card">
                <div class="card-header bg-info text-white">
                  <h2 class="h4 mb-0">Reading</h2>
                </div>
                <div class="card-body text-center">
                  <div class="skill-score">
                    {{ $assessments->where('type', 'reading_test')->first()->percentage }}%
                  </div>
                  <div class="progress">
                    <div
                      class="progress-bar bg-info"
                      role="progressbar"
                      style="width: {{ $assessments->where('type', 'reading_test')->first()->percentage }}%"
                      aria-valuenow="{{ $assessments->where('type', 'reading_test')->first()->percentage }}"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    ></div>
                  </div>
                  <div class="level-indicator">
                    Level: {{ $assessments->where('type', 'reading_test')->first()->level }}
                  </div>
                  <div class="skill-detail">
                    <small>{{ $assessments->where('type', 'reading_test')->first()->feedback ?? 'Keterampilan membaca Anda menunjukkan pemahaman yang baik terhadap teks.' }}</small>
                  </div>
                </div>
              </div>
            </div>
            @else
            <div class="col-md-4">
              <div class="card skill-card">
                <div class="card-header bg-info text-white">
                  <h2 class="h4 mb-0">Reading</h2>
                </div>
                <div class="card-body text-center">
                  <div class="alert alert-light">
                    Belum diambil
                    <div class="mt-2">
                      <a
                        href="{{ route('reading-test') }}"
                        class="btn btn-sm btn-info"
                        >Ambil Tes</a
                      >
                    </div>
                  </div>
                </div>
              </div>
            </div>
            @endif

            @if(isset($assessments) && $assessments->where('type', 'speaking_test')->count() > 0)
            <div class="col-md-4">
              <div class="card skill-card">
                <div class="card-header bg-warning text-white">
                  <h2 class="h4 mb-0">Speaking</h2>
                </div>
                <div class="card-body text-center">
                  <div class="skill-score">
                    {{ $assessments->where('type', 'speaking_test')->first()->percentage }}%
                  </div>
                  <div class="progress">
                    <div
                      class="progress-bar bg-warning"
                      role="progressbar"
                      style="width: {{ $assessments->where('type', 'speaking_test')->first()->percentage }}%"
                      aria-valuenow="{{ $assessments->where('type', 'speaking_test')->first()->percentage }}"
                      aria-valuemin="0"
                      aria-valuemax="100"
                    ></div>
                  </div>
                  <div class="level-indicator">
                    Level: {{ $assessments->where('type', 'speaking_test')->first()->level }}
                  </div>
                  <div class="skill-detail">
                    <small>{{ $assessments->where('type', 'speaking_test')->first()->feedback ?? 'Keterampilan berbicara Anda menunjukkan kemampuan yang baik dalam mengekspresikan diri.' }}</small>
                  </div>
                  @if(isset($assessments) && $assessments->where('type', 'speaking_test')->first()->details)
                  <div class="mt-3">
                    <button
                      class="btn btn-sm btn-outline-warning"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#speakingDetails"
                    >
                      Lihat Detail
                    </button>
                    <div class="collapse mt-2" id="speakingDetails">
                      <div class="card card-body">
                        <p>
                          <small>Pengucapan: {{ json_decode($assessments->where('type', 'speaking_test')->first()->details)->pronunciation ?? 0 }}%</small>
                        </p>
                        <p>
                          <small>Kelancaran: {{ json_decode($assessments->where('type', 'speaking_test')->first()->details)->fluency ?? 0 }}%</small>
                        </p>
                        <p>
                          <small>Konten: {{ json_decode($assessments->where('type', 'speaking_test')->first()->details)->content ?? 0 }}%</small>
                        </p>
                      </div>
                    </div>
                  </div>
                  @endif
                </div>
              </div>
            </div>
            @else
            <div class="col-md-4">
              <div class="card skill-card">
                <div class="card-header bg-warning text-white">
                  <h2 class="h4 mb-0">Speaking</h2>
                </div>
                <div class="card-body text-center">
                  <div class="alert alert-light">
                    Belum diambil
                    <div class="mt-2">
                      <a
                        href="{{ route('speaking-test') }}"
                        class="btn btn-sm btn-warning"
                        >Ambil Tes</a
                      >
                    </div>
                  </div>
                </div>
              </div>
            </div>
            @endif
          </div>

          <!-- Assessments history table -->
          @if(isset($assessments) && count($assessments) > 0)
          <h3 class="text-center mt-5 mb-3">Riwayat Tes</h3>
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
                @foreach($assessments as $assessment)
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
          @endif
        </div>
      </div>
    </div>

    @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Any additional JavaScript for this page
      });
    </script>
    @endpush
</x-app-layout> 