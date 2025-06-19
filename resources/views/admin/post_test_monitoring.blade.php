<x-app-layout>
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-desktop me-2"></i> Monitoring Post-Test</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Halaman ini menampilkan daftar siswa yang sedang mengerjakan post-test secara real-time.
                        </div>
                        
                        <!-- Filter controls -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-3">Filter</h6>
                                        <form id="filter-form" class="row g-3">
                                            <div class="col-md-6">
                                                <label for="language-filter" class="form-label">Bahasa</label>
                                                <select id="language-filter" class="form-select">
                                                    <option value="">Semua Bahasa</option>
                                                    @foreach($languages as $code => $name)
                                                        <option value="{{ $code }}" {{ $selectedLanguage == $code ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="level-filter" class="form-label">Level</label>
                                                <select id="level-filter" class="form-select">
                                                    <option value="">Semua Level</option>
                                                    @foreach($levels as $code => $name)
                                                        <option value="{{ $code }}" {{ $selectedLevel == $code ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-filter me-2"></i> Terapkan Filter
                                                </button>
                                                <button type="button" id="reset-filter" class="btn btn-outline-secondary">
                                                    <i class="fas fa-redo me-2"></i> Reset
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body p-3">
                                        <h6 class="card-title mb-3">Status</h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="mb-0">Terakhir diperbarui: <span id="last-updated">-</span></p>
                                                <p class="mb-0">Total siswa aktif: <span id="active-count" class="badge bg-success">0</span></p>
                                            </div>
                                            <div>
                                                <button id="refresh-btn" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-sync-alt me-2"></i> Refresh
                                                </button>
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox" id="auto-refresh" checked>
                                                    <label class="form-check-label" for="auto-refresh">Auto-refresh (3s)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Active post-tests table -->
                        <div id="post-tests-container">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Siswa</th>
                                            <th>Bahasa</th>
                                            <th>Level</th>
                                            <th>Waktu Mulai</th>
                                            <th>Batas Waktu</th>
                                            <th>Progress</th>
                                            <th>Waktu Tersisa</th>
                                        </tr>
                                    </thead>
                                    <tbody id="post-tests-table-body">
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2 mb-0">Memuat data...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- No active tests message -->
                        <div id="no-tests-message" class="alert alert-warning d-none">
                            <i class="fas fa-exclamation-triangle me-2"></i> Tidak ada siswa yang sedang mengerjakan post-test saat ini.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const tableBody = document.getElementById('post-tests-table-body');
            const noTestsMessage = document.getElementById('no-tests-message');
            const lastUpdatedEl = document.getElementById('last-updated');
            const activeCountEl = document.getElementById('active-count');
            const refreshBtn = document.getElementById('refresh-btn');
            const autoRefreshToggle = document.getElementById('auto-refresh');
            const filterForm = document.getElementById('filter-form');
            const languageFilter = document.getElementById('language-filter');
            const levelFilter = document.getElementById('level-filter');
            const resetFilterBtn = document.getElementById('reset-filter');
            
            // Variables
            let refreshInterval;
            const REFRESH_INTERVAL = 3000; // 3 seconds - more frequent updates
            
            // Functions
            function formatTime(timeString) {
                return timeString;
            }
            
            function fetchData() {
                // Get filter values
                const language = languageFilter.value;
                const level = levelFilter.value;
                
                // Build query string
                let queryParams = [];
                if (language) queryParams.push(`language=${language}`);
                if (level) queryParams.push(`level=${level}`);
                const queryString = queryParams.length > 0 ? `?${queryParams.join('&')}` : '';
                
                // Add a cache-busting parameter to prevent caching
                const cacheBuster = `${queryParams.length > 0 ? '&' : '?'}t=${Date.now()}`;
                
                // Show loading state but only if the table is empty
                if (!tableBody.innerHTML || tableBody.innerHTML.includes('Loading')) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 mb-0">Memuat data...</p>
                            </td>
                        </tr>
                    `;
                }
                
                // Fetch data from server
                fetch(`{{ route('admin.post-test.data') }}${queryString}${cacheBuster}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Cache-Control': 'no-cache, no-store, must-revalidate'
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 403) {
                            window.location.href = '{{ route('dashboard') }}';
                            throw new Error('Unauthorized access');
                        }
                        throw new Error(`Server responded with status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Update last updated time
                    const now = new Date();
                    lastUpdatedEl.textContent = now.toLocaleTimeString();
                    
                    // Get active tests
                    const activeTests = data.active_tests || [];
                    
                    // Update active count
                    activeCountEl.textContent = activeTests.length;
                    
                    // If active tests array is empty, set a retry timeout
                    if (activeTests.length === 0) {
                        tableBody.innerHTML = '';
                        noTestsMessage.classList.remove('d-none');
                        
                        // Add info text to indicate auto-refreshing
                        noTestsMessage.innerHTML = `
                            <i class="fas fa-exclamation-triangle me-2"></i> 
                            Tidak ada siswa yang sedang mengerjakan post-test saat ini.
                            <div class="mt-2">
                                <small class="text-muted">Data akan otomatis diperbarui ketika ada siswa yang mulai mengerjakan post-test.</small>
                            </div>
                        `;
                    } else {
                        noTestsMessage.classList.add('d-none');
                        
                        // Build table rows
                        let tableHtml = '';
                        
                        activeTests.forEach(test => {
                            // Skip any tests with zero or negative remaining time
                            if (test.remaining_time === '00:00' || parseInt(test.remaining_time.split(':')[0]) < 0) {
                                return;
                            }
                            
                            // Determine progress bar color
                            let progressBarColor = 'bg-success';
                            if (test.progress_percentage < 30) {
                                progressBarColor = 'bg-danger';
                            } else if (test.progress_percentage < 70) {
                                progressBarColor = 'bg-warning';
                            }
                            
                            // Determine time bar color
                            let timeBarColor = 'bg-success';
                            const timePercentRemaining = 100 - test.time_percentage;
                            if (timePercentRemaining < 30) {
                                timeBarColor = 'bg-danger';
                            } else if (timePercentRemaining < 70) {
                                timeBarColor = 'bg-warning';
                            }
                            
                            tableHtml += `
                                <tr data-student-id="${test.student_id}" data-language="${test.language}" data-level="${test.level}">
                                    <td>${test.student_name}</td>
                                    <td>${test.language_name}</td>
                                    <td>${test.level} (${test.level_name})</td>
                                    <td>${test.start_time} WIB</td>
                                    <td>${test.time_limit} menit</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 10px;">
                                                <div class="progress-bar ${progressBarColor}" role="progressbar" style="width: ${test.progress_percentage}%"></div>
                                            </div>
                                            <span class="ms-2">${test.questions_answered}/${test.questions_total} (${test.progress_percentage}%)</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 10px;">
                                                <div class="progress-bar ${timeBarColor}" role="progressbar" style="width: ${timePercentRemaining}%"></div>
                                            </div>
                                            <span class="ms-2">${test.remaining_time}</span>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        // If no rows were added after filtering out completed tests
                        if (tableHtml === '') {
                            tableBody.innerHTML = '';
                            noTestsMessage.classList.remove('d-none');
                            
                            // Add info text to indicate auto-refreshing
                            noTestsMessage.innerHTML = `
                                <i class="fas fa-exclamation-triangle me-2"></i> 
                                Tidak ada siswa yang sedang mengerjakan post-test saat ini.
                                <div class="mt-2">
                                    <small class="text-muted">Data akan otomatis diperbarui ketika ada siswa yang mulai mengerjakan post-test.</small>
                                </div>
                            `;
                            
                            // Update active count again
                            activeCountEl.textContent = '0';
                        } else {
                            tableBody.innerHTML = tableHtml;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center text-danger py-4">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Terjadi kesalahan saat mengambil data. Silakan coba lagi.
                            </td>
                        </tr>
                    `;
                });
            }
            
            function startAutoRefresh() {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                }
                
                if (autoRefreshToggle.checked) {
                    refreshInterval = setInterval(fetchData, REFRESH_INTERVAL);
                }
            }
            
            // Event listeners
            refreshBtn.addEventListener('click', function() {
                fetchData();
            });
            
            autoRefreshToggle.addEventListener('change', function() {
                startAutoRefresh();
            });
            
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                fetchData();
                
                // Update URL with filter params
                const language = languageFilter.value;
                const level = levelFilter.value;
                let url = new URL(window.location.href);
                
                if (language) {
                    url.searchParams.set('language', language);
                } else {
                    url.searchParams.delete('language');
                }
                
                if (level) {
                    url.searchParams.set('level', level);
                } else {
                    url.searchParams.delete('level');
                }
                
                window.history.pushState({}, '', url);
            });
            
            resetFilterBtn.addEventListener('click', function() {
                languageFilter.value = '';
                levelFilter.value = '';
                filterForm.dispatchEvent(new Event('submit'));
            });
            
            // Initial data fetch
            fetchData();
            
            // Start auto-refresh
            startAutoRefresh();
        });
    </script>
    @endpush
</x-app-layout>