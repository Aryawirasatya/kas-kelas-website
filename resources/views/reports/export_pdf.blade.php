{{-- resources/views/reports/export_pdf.blade.php --}}
@php
    $summary    = $summary ?? [];
    $periods    = $periods ?? [];
    $categories = $categories ?? [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kas Kelas</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        h2, h3, h4 {
            margin: 0;
            padding: 0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #555;
            padding: 4px 6px;
        }
        th {
            background-color: #f0f0f0;
        }
        .no-border td {
            border: none !important;
            padding: 2px 0;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 4px;
            padding: 4px 0;
            border-bottom: 1px solid #555;
        }
        .small {
            font-size: 10px;
            color: #555;
        }
    </style>
</head>
<body>
    {{-- HEADER --}}
    <h2 class="text-center mb-1">LAPORAN KAS KELAS</h2>

    @if($classYear ?? false)
        <p class="text-center mb-3 small">
            Kelas {{ $classYear->class_label ?? '-' }} · Tahun Ajaran {{ $classYear->academic_year ?? '-' }}
        </p>
    @endif

    {{-- RINGKASAN KAS --}}
    <div class="mb-3">
        <div class="section-title">Ringkasan Kas</div>
        <table>
            <tr>
                <td>Total Pemasukan</td>
                <td class="text-right">
                    Rp {{ number_format($summary['total_income'] ?? 0, 0, ',', '.') }}
                </td>
                <td>Total Pengeluaran</td>
                <td class="text-right">
                    Rp {{ number_format($summary['total_expense'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Saldo Akhir</td>
                <td class="text-right">
                    Rp {{ number_format($summary['balance'] ?? 0, 0, ',', '.') }}
                </td>
                <td>Total Tunggakan</td>
                <td class="text-right">
                    Rp {{ number_format($summary['total_arrears'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td>Jumlah Periode</td>
                <td>{{ $summary['period_count'] ?? 0 }} periode</td>
                <td>Jumlah Siswa Aktif</td>
                <td>{{ $summary['student_count'] ?? 0 }} siswa</td>
            </tr>
            <tr>
                <td>Nominal Kas / Minggu</td>
                <td class="text-right">
                    Rp {{ number_format($summary['kas_nominal'] ?? 0, 0, ',', '.') }}
                </td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>

    {{-- REKAP PER PERIODE --}}
    <div class="mb-3">
        <div class="section-title">Rekap Per Periode (Minggu)</div>
        <table>
            <thead>
                <tr>
                    <th>Periode</th>
                    <th>Tanggal Mulai</th>
                    <th>Tanggal Selesai</th>
                    <th class="text-right">Target</th>
                    <th class="text-right">Masuk</th>
                    <th class="text-right">Tunggakan</th>
                    <th class="text-right">% Tercapai</th>
                    <th class="text-center">Lunas / Belum</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($periods as $p)
                    <tr>
                        <td>{{ $p['label'] ?? '-' }}</td>
                        <td>
                            {{ isset($p['date_start']) ? \Illuminate\Support\Carbon::parse($p['date_start'])->format('d/m/Y') : '-' }}
                        </td>
                        <td>
                            {{ isset($p['date_end']) ? \Illuminate\Support\Carbon::parse($p['date_end'])->format('d/m/Y') : '-' }}
                        </td>
                        <td class="text-right">
                            Rp {{ number_format($p['target'] ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-right">
                            Rp {{ number_format($p['paid'] ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-right">
                            Rp {{ number_format($p['arrears'] ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-right">
                            {{ $p['completion_percent'] ?? 0 }}%
                        </td>
                        <td class="text-center">
                            {{ $p['paid_students_count'] ?? 0 }} lunas /
                            {{ $p['unpaid_students_count'] ?? 0 }} belum
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            Belum ada periode kas untuk tahun ajaran ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PENGELUARAN PER KATEGORI --}}
    <div class="mb-3">
        <div class="section-title">Pengeluaran per Kategori</div>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="text-right">Total Pengeluaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $c)
                    <tr>
                        <td>{{ $c['category_name'] ?? '-' }}</td>
                        <td class="text-right">
                            Rp {{ number_format($c['total'] ?? 0, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center">
                            Belum ada pengeluaran tercatat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- FOOTER --}}
    <div class="mb-1 small">
        Dicetak pada: {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
    </div>
</body>
</html>
