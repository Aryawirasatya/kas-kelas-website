{{-- resources/views/reports/personal_excel.blade.php --}}
@php
    $history      = $history ?? [];
    $studentName  = $studentName ?? '-';
    $classLabel   = $classLabel ?? '-';
    $academicYear = $academicYear ?? '-';
@endphp

<table>
    <tr>
        <th colspan="5">RIWAYAT PEMBAYARAN KAS</th>
    </tr>
    <tr>
        <td>Nama Siswa</td>
        <td colspan="4">{{ $studentName }}</td>
    </tr>
    <tr>
        <td>Kelas</td>
        <td colspan="4">{{ $classLabel }} ({{ $academicYear }})</td>
    </tr>
    <tr><td colspan="5"></td></tr>

    <tr>
        <th>Periode</th>
        <th>Tanggal Bayar</th>
        <th>Nominal</th>
        <th>Status</th>
        <th>Keterangan</th>
    </tr>

    @forelse($history as $row)
        <tr>
            <td>{{ $row['period_label'] ?? '-' }}</td>
            <td>
                {{ isset($row['date']) ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '-' }}
            </td>
            <td>Rp {{ number_format($row['amount'] ?? 0, 0, ',', '.') }}</td>
            <td>{{ $row['status'] ?? '-' }}</td>
            <td></td>
        </tr>
    @empty
        <tr>
            <td colspan="5">Belum ada pembayaran tercatat.</td>
        </tr>
    @endforelse
</table>
