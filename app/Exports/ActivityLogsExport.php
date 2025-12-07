<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
     * Koleksi log yang akan diexport.
     */
    protected Collection $logs;

    /**
     * Mapping modul (prefix action → label ramah).
     */
    protected array $modules;

    /**
     * Mapping action → kalimat manusiawi.
     */
    protected array $actionLabels;

    /**
     * @param  \Illuminate\Support\Collection  $logs
     * @param  array  $modules
     * @param  array  $actionLabels
     */
    public function __construct(Collection $logs, array $modules, array $actionLabels)
    {
        $this->logs         = $logs;
        $this->modules      = $modules;
        $this->actionLabels = $actionLabels;
    }

    /**
     * Data utama yang akan dikirim ke Excel.
     */
    public function collection(): Collection
    {
        return $this->logs;
    }

    /**
     * Header kolom Excel (baris pertama).
     */
    public function headings(): array
    {
        return [
            'Tanggal',
            'Jam',
            'Nama User',
            'Email User',
            'Role User',
            'Modul / Fitur',
            'Kode Aksi',
            'Ringkasan Aktivitas',
            'Objek / Entity',
            'Nominal',
        ];
    }

    /**
     * Mapping setiap row ActivityLog ke bentuk array untuk Excel.
     *
     * @param  mixed  $log
     * @return array
     */
    public function map($log): array
    {
        $parts       = explode('.', $log->action);
        $moduleKey   = $parts[0] ?? null;
        $actionName  = $parts[1] ?? $log->action;
        $moduleLabel = $this->modules[$moduleKey] ?? $moduleKey ?? '-';

        $to   = $log->to_json ?? [];
        $from = $log->from_json ?? [];

        $message     = $to['message'] ?? null;
        $entityLabel = $to['entity_label'] ?? null;
        $reason      = $to['reason'] ?? null;
        $amount      = $to['amount'] ?? $from['amount'] ?? null;

        $roleNames   = $log->actor ? $log->actor->roles->pluck('name')->toArray() : [];
        $prettyRoles = collect($roleNames)->map(fn($r) => ucfirst($r))->implode(', ');

        $classYearName = $log->classYear?->name;
        $humanAction   = $this->actionLabels[$log->action] ?? null;

        // Kalimat utama
        $description = $humanAction ?: $message ?: ucfirst(str_replace('_', ' ', $log->action));

        // Tambahkan alasan jika ada → jadi satu kalimat yang jelas
        if ($reason) {
            $description .= ' (Alasan: ' . $reason . ')';
        }

        return [
            // Tanggal
            $log->created_at?->format('d-m-Y') ?? '-',

            // Jam
            $log->created_at?->format('H:i') ?? '',

            // User
            $log->actor->name ?? '-',

            // Email
            $log->actor->email ?? '-',

            // Role
            $prettyRoles ?: '-',

            // Modul
            $moduleLabel,

            // Kode aksi (misal: created, approved, dll.)
            $actionName,

            // Ringkasan aktivitas (manusiawi)
            $description,

            // Objek / entity (misal: nama siswa, nama kategori, dsb.)
            $entityLabel ?? '-',

            // Nominal
            $amount ? 'Rp' . number_format($amount, 0, ',', '.') : '-',
            // Tahun ajaran
        ];
    }

    /**
     * Styling dasar untuk sheet Excel.
     * - Header bold, background abu-abu muda
     * - Isi rata atas, supaya teks panjang tetap rapi
     */
    public function styles(Worksheet $sheet): array
    {
        // Baris header (1)
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => [
                    'rgb' => 'F2F2F2',
                ],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical'   => 'center',
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => 'BFBFBF'],
                ],
            ],
        ]);

        // Semua baris isi → wrap text supaya deskripsi panjang tetap terbaca
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A2:K{$lastRow}")->applyFromArray([
            'alignment' => [
                'vertical' => 'top',
                'wrapText' => true,
            ],
        ]);

        return [];
    }
}
