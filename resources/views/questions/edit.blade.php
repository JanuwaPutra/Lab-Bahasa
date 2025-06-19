<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Edit Soal</h1>
          <a href="{{ route('questions.index', ['type' => $question->assessment_type]) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Soal
          </a>
        </div>
      </div>
    </div>

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
              Anda hanya dapat mengedit soal dengan bahasa dan level yang sesuai dengan pengaturan Anda.
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

    @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit Soal</h5>
          </div>
          <div class="card-body">
            <form action="{{ route('questions.update', $question->id) }}" method="POST">
              @csrf
              @method('PUT')
              
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Tipe Tes</label>
                  <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $question->assessment_type)) }}" disabled>
                  <input type="hidden" name="assessment_type" value="{{ $question->assessment_type }}">
                </div>
                
                <div class="col-md-6">
                  <label class="form-label">Tipe Pertanyaan</label>
                  <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $question->type)) }}" disabled>
                  <input type="hidden" name="type" value="{{ $question->type }}">
                </div>
              </div>
              
              <div class="mb-3">
                <label for="text" class="form-label">Teks Pertanyaan</label>
                <textarea name="text" id="text" class="form-control" rows="3" required>{{ old('text', $question->text) }}</textarea>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-4">
                  <label for="level" class="form-label">Level</label>
                  <select name="level" id="level" class="form-select" required>
                    <option value="">Pilih Level</option>
                    @if(Auth::user()->hasRole('admin'))
                      <option value="1" {{ old('level', $question->level) == 1 ? 'selected' : '' }}>Level 1 (Beginner)</option>
                      <option value="2" {{ old('level', $question->level) == 2 ? 'selected' : '' }}>Level 2 (Intermediate)</option>
                      <option value="3" {{ old('level', $question->level) == 3 ? 'selected' : '' }}>Level 3 (Advanced)</option>
                    @else
                      @foreach($teacherLanguageSettings ?? [] as $setting)
                        <option value="{{ $setting['level'] }}" {{ old('level', $question->level) == $setting['level'] ? 'selected' : '' }}>
                          Level {{ $setting['level'] }} ({{ $setting['level_name'] }})
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>
                
                <div class="col-md-4" id="points-container" style="{{ $question->type == 'multiple_choice' || $question->type == 'true_false' ? 'display:none;' : '' }}">
                  <label for="points" class="form-label">Skor Default</label>
                  <div class="input-group">
                    <input type="number" name="points" id="points" class="form-control" min="1" value="{{ old('points', $question->points) }}" required>
                    <span class="input-group-text" data-bs-toggle="tooltip" data-bs-placement="top" title="Skor default yang digunakan ketika opsi tidak memiliki skor spesifik">
                      <i class="fas fa-info-circle"></i>
                    </span>
                  </div>
                  <small class="text-muted">Digunakan untuk soal esai dan isian atau jika opsi tidak memiliki skor.</small>
                </div>
                
                <div class="col-md-4">
                  <label for="language" class="form-label">Bahasa</label>
                  <select name="language" id="language" class="form-select" required>
                    @if(Auth::user()->hasRole('admin'))
                      <option value="id" {{ old('language', $question->language) == 'id' ? 'selected' : '' }}>Indonesia</option>
                      <option value="en" {{ old('language', $question->language) == 'en' ? 'selected' : '' }}>English</option>
                      <option value="ru" {{ old('language', $question->language) == 'ru' ? 'selected' : '' }}>Russian</option>
                    @else
                      @foreach($teacherLanguageSettings ?? [] as $setting)
                        <option value="{{ $setting['language_code'] }}" {{ old('language', $question->language) == $setting['language_code'] ? 'selected' : '' }}>
                          {{ $setting['language'] }}
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>
              </div>
              
              <!-- Type-specific fields -->
              @if($question->type == 'multiple_choice')
              <div class="mb-3">
                <label class="form-label">Pilihan Jawaban dan Skor</label>
                <div id="options-container">
                  @php
                    $options = is_string($question->options) ? json_decode($question->options, true) : $question->options;
                    $options = is_array($options) ? $options : [];
                    
                    $optionScores = is_string($question->option_scores) ? json_decode($question->option_scores, true) : $question->option_scores;
                    $optionScores = is_array($optionScores) ? $optionScores : [];
                  @endphp
                  @foreach($options as $index => $option)
                  <div class="input-group mb-2 option-row">
                    <span class="input-group-text">{{ chr(65 + $index) }}</span>
                    <input type="text" name="options[]" class="form-control" value="{{ old('options.'.$index, $option) }}" required>
                    <span class="input-group-text">Skor</span>
                    <input type="number" name="option_scores[]" class="form-control" value="{{ old('option_scores.'.$index, $optionScores[$index] ?? 0) }}" style="max-width: 80px;">
                    <button type="button" class="btn btn-danger remove-option"><i class="fas fa-times"></i></button>
                  </div>
                  @endforeach
                </div>
                <button type="button" id="add-option" class="btn btn-sm btn-secondary mt-2">
                  <i class="fas fa-plus"></i> Tambah Pilihan
                </button>
              </div>
              
              <div class="mb-3">
                <label for="correct_answer_mc" class="form-label">Jawaban Benar</label>
                <select name="correct_answer_select" id="correct_answer_mc" class="form-select" required>
                  @foreach($options as $index => $option)
                  <option value="{{ $index }}" {{ old('correct_answer', $question->correct_answer) == $index ? 'selected' : '' }}>{{ chr(65 + $index) }}</option>
                  @endforeach
                </select>
                <input type="hidden" name="correct_answer" id="hidden_correct_answer" value="{{ old('correct_answer', $question->correct_answer) }}">
              </div>
              @endif
              
              @if($question->type == 'true_false')
              <div class="mb-3">
                <label class="form-label">Jawaban Benar dan Skor</label>
                <div class="row">
                  <div class="col-md-6">
                    <div class="input-group mb-2">
                      <div class="input-group-text">
                        <input class="form-check-input" type="radio" name="correct_answer" id="true" value="true" {{ old('correct_answer', $question->correct_answer) == 'true' ? 'checked' : '' }}>
                      </div>
                      <label class="form-control" for="true">Benar</label>
                      <span class="input-group-text">Skor</span>
                      @php
                        $optionScores = is_array($question->option_scores) ? $question->option_scores : [];
                      @endphp
                      <input type="number" name="option_scores[0]" class="form-control" value="{{ old('option_scores.0', $optionScores[0] ?? 0) }}" style="max-width: 80px;">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="input-group mb-2">
                      <div class="input-group-text">
                        <input class="form-check-input" type="radio" name="correct_answer" id="false" value="false" {{ old('correct_answer', $question->correct_answer) == 'false' ? 'checked' : '' }}>
                      </div>
                      <label class="form-control" for="false">Salah</label>
                      <span class="input-group-text">Skor</span>
                      <input type="number" name="option_scores[1]" class="form-control" value="{{ old('option_scores.1', $optionScores[1] ?? 0) }}" style="max-width: 80px;">
                    </div>
                  </div>
                </div>
              </div>
              @endif
              
              @if($question->type == 'essay')
              <div class="mb-3">
                <label for="min_words" class="form-label">Jumlah Kata Minimal</label>
                <input type="number" name="min_words" id="min_words" class="form-control" min="10" value="{{ old('min_words', $question->min_words) }}">
              </div>
              @endif
              
              @if($question->type == 'fill_blank')
              <div class="mb-3">
                <label for="correct_answer_fb" class="form-label">Jawaban Benar</label>
                <input type="text" name="correct_answer" id="correct_answer_fb" class="form-control" value="{{ old('correct_answer', $question->correct_answer) }}">
              </div>
              @endif
              
              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="active" id="active" {{ old('active', $question->active) ? 'checked' : '' }}>
                <label class="form-check-label" for="active">Aktif</label>
              </div>
              
              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-2"></i> Simpan Perubahan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if($question->type == 'multiple_choice')
  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const optionsContainer = document.getElementById('options-container');
      const addOptionBtn = document.getElementById('add-option');
      const form = document.querySelector('form');
      
      // For teacher role only - set up language/level filtering
      @if(Auth::user()->hasRole('teacher'))
      const languageSelect = document.getElementById('language');
      const levelSelect = document.getElementById('level');
      
      // Store teacher language settings
      const teacherSettings = @json($teacherLanguageSettings ?? []);
      
      // Filter level options based on selected language
      languageSelect.addEventListener('change', function() {
        const selectedLanguage = this.value;
        
        // Clear current options except the first one
        while (levelSelect.options.length > 1) {
          levelSelect.remove(1);
        }
        
        // Add levels for the selected language
        const filteredSettings = teacherSettings.filter(setting => setting.language_code === selectedLanguage);
        filteredSettings.forEach(setting => {
          const option = document.createElement('option');
          option.value = setting.level;
          option.textContent = `Level ${setting.level} (${setting.level_name})`;
          option.selected = (setting.level == {{ $question->level }});
          levelSelect.appendChild(option);
        });
        
        // If only one level is available, select it automatically
        if (filteredSettings.length === 1) {
          levelSelect.value = filteredSettings[0].level;
        }
      });
      
      // Trigger language change on page load
      languageSelect.dispatchEvent(new Event('change'));
      @endif
      
      // Initialize tooltips
      if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
      }
      
      // Add option row
      function addOptionRow() {
        const optionRows = document.querySelectorAll('.option-row');
        const newIndex = optionRows.length;
        
        const optionRow = document.createElement('div');
        optionRow.className = 'input-group mb-2 option-row';
        optionRow.innerHTML = `
          <span class="input-group-text">${String.fromCharCode(65 + newIndex)}</span>
          <input type="text" name="options[]" class="form-control" required>
          <span class="input-group-text">Skor</span>
          <input type="number" name="option_scores[]" class="form-control" value="0" style="max-width: 80px;">
          <button type="button" class="btn btn-danger remove-option"><i class="fas fa-times"></i></button>
        `;
        
        optionsContainer.appendChild(optionRow);
        
        // Update correct answer options
        updateCorrectAnswerOptions();
      }
      
      // Remove option row
      function removeOptionRow(event) {
        if (event.target.classList.contains('remove-option') || event.target.closest('.remove-option')) {
          const optionRows = document.querySelectorAll('.option-row');
          
          // Don't remove if there are only 2 options
          if (optionRows.length <= 2) {
            alert('Minimal harus ada 2 pilihan jawaban.');
            return;
          }
          
          const row = event.target.closest('.option-row');
          row.remove();
          
          // Renumber options
          document.querySelectorAll('.option-row').forEach((row, index) => {
            row.querySelector('.input-group-text').textContent = String.fromCharCode(65 + index);
          });
          
          // Update correct answer options
          updateCorrectAnswerOptions();
        }
      }
      
      // Update correct answer options
      function updateCorrectAnswerOptions() {
        const correctAnswerSelect = document.getElementById('correct_answer_mc');
        const hiddenCorrectAnswer = document.getElementById('hidden_correct_answer');
        const optionRows = document.querySelectorAll('.option-row');
        const currentValue = correctAnswerSelect.value;
        
        // Clear existing options
        correctAnswerSelect.innerHTML = '';
        
        // Add new options
        for (let i = 0; i < optionRows.length; i++) {
          const option = document.createElement('option');
          option.value = i;
          option.textContent = String.fromCharCode(65 + i);
          option.selected = (currentValue == i);
          correctAnswerSelect.appendChild(option);
        }
        
        // Update hidden field with selected value
        if (correctAnswerSelect.value) {
          hiddenCorrectAnswer.value = correctAnswerSelect.value;
        }
      }
      
      // Keep hidden field in sync with select
      document.getElementById('correct_answer_mc').addEventListener('change', function() {
        document.getElementById('hidden_correct_answer').value = this.value;
      });
      
      // Handle form submission
      form.addEventListener('submit', function(e) {
        const correctAnswerSelect = document.getElementById('correct_answer_mc');
        const hiddenCorrectAnswer = document.getElementById('hidden_correct_answer');
        
        if (!correctAnswerSelect.value || correctAnswerSelect.value === '') {
          e.preventDefault();
          alert('Silakan pilih jawaban yang benar untuk soal pilihan ganda.');
          return false;
        }
        
        // Make sure hidden field has the latest value
        hiddenCorrectAnswer.value = correctAnswerSelect.value;
        
        console.log('Submitting form with correct answer:', hiddenCorrectAnswer.value);
        return true;
      });
      
      // Event listeners
      addOptionBtn.addEventListener('click', addOptionRow);
      optionsContainer.addEventListener('click', removeOptionRow);
    });
  </script>
  @endpush
  @endif
</x-app-layout> 