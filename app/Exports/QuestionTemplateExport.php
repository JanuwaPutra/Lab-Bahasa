<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class QuestionTemplateExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, ShouldAutoSize, WithColumnFormatting
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Create a sample row
        return new Collection([
            [
                'Tipe Soal' => 'Pilihan Ganda',
                'Pertanyaan' => 'Bahasa Indonesia adalah bahasa resmi negara...',
                'Level' => 1,
                'Poin' => 5,
                'Status' => 'Aktif',
                'Opsi A' => 'Indonesia',
                'Opsi B' => 'Malaysia',
                'Opsi C' => 'Singapura',
                'Opsi D' => 'Brunei',
                'Opsi E' => '',
                'Jawaban Benar' => 'A',
                'Skor Opsi A' => 5,
                'Skor Opsi B' => 0,
                'Skor Opsi C' => 0,
                'Skor Opsi D' => 0,
                'Skor Opsi E' => 0,
            ],
            [
                'Tipe Soal' => 'Benar/Salah',
                'Pertanyaan' => 'Jakarta adalah ibukota Indonesia.',
                'Level' => 1,
                'Poin' => 1,
                'Status' => 'Aktif',
                'Opsi A' => 'Benar',
                'Opsi B' => 'Salah',
                'Opsi C' => '',
                'Opsi D' => '',
                'Opsi E' => '',
                'Jawaban Benar' => 'A',
                'Skor Opsi A' => 1,
                'Skor Opsi B' => 0,
                'Skor Opsi C' => '',
                'Skor Opsi D' => '',
                'Skor Opsi E' => '',
            ]
        ]);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
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
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 50,
            'C' => 10,
            'D' => 10,
            'E' => 10,
            'F' => 25,
            'G' => 25,
            'H' => 25,
            'I' => 25,
            'J' => 25,
            'K' => 15,
        ];
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER,
            'D' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER,
            'M' => NumberFormat::FORMAT_NUMBER,
            'N' => NumberFormat::FORMAT_NUMBER,
            'O' => NumberFormat::FORMAT_NUMBER,
            'P' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DDDDDD']
            ]
        ]);
        
        // Instructions sheet
        $sheet->getParent()->createSheet()->setTitle('Petunjuk');
        $instructionSheet = $sheet->getParent()->getSheetByName('Petunjuk');
        
        $instructionSheet->setCellValue('A1', 'PETUNJUK PENGISIAN TEMPLATE SOAL');
        $instructionSheet->mergeCells('A1:F1');
        $instructionSheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);
        
        $instructions = [
            ['Kolom', 'Keterangan', 'Format', 'Wajib Diisi', 'Nilai yang Diperbolehkan'],
            ['Tipe Soal', 'Jenis soal yang akan diupload', 'Text', 'Ya', 'Pilihan Ganda atau Benar/Salah'],
            ['Pertanyaan', 'Teks pertanyaan', 'Text', 'Ya', 'Teks bebas'],
            ['Level', 'Level kesulitan soal', 'Angka', 'Ya', '1 (Beginner), 2 (Intermediate), 3 (Advanced)'],
            ['Poin', 'Poin default jika jawaban benar', 'Angka', 'Ya', 'Angka positif'],
            ['Status', 'Status soal', 'Text', 'Ya', 'Aktif atau Nonaktif'],
            ['Opsi A-E', 'Pilihan jawaban', 'Text', 'Minimal Opsi A & B', 'Teks bebas'],
            ['Jawaban Benar', 'Kunci jawaban', 'Text', 'Ya', 'A, B, C, D, atau E (sesuai opsi yang tersedia)'],
            ['Skor Opsi A-E', 'Skor untuk setiap opsi', 'Angka', 'Tidak', 'Angka (default 0)']
        ];
        
        foreach ($instructions as $index => $instruction) {
            $row = $index + 3;
            $instructionSheet->setCellValue('A' . $row, $instruction[0]);
            $instructionSheet->setCellValue('B' . $row, $instruction[1]);
            $instructionSheet->setCellValue('C' . $row, $instruction[2]);
            $instructionSheet->setCellValue('D' . $row, $instruction[3]);
            $instructionSheet->setCellValue('E' . $row, $instruction[4]);
        }
        
        $instructionSheet->getStyle('A3:E' . ($row))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ]);
        
        $instructionSheet->getStyle('A3:E3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DDDDDD']
            ]
        ]);
        
        $instructionSheet->setCellValue('A' . ($row + 2), 'Catatan Penting:');
        $instructionSheet->getStyle('A' . ($row + 2))->applyFromArray(['font' => ['bold' => true]]);
        
        $notes = [
            '1. Untuk tipe soal Benar/Salah, Opsi A selalu "Benar" dan Opsi B selalu "Salah"',
            '2. Opsi C, D, E bisa dikosongkan jika tidak digunakan',
            '3. Jawaban benar harus berupa huruf A, B, C, D, atau E sesuai dengan opsi yang tersedia',
            '4. Level soal: 1 = Beginner, 2 = Intermediate, 3 = Advanced',
            '5. Status soal: "Aktif" atau "Nonaktif"'
        ];
        
        foreach ($notes as $index => $note) {
            $instructionSheet->setCellValue('A' . ($row + 3 + $index), $note);
        }
        
        $instructionSheet->getColumnDimension('A')->setWidth(15);
        $instructionSheet->getColumnDimension('B')->setWidth(35);
        $instructionSheet->getColumnDimension('C')->setWidth(10);
        $instructionSheet->getColumnDimension('D')->setWidth(15);
        $instructionSheet->getColumnDimension('E')->setWidth(40);
        
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
} 