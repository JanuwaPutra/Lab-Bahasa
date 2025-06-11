<x-app-layout>
  <div class="container mt-4">
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Tambah Kuis untuk Materi</h1>
          <a href="{{ route('teacher.materials') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Materi
          </a>
        </div>
        <p class="text-muted">Materi: <strong>{{ $material->title }}</strong></p>
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

    <div class="card">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Form Kuis Baru</h5>
      </div>
      <div class="card-body">
        <form action="{{ route('teacher.materials.quiz.store', $material->id) }}" method="POST" id="quizForm">
          @csrf
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="title" class="form-label">Judul Kuis <span class="text-danger">*</span></label>
              <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
            </div>
            
            <div class="col-md-6">
              <label for="passing_score" class="form-label">Skor Kelulusan (%) <span class="text-danger">*</span></label>
              <input type="number" name="passing_score" id="passing_score" class="form-control" min="1" max="100" value="{{ old('passing_score', 70) }}" required>
              <small class="text-muted">Skor minimum untuk lulus kuis (dalam persen)</small>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="description" class="form-label">Deskripsi Kuis</label>
            <textarea name="description" id="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            <small class="text-muted">Berikan deskripsi singkat tentang kuis ini</small>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="time_limit" class="form-label">Batas Waktu (menit)</label>
              <input type="number" name="time_limit" id="time_limit" class="form-control" min="1" value="{{ old('time_limit') }}">
              <small class="text-muted">Kosongkan jika tidak ada batas waktu</small>
            </div>
            
            <div class="col-md-6 d-flex align-items-end">
              <div class="form-check me-4">
                <input class="form-check-input" type="checkbox" name="must_pass" id="must_pass" value="1" {{ old('must_pass', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="must_pass">
                  Wajib Lulus
                </label>
                <small class="text-muted d-block">Siswa harus lulus kuis ini untuk lanjut ke materi berikutnya</small>
              </div>
              
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="active" id="active" value="1" {{ old('active', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="active">
                  Aktif
                </label>
                <small class="text-muted d-block">Kuis yang tidak aktif tidak akan ditampilkan</small>
              </div>
            </div>
          </div>
          
          <hr class="my-4">
          
          <h5 class="mb-3">Pertanyaan Kuis</h5>
          
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Kuis ini hanya menggunakan format pertanyaan pilihan ganda. Pastikan setiap pertanyaan memiliki setidaknya 2 pilihan jawaban.
          </div>
          
          <div id="questions-container">
            <!-- Container untuk pertanyaan, akan diisi dengan JavaScript -->
          </div>
          
          <div class="text-center my-3">
            <button type="button" class="btn btn-success" id="add-question-btn">
              <i class="fas fa-plus-circle me-2"></i> Tambah Pertanyaan Baru
            </button>
          </div>
          
          <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-2"></i> Simpan Kuis
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Template untuk pertanyaan baru -->
  <template id="question-template">
    <div class="card mb-3 question-card">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Pertanyaan <span class="question-number"></span></h6>
        <button type="button" class="btn btn-sm btn-danger delete-question-btn">
          <i class="fas fa-trash"></i>
        </button>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Teks Pertanyaan <span class="text-danger">*</span></label>
          <textarea name="questions[idx][text]" class="form-control question-text" rows="2" required></textarea>
        </div>
        
        <input type="hidden" name="questions[idx][type]" value="multiple_choice">
        
        <div class="mb-3">
          <label class="form-label">Pilihan Jawaban <span class="text-danger">*</span></label>
          <div class="options-container">
            <!-- Container untuk pilihan jawaban, akan diisi dengan JavaScript -->
          </div>
          
          <button type="button" class="btn btn-sm btn-info mt-2 add-option-btn">
            <i class="fas fa-plus me-1"></i> Tambah Pilihan
          </button>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Jawaban Benar <span class="text-danger">*</span></label>
          <select name="questions[idx][correct_answer]" class="form-select correct-answer-select" required>
            <option value="">Pilih jawaban benar</option>
          </select>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Poin</label>
          <input type="number" name="questions[idx][points]" class="form-control" min="1" value="1">
          <small class="text-muted">Jumlah poin untuk pertanyaan ini</small>
        </div>
      </div>
    </div>
  </template>
  
  <!-- Template untuk pilihan jawaban -->
  <template id="option-template">
    <div class="input-group mb-2 option-group">
      <span class="input-group-text option-letter"></span>
      <input type="text" name="questions[q_idx][options][o_idx]" class="form-control option-text" placeholder="Masukkan pilihan jawaban" required>
      <button type="button" class="btn btn-outline-danger delete-option-btn">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </template>
  
  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const questionsContainer = document.getElementById('questions-container');
      const addQuestionBtn = document.getElementById('add-question-btn');
      const questionTemplate = document.getElementById('question-template');
      const optionTemplate = document.getElementById('option-template');
      
      let questionCount = 0;
      
      // Fungsi untuk menambahkan pertanyaan baru
      function addQuestion() {
        const questionIdx = questionCount;
        
        // Clone template pertanyaan
        const newQuestion = document.importNode(questionTemplate.content, true);
        
        // Update nomor pertanyaan
        newQuestion.querySelector('.question-number').textContent = questionIdx + 1;
        
        // Update name attributes dengan index yang benar
        newQuestion.querySelectorAll('[name*="idx"]').forEach(el => {
          el.name = el.name.replace('idx', questionIdx);
        });
        
        // Event listener untuk tombol hapus pertanyaan
        newQuestion.querySelector('.delete-question-btn').addEventListener('click', function() {
          if (confirm('Apakah Anda yakin ingin menghapus pertanyaan ini?')) {
            this.closest('.question-card').remove();
            updateQuestionNumbers();
          }
        });
        
        // Event listener untuk tombol tambah pilihan
        newQuestion.querySelector('.add-option-btn').addEventListener('click', function() {
          addOption(this.closest('.question-card'), questionIdx);
        });
        
        // Tambahkan pertanyaan ke container
        questionsContainer.appendChild(newQuestion);
        
        // Tambahkan 4 pilihan default
        const card = questionsContainer.lastElementChild;
        for (let i = 0; i < 4; i++) {
          addOption(card, questionIdx);
        }
        
        questionCount++;
      }
      
      // Fungsi untuk menambahkan pilihan baru
      function addOption(questionCard, questionIdx) {
        const optionsContainer = questionCard.querySelector('.options-container');
        const optionCount = optionsContainer.children.length;
        
        // Clone template pilihan
        const newOption = document.importNode(optionTemplate.content, true);
        
        // Update option letter (A, B, C, etc.)
        const optionLetter = String.fromCharCode(65 + optionCount); // A=65, B=66, etc.
        newOption.querySelector('.option-letter').textContent = optionLetter;
        
        // Update name attributes dengan index yang benar
        newOption.querySelectorAll('[name*="q_idx"]').forEach(el => {
          el.name = el.name.replace('q_idx', questionIdx);
        });
        newOption.querySelectorAll('[name*="o_idx"]').forEach(el => {
          el.name = el.name.replace('o_idx', optionCount);
        });
        
        // Event listener untuk tombol hapus pilihan
        newOption.querySelector('.delete-option-btn').addEventListener('click', function() {
          if (optionsContainer.children.length <= 2) {
            alert('Pertanyaan harus memiliki minimal 2 pilihan jawaban.');
            return;
          }
          
          this.closest('.option-group').remove();
          updateOptionLetters(questionCard);
          updateCorrectAnswerOptions(questionCard, questionIdx);
        });
        
        // Tambahkan pilihan ke container
        optionsContainer.appendChild(newOption);
        
        // Update dropdown jawaban benar
        updateCorrectAnswerOptions(questionCard, questionIdx);
      }
      
      // Fungsi untuk memperbarui huruf pilihan (A, B, C, dst.) setelah menghapus
      function updateOptionLetters(questionCard) {
        const options = questionCard.querySelectorAll('.option-group');
        
        options.forEach((option, index) => {
          const optionLetter = String.fromCharCode(65 + index);
          option.querySelector('.option-letter').textContent = optionLetter;
          
          // Update name attribute untuk menjaga urutan index
          const questionIdx = option.querySelector('.option-text').name.match(/questions\[(\d+)\]/)[1];
          option.querySelector('.option-text').name = `questions[${questionIdx}][options][${index}]`;
        });
      }
      
      // Fungsi untuk memperbarui dropdown jawaban benar
      function updateCorrectAnswerOptions(questionCard, questionIdx) {
        const options = questionCard.querySelectorAll('.option-group');
        const correctAnswerSelect = questionCard.querySelector('.correct-answer-select');
        
        // Simpan nilai yang dipilih sebelumnya
        const selectedValue = correctAnswerSelect.value;
        
        // Hapus semua opsi kecuali placeholder
        while (correctAnswerSelect.options.length > 1) {
          correctAnswerSelect.remove(1);
        }
        
        // Tambahkan opsi baru berdasarkan pilihan yang ada
        options.forEach((option, index) => {
          const optionText = option.querySelector('.option-text').value || `Pilihan ${String.fromCharCode(65 + index)}`;
          const optionValue = index.toString();
          
          const newOption = new Option(optionText, optionValue);
          correctAnswerSelect.add(newOption);
          
          // Jika opsi ini sebelumnya dipilih, pilih lagi
          if (optionValue === selectedValue) {
            correctAnswerSelect.value = optionValue;
          }
        });
      }
      
      // Fungsi untuk memperbarui nomor pertanyaan setelah menghapus
      function updateQuestionNumbers() {
        const questions = document.querySelectorAll('.question-card');
        questions.forEach((question, index) => {
          question.querySelector('.question-number').textContent = index + 1;
        });
      }
      
      // Event listener untuk tombol tambah pertanyaan
      addQuestionBtn.addEventListener('click', addQuestion);
      
      // Event listener untuk form submission
      document.getElementById('quizForm').addEventListener('submit', function(e) {
        const questions = document.querySelectorAll('.question-card');
        
        if (questions.length === 0) {
          e.preventDefault();
          alert('Harap tambahkan minimal 1 pertanyaan.');
          return;
        }
        
        // Validasi setiap pertanyaan
        let isValid = true;
        questions.forEach((question, index) => {
          const optionsContainer = question.querySelector('.options-container');
          if (optionsContainer.children.length < 2) {
            isValid = false;
            alert(`Pertanyaan ${index + 1} harus memiliki minimal 2 pilihan jawaban.`);
          }
          
          // Perbarui nilai option text di dropdown jawaban benar
          const options = question.querySelectorAll('.option-text');
          options.forEach((option, optIndex) => {
            option.addEventListener('input', function() {
              const correctAnswerSelect = question.querySelector('.correct-answer-select');
              if (correctAnswerSelect.options[optIndex + 1]) {
                correctAnswerSelect.options[optIndex + 1].text = option.value || `Pilihan ${String.fromCharCode(65 + optIndex)}`;
              }
            });
          });
        });
        
        if (!isValid) {
          e.preventDefault();
        }
      });
      
      // Tambahkan pertanyaan pertama saat halaman dimuat
      addQuestion();
    });
  </script>
  @endpush
</x-app-layout> 