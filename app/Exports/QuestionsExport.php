<?php

namespace App\Exports;

use App\Models\Question;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class QuestionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $testType;
    protected $language;

    public function __construct($testType, $language)
    {
        $this->testType = $testType;
        $this->language = $language;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Question::where('assessment_type', $this->testType)
            ->where('language', $this->language)
            ->where(function($query) {
                $query->where('type', 'multiple_choice')
                      ->orWhere('type', 'true_false');
            })
            ->orderBy('level')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Tipe Soal',
            'Pertanyaan',
            'Level',
            'Poin',
            'Status',
            'Opsi A',
            'Opsi B',
            'Opsi C',
            'Opsi D',
            'Opsi E',
            'Jawaban Benar',
            'Skor Opsi A',
            'Skor Opsi B',
            'Skor Opsi C',
            'Skor Opsi D',
            'Skor Opsi E',
        ];
    }

    /**
     * @param Question $question
     */
    public function map($question): array
    {
        $options = $question->options;
        $optionScores = $question->option_scores ?? [];
        
        if ($question->type == 'true_false') {
            $options = ['Benar', 'Salah'];
            $correctAnswer = $question->correct_answer == 'true' ? 'A' : 'B';
        } else {
            $correctIndex = is_numeric($question->correct_answer) ? (int) $question->correct_answer : null;
            $correctAnswer = is_null($correctIndex) ? '' : chr(65 + $correctIndex);
        }
        
        return [
            $question->id,
            $question->type == 'multiple_choice' ? 'Pilihan Ganda' : 'Benar/Salah',
            $question->text,
            $question->level,
            $question->points,
            $question->active ? 'Aktif' : 'Nonaktif',
            $options[0] ?? '',
            $options[1] ?? '',
            $options[2] ?? '',
            $options[3] ?? '',
            $options[4] ?? '',
            $correctAnswer,
            $optionScores[0] ?? 0,
            $optionScores[1] ?? 0,
            $optionScores[2] ?? 0,
            $optionScores[3] ?? 0,
            $optionScores[4] ?? 0,
        ];
    }

    /**
     * Apply styles to worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
} 