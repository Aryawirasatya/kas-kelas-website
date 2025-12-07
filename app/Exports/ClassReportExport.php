<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ClassReportExport implements FromView, ShouldAutoSize, WithEvents
{
    protected array $report;
    protected string $role;

    public function __construct(array $report)
    {
        $this->report = $report;
        $this->role   = $report['role'] ?? 'guest';
    }

    public function view(): View
    {
        return view('reports.export_excel', [
            'report'          => $this->report,
            'role'            => $this->role,
            'summary'         => $this->report['summary'] ?? [],
            'periods'         => $this->report['periods'] ?? [],
            'categories'      => $this->report['categories'] ?? [],
            'personalHistory' => $this->report['personalHistory'] ?? null,
            'classYear'       => $this->report['classYear'] ?? null,
        ]);
    }

    /**
     * Styling otomatis setelah sheet selesai dibuat.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // 1) Pastikan semua kolom utama auto-size (A–H)
                foreach (range('A', 'H') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // 2) Hitung range yang terpakai (misal: A1:H50)
                $dimension = $sheet->calculateWorksheetDimension();

                // 3) Terapkan border + alignment ke seluruh range yang terpakai
                $sheet->getStyle($dimension)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FF000000'],
                        ],
                    ],
                    'alignment' => [
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                ]);

                // 4) Auto height semua baris
                $highestRow = $sheet->getHighestRow();
                for ($row = 1; $row <= $highestRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(-1);
                }

                // 5) Header judul (A1:H1) dibikin lebih menonjol
                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 15,
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
