{{-- resources/views/reports/personal_pdf.blade.php --}}
@php
    $history      = $history ?? [];
    $studentName  = $studentName ?? '-';
    $classLabel   = $classLabel ?? '-';
    $academicYear = $academicYear ?? '-';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pembayaran Kas</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        h3 {
            margin: 0 0 8px 0;
            text-align: center;
        }
        .mb-2 { margin-bottom: 8px; }
        .small { font-size: 10px; color: #555; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #555;
            padding: 4px 6px;
        }
        th {
            background-color: #f0f0f0;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h3>Riwayat Pembayaran Kas</h3>
    <div class="mb-2 small">
        Nama: <strong>{{ $studentName }}</strong><br>
        Kelas: <strong>{{ $classLabel }}</strong> ({{ $academicYear }})
    </div>

    <table>
        <thead>
            <tr>
                <th>Periode</th>
                <th>Tanggal Bayar</th>
                <th class="text-right">Nominal</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($history as $row)
                <tr>
                    <td>{{ $row['period_label'] ?? '-' }}</td>
                    <td>
                        {{ isset($row['date']) ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '-' }}
                    </td>
                    <td class="text-right">
                        Rp {{ number_format($row['amount'] ?? 0, 0, ',', '.') }}
                    </td>
                    <td>{{ $row['status'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Belum ada pembayaran tercatat.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="small" style="margin-top:10px;">
        Dicetak pada: {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
    </div>
</body>
</html>
