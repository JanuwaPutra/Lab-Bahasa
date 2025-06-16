<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Tambah Soal Baru</h1>
          <a href="{{ route('questions.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Soal
          </a>
        </div>
      </div>
    </div>

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
            <h5 class="mb-0">Form Soal Baru</h5>
          </div>
          <div class="card-body">
            <form action="{{ route('questions.store') }}" method="POST" id="questionForm">
              @csrf
              
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="assessment_type" class="form-label">Tipe Tes</label>
                  <select name="assessment_type" id="assessment_type" class="form-select" required>
                    <option value="">Pilih Tipe Tes</option>
                    <option value="pretest" {{ old('assessment_type') == 'pretest' ? 'selected' : '' }}>Pretest</option>
                    <option value="post_test" {{ old('assessment_type') == 'post_test' ? 'selected' : '' }}>Post-test</option>
                    <option value="placement" {{ old('assessment_type') == 'placement' ? 'selected' : '' }}>Placement Test</option>
                    <option value="listening" {{ old('assessment_type') == 'listening' ? 'selected' : '' }}>Listening Test</option>
                    <option value="reading" {{ old('assessment_type') == 'reading' ? 'selected' : '' }}>Reading Test</option>
                    <option value="speaking" {{ old('assessment_type') == 'speaking' ? 'selected' : '' }}>Speaking Test</option>
                    <option value="grammar" {{ old('assessment_type') == 'grammar' ? 'selected' : '' }}>Grammar Test</option>
                  </select>
                </div>
                
                <div class="col-md-6">
                  <label for="type" class="form-label">Tipe Pertanyaan</label>
                  <select name="type" id="type" class="form-select" required>
                    <option value="">Pilih Tipe Pertanyaan</option>
                    <option value="multiple_choice" {{ old('type') == 'multiple_choice' ? 'selected' : '' }}>Pilihan Ganda</option>
                    <option value="true_false" {{ old('type') == 'true_false' ? 'selected' : '' }}>Benar/Salah</option>
                    <option value="essay" {{ old('type') == 'essay' ? 'selected' : '' }}>Esai</option>
                    <option value="fill_blank" {{ old('type') == 'fill_blank' ? 'selected' : '' }}>Isian</option>
                  </select>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="text" class="form-label">Teks Pertanyaan</label>
                <textarea name="text" id="text" class="form-control" rows="3" required>{{ old('text') }}</textarea>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-4" id="level_container">
                  <label for="level" class="form-label">Level</label>
                  <select name="level" id="level" class="form-select" required>
                    <option value="">Pilih Level</option>
                    <option value="1" {{ old('level') == 1 ? 'selected' : '' }}>Level 1 (Beginner)</option>
                    <option value="2" {{ old('level') == 2 ? 'selected' : '' }}>Level 2 (Intermediate)</option>
                    <option value="3" {{ old('level') == 3 ? 'selected' : '' }}>Level 3 (Advanced)</option>
                  </select>
                  <!-- Hidden input for pretest -->
                  <input type="hidden" name="level_hidden" id="level_hidden" value="1">
                </div>
                
                <div class="col-md-4" id="points_container">
                  <label for="points" class="form-label">Skor Default</label>
                  <div class="input-group">
                    <input type="number" name="points" id="points" class="form-control" min="1" value="{{ old('points', 1) }}" required>
                    <span class="input-group-text" data-bs-toggle="tooltip" data-bs-placement="top" title="Skor default yang digunakan ketika opsi tidak memiliki skor spesifik">
                      <i class="fas fa-info-circle"></i>
                    </span>
                  </div>
                  <small class="text-muted">Digunakan untuk soal esai dan isian atau jika opsi tidak memiliki skor.</small>
                </div>
                
                <div class="col-md-4">
                  <label for="language" class="form-label">Bahasa</label>
                  <select name="language" id="language" class="form-select" required>
                    <option value="id" {{ old('language') == 'id' ? 'selected' : '' }}>Indonesia</option>
                    <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>English</option>
                  </select>
                </div>
              </div>
              
              <!-- Dynamic fields based on question type -->
              <div id="multiple_choice_fields" class="question-type-fields" style="display: {{ old('type') == 'multiple_choice' ? 'block' : 'none' }};">
                <div class="mb-3">
                  <label class="form-label">Pilihan Jawaban dan Skor</label>
                  <div id="options-container">
                    @if(old('options'))
                      @foreach(old('options') as $index => $option)
                      <div class="input-group mb-2 option-row">
                        <span class="input-group-text">{{ chr(65 + $index) }}</span>
                        <input type="text" name="options[]" class="form-control" value="{{ $option }}" required>
                        <span class="input-group-text">Skor</span>
                        <input type="number" name="option_scores[]" class="form-control" value="{{ old('option_scores.'.$index, 0) }}" style="max-width: 80px;">
                        <button type="button" class="btn btn-danger remove-option"><i class="fas fa-times"></i></button>
                      </div>
                      @endforeach
                    @else
                      @for($i = 0; $i < 4; $i++)
                      <div class="input-group mb-2 option-row">
                        <span class="input-group-text">{{ chr(65 + $i) }}</span>
                        <input type="text" name="options[]" class="form-control" required>
                        <span class="input-group-text">Skor</span>
                        <input type="number" name="option_scores[]" class="form-control" value="0" style="max-width: 80px;">
                        <button type="button" class="btn btn-danger remove-option"><i class="fas fa-times"></i></button>
                      </div>
                      @endfor
                    @endif
                  </div>
                  <button type="button" id="add-option" class="btn btn-sm btn-secondary mt-2">
                    <i class="fas fa-plus"></i> Tambah Pilihan
                  </button>
                </div>
                
                <div class="mb-3">
                  <label for="correct_answer_mc" class="form-label">Jawaban Benar</label>
                  <select name="correct_answer_select" id="correct_answer_mc" class="form-select" required>
                    <option value="">Pilih Jawaban Benar</option>
                    @for($i = 0; $i < 10; $i++)
                    <option value="{{ $i }}" {{ old('correct_answer') === (string)$i ? 'selected' : '' }}>{{ chr(65 + $i) }}</option>
                    @endfor
                  </select>
                  <input type="hidden" name="correct_answer" id="hidden_correct_answer" value="{{ old('correct_answer') }}">
                </div>
              </div>
              
              <div id="true_false_fields" class="question-type-fields" style="display: {{ old('type') == 'true_false' ? 'block' : 'none' }};">
                <div class="mb-3">
                  <label class="form-label">Jawaban Benar dan Skor</label>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="input-group mb-2">
                        <div class="input-group-text">
                          <input class="form-check-input true-false-option" type="radio" name="correct_answer_tf" id="tf_true" value="true" checked>
                        </div>
                        <label class="form-control" for="tf_true">Benar</label>
                        <span class="input-group-text">Skor</span>
                        <input type="number" name="option_scores[0]" class="form-control" value="{{ old('option_scores.0', 0) }}" style="max-width: 80px;">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="input-group mb-2">
                        <div class="input-group-text">
                          <input class="form-check-input true-false-option" type="radio" name="correct_answer_tf" id="tf_false" value="false">
                        </div>
                        <label class="form-control" for="tf_false">Salah</label>
                        <span class="input-group-text">Skor</span>
                        <input type="number" name="option_scores[1]" class="form-control" value="{{ old('option_scores.1', 0) }}" style="max-width: 80px;">
                      </div>
                    </div>
                  </div>
                  <!-- Hidden field to store the actual value -->
                  <input type="hidden" name="correct_answer" id="correct_answer_hidden" value="true">
                </div>
              </div>
              
              <div id="essay_fields" class="question-type-fields" style="display: {{ old('type') == 'essay' ? 'block' : 'none' }};">
                <div class="mb-3">
                  <label for="min_words" class="form-label">Jumlah Kata Minimal</label>
                  <input type="number" name="min_words" id="min_words" class="form-control" min="10" value="{{ old('min_words', 50) }}">
                </div>
              </div>
              
              <div id="fill_blank_fields" class="question-type-fields" style="display: {{ old('type') == 'fill_blank' ? 'block' : 'none' }};">
                <div class="mb-3">
                  <label for="correct_answer_fb" class="form-label">Jawaban Benar</label>
                  <input type="text" name="correct_answer" id="correct_answer_fb" class="form-control" value="{{ old('correct_answer') }}">
                </div>
              </div>
              
              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="active" id="active" {{ old('active', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="active">Aktif</label>
              </div>
              
              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                  <i class="fas fa-save me-2"></i> Simpan Soal
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const questionTypeSelect = document.getElementById('type');
      const questionTypeFields = document.querySelectorAll('.question-type-fields');
      const optionsContainer = document.getElementById('options-container');
      const addOptionBtn = document.getElementById('add-option');
      const form = document.getElementById('questionForm');
      const submitBtn = document.getElementById('submitBtn');
      const pointsContainer = document.getElementById('points_container');
      
      // Debug - log when the page loads
      console.log('DOM Content Loaded');
      console.log('Current question type:', questionTypeSelect.value);
      
      // Show/hide fields based on question type
      function toggleQuestionTypeFields() {
        const selectedType = questionTypeSelect.value;
        console.log('Toggle fields for type:', selectedType);
        
        // Hide all fields first
        questionTypeFields.forEach(field => {
          field.style.display = 'none';
        });
        
        // Show fields for selected type
        if (selectedType) {
          const fieldToShow = document.getElementById(selectedType + '_fields');
          if (fieldToShow) {
            fieldToShow.style.display = 'block';
            console.log('Showing fields for:', selectedType);
          } else {
            console.log('Field not found for type:', selectedType);
          }
          
          // Show/hide points field based on question type
          if (selectedType === 'multiple_choice' || selectedType === 'true_false') {
            // Hide points field for multiple choice and true/false
            pointsContainer.style.display = 'none';
          } else {
            // For essay and fill_blank, show points
            pointsContainer.style.display = 'block';
          }
        }
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
        
        // Clear existing options
        correctAnswerSelect.innerHTML = '<option value="">Pilih Jawaban Benar</option>';
        
        // Add new options
        for (let i = 0; i < optionRows.length; i++) {
          const option = document.createElement('option');
          option.value = i;
          option.textContent = String.fromCharCode(65 + i);
          correctAnswerSelect.appendChild(option);
        }
        
        // Update hidden field if select has a value
        if (correctAnswerSelect.value) {
          hiddenCorrectAnswer.value = correctAnswerSelect.value;
        }
      }
      
      // Keep hidden field in sync with select
      if (document.getElementById('correct_answer_mc')) {
        document.getElementById('correct_answer_mc').addEventListener('change', function() {
          document.getElementById('hidden_correct_answer').value = this.value;
        });
      }
      
      // Add direct click handler to submit button
      if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
          console.log('Submit button clicked directly');
          
          // Prevent the default click behavior
          e.preventDefault();
          
          // Get the selected question type
          const selectedType = questionTypeSelect.value;
          console.log('Selected question type on submit:', selectedType);
          
          // Validate based on question type
          let isValid = true;
          
          // Basic validation for all question types
          if (!selectedType) {
            alert('Silakan pilih tipe pertanyaan.');
            isValid = false;
          }
          
          const assessmentType = document.getElementById('assessment_type').value;
          if (!assessmentType) {
            alert('Silakan pilih tipe tes.');
            isValid = false;
          }
          
          const questionText = document.getElementById('text').value;
          if (!questionText) {
            alert('Silakan isi teks pertanyaan.');
            isValid = false;
          }
          
          // Check level field only for non-pretest/non-placement types
          if (assessmentType !== 'pretest' && assessmentType !== 'placement') {
            const level = document.getElementById('level').value;
            if (!level) {
              alert('Silakan pilih level.');
              isValid = false;
            }
          }
          
          // Type-specific validation
          if (isValid) {
            if (selectedType === 'multiple_choice') {
              const correctAnswerSelect = document.getElementById('correct_answer_mc');
              const hiddenCorrectAnswer = document.getElementById('hidden_correct_answer');
              
              if (!correctAnswerSelect.value || correctAnswerSelect.value === '') {
                alert('Silakan pilih jawaban yang benar untuk soal pilihan ganda.');
                isValid = false;
              } else {
                // Make sure hidden field has the latest value
                hiddenCorrectAnswer.value = correctAnswerSelect.value;
              }
            } 
            else if (selectedType === 'true_false') {
              // Make sure one option is checked - either true or false
              const tfTrue = document.getElementById('tf_true');
              const tfFalse = document.getElementById('tf_false');
              const hiddenValue = document.getElementById('correct_answer_hidden');
              
              if (!tfTrue.checked && !tfFalse.checked) {
                // Default to true if neither is checked
                tfTrue.checked = true;
              }
              
              // Update the hidden field with the selected value
              hiddenValue.value = tfTrue.checked ? 'true' : 'false';
              console.log('True/False validation - set to:', hiddenValue.value);
            } 
            else if (selectedType === 'fill_blank') {
              const correctAnswer = document.getElementById('correct_answer_fb').value;
              if (!correctAnswer) {
                alert('Silakan masukkan jawaban yang benar untuk soal isian.');
                isValid = false;
              }
            } 
            else if (selectedType === 'essay') {
              const minWords = document.getElementById('min_words').value;
              if (!minWords || minWords < 10) {
                alert('Jumlah kata minimal harus diisi dan minimal 10 kata.');
                isValid = false;
              }
            }
          }
          
          // If all validations pass, submit the form
          if (isValid) {
            console.log('Form validation passed, submitting form manually');
            
            // For true/false questions, explicitly set the correct_answer from the radio buttons
            if (selectedType === 'true_false') {
              const tfTrue = document.getElementById('tf_true');
              const hiddenField = document.getElementById('correct_answer_hidden');
              
              // Set the correct answer based on which radio is checked
              hiddenField.value = tfTrue.checked ? 'true' : 'false';
              console.log('Setting final true/false value to:', hiddenField.value);
            }
            
            form.submit();
          } else {
            console.log('Form validation failed, not submitting');
          }
        });
      }
      
      // Event listeners
      questionTypeSelect.addEventListener('change', function(e) {
        console.log('Question type changed to:', this.value);
        toggleQuestionTypeFields();
      });
      
      addOptionBtn.addEventListener('click', addOptionRow);
      optionsContainer.addEventListener('click', removeOptionRow);
      
      // Function to handle assessment type changes
      function handleAssessmentTypeChange() {
        const assessmentType = document.getElementById('assessment_type').value;
        const levelContainer = document.getElementById('level_container');
        const levelSelect = document.getElementById('level');
        const levelHidden = document.getElementById('level_hidden');
        
        console.log('Assessment type changed to:', assessmentType);
        
        if (assessmentType === 'pretest' || assessmentType === 'placement') {
          // Hide level selection for pretest/placement and set a default value (1)
          levelContainer.style.display = 'none';
          levelSelect.removeAttribute('required');
          levelSelect.value = '1';
          levelHidden.name = 'level';
          levelSelect.name = 'level_original';
        } else {
          // Show level selection for other assessment types
          levelContainer.style.display = 'block';
          levelSelect.setAttribute('required', 'required');
          levelSelect.name = 'level';
          levelHidden.name = 'level_hidden';
        }
      }
      
      // Add event listener to assessment type selector
      document.getElementById('assessment_type').addEventListener('change', handleAssessmentTypeChange);
      
      // Initialize on page load
      toggleQuestionTypeFields();
      updateCorrectAnswerOptions();
      handleAssessmentTypeChange(); // Initial check for assessment type
      
      // Add event listeners to true/false radio buttons
      document.querySelectorAll('.true-false-option').forEach(function(radio) {
        radio.addEventListener('change', function() {
          document.getElementById('correct_answer_hidden').value = this.value;
          console.log('True/False value changed to:', this.value);
        });
      });
      
      // Initialize tooltips
      if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
      }
      
      // Trigger the toggle function again if there's a pre-selected value (e.g., from old input)
      if (questionTypeSelect.value) {
        toggleQuestionTypeFields();
      }
      
      // Initialize level visibility based on pre-selected assessment type
      if (document.getElementById('assessment_type').value) {
        handleAssessmentTypeChange();
      }
    });
  </script>
  @endpush
</x-app-layout> 