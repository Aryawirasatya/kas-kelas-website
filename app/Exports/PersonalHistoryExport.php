<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PersonalHistoryExport implements FromView, ShouldAutoSize, WithEvents
{
    protected array $history;
    protected ?string $studentName;
    protected ?string $classLabel;
    protected ?string $academicYear;

    public function __construct(array $history, ?string $studentName = null, ?string $classLabel = null, ?string $academicYear = null)
    {
        $this->history      = $history;
        $this->studentName  = $studentName;
        $this->classLabel   = $classLabel;
        $this->academicYear = $academicYear;
    }

    public function view(): View
    {
        return view('reports.personal_excel', [
            'history'      => $this->history,
            'studentName'  => $this->studentName,
            'classLabel'   => $this->classLabel,
            'academicYear' => $this->academicYear,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach (range('A', 'E') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $dimension = $sheet->calculateWorksheetDimension();

                $sheet->getStyle($dimension)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FF000000'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $highestRow = $sheet->getHighestRow();
                for ($row = 1; $row <= $highestRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(-1);
                }

                $sheet->getStyle('A1:E1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            },
        ];
    }
}
