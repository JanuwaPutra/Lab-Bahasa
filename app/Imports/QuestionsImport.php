<?php

namespace App\Imports;

use App\Models\Question;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;
use Illuminate\Support\Str;

class QuestionsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts
{
    protected $testType;
    protected $language;
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $errors = [];

    /**
     * Create a new import instance.
     *
     * @param string $testType
     * @param string $language
     */
    public function __construct($testType, $language)
    {
        $this->testType = $testType;
        $this->language = $language;
    }

    /**
     * @return int
     */
    public function getSuccessCount()
    {
        return $this->successCount;
    }

    /**
     * @return int
     */
    public function getFailedCount()
    {
        return $this->failedCount;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param array $row
     *
     * @return \App\Models\Question|null
     */
    public function model(array $row)
    {
        // Convert column names from Indonesian to English
        $typeMap = [
            'Pilihan Ganda' => 'multiple_choice',
            'Benar/Salah' => 'true_false',
        ];
        
        $type = $typeMap[$row['tipe_soal']] ?? null;
        
        if (!$type) {
            $this->failedCount++;
            return null;
        }
        
        // Handle multiple choice questions
        $options = [];
        $optionScores = [];
        
        // Process options and their scores
        for ($i = 0; $i < 5; $i++) {
            $optionColumn = 'opsi_' . chr(97 + $i); // opsi_a, opsi_b, etc.
            $scoreColumn = 'skor_opsi_' . chr(97 + $i); // skor_opsi_a, skor_opsi_b, etc.
            
            if (isset($row[$optionColumn]) && !empty($row[$optionColumn])) {
                $options[] = $row[$optionColumn];
                $optionScores[] = isset($row[$scoreColumn]) ? (int)$row[$scoreColumn] : 0;
            }
        }
        
        // Process correct answer (convert letter to index)
        $correctAnswer = null;
        if (isset($row['jawaban_benar'])) {
            if ($type == 'multiple_choice') {
                $letter = strtoupper($row['jawaban_benar']);
                if (preg_match('/^[A-E]$/', $letter)) {
                    $correctAnswer = ord($letter) - 65; // Convert A=0, B=1, etc.
                }
            } else if ($type == 'true_false') {
                $letter = strtoupper($row['jawaban_benar']);
                $correctAnswer = ($letter == 'A') ? 'true' : 'false';
            }
        }
        
        // For true/false, ensure we have standard options
        if ($type == 'true_false') {
            $options = ['Benar', 'Salah'];
            if (count($optionScores) < 2) {
                $optionScores = [0, 0];
            }
        }
        
        $active = isset($row['status']) ? Str::lower($row['status']) == 'aktif' : true;
        
        // Create and return the question
        $this->successCount++;
        return new Question([
            'text' => $row['pertanyaan'],
            'type' => $type,
            'options' => $options,
            'option_scores' => $optionScores,
            'correct_answer' => $correctAnswer,
            'level' => $row['level'] ?? 1,
            'points' => $row['poin'] ?? 1,
            'assessment_type' => $this->testType,
            'active' => $active,
            'language' => $this->language,
        ]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'tipe_soal' => 'required|in:Pilihan Ganda,Benar/Salah',
            'pertanyaan' => 'required|string',
            'level' => 'required|integer|min:1|max:3',
            'poin' => 'required|integer|min:1',
            'status' => 'required|in:Aktif,Nonaktif',
            'opsi_a' => 'required|string',
            'opsi_b' => 'required|string',
            'jawaban_benar' => 'required|string',
        ];
    }
    
    /**
     * @param Throwable $e
     */
    public function onError(Throwable $e)
    {
        $this->failedCount++;
        $this->errors[] = [
            'row' => $e->getCode() + 2, // +2 for header and 0-based to 1-based
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
    
    /**
     * @param Failure ...$failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failedCount++;
            $this->errors[] = [
                'row' => $failure->row() + 2, // +2 for header and 0-based to 1-based
                'message' => 'Validation error: ' . implode(', ', $failure->errors())
            ];
        }
    }
    
    /**
     * @return int
     */
    public function batchSize(): int
    {
        return 100;
    }
    
    /**
     * @return array
     */
    public function headingRow(): array
    {
        return [
            'tipe_soal' => 'Tipe Soal',
            'pertanyaan' => 'Pertanyaan',
            'level' => 'Level',
            'poin' => 'Poin',
            'status' => 'Status',
            'opsi_a' => 'Opsi A',
            'opsi_b' => 'Opsi B',
            'opsi_c' => 'Opsi C',
            'opsi_d' => 'Opsi D',
            'opsi_e' => 'Opsi E',
            'jawaban_benar' => 'Jawaban Benar',
            'skor_opsi_a' => 'Skor Opsi A',
            'skor_opsi_b' => 'Skor Opsi B',
            'skor_opsi_c' => 'Skor Opsi C',
            'skor_opsi_d' => 'Skor Opsi D',
            'skor_opsi_e' => 'Skor Opsi E',
        ];
    }
} 