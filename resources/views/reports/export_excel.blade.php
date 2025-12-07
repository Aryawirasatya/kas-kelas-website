{{-- resources/views/reports/export_excel.blade.php --}}
@php
    $summary         = $summary ?? [];
    $periods         = $periods ?? [];
    $categories      = $categories ?? [];
    $personalHistory = $personalHistory ?? null;
@endphp

<table>
    {{-- ================== HEADER LAPORAN ================== --}}
    <tr>
        <th colspan="8" style="font-size:16px; font-weight:bold; text-align:center; border:1px solid #000;">
            LAPORAN KAS KELAS
        </th>
    </tr>

    @if($classYear ?? false)
        <tr>
            <td style="font-weight:bold; border:1px solid #000;">Kelas</td>
            <td colspan="3" style="border:1px solid #000;">
                {{ $classYear->class_label ?? '-' }}
            </td>
            <td style="font-weight:bold; border:1px solid #000;">Tahun Ajaran</td>
            <td colspan="3" style="border:1px solid #000;">
                {{ $classYear->academic_year ?? '-' }}
            </td>
        </tr>
    @endif

    <tr><td colspan="8"></td></tr>

    {{-- ================== RINGKASAN KAS ================== --}}
    <tr>
        <th colspan="8" style="background-color:#e5e5e5; font-weight:bold; border:1px solid #000;">
            RINGKASAN KAS
        </th>
    </tr>

    <tr>
        <td style="font-weight:bold; border:1px solid #000; width:25%;">Total Pemasukan</td>
        <td style="border:1px solid #000; width:25%;">
            Rp {{ number_format($summary['total_income'] ?? 0, 0, ',', '.') }}
        </td>
        <td style="font-weight:bold; border:1px solid #000; width:25%;">Total Pengeluaran</td>
        <td style="border:1px solid #000; width:25%;">
            Rp {{ number_format($summary['total_expense'] ?? 0, 0, ',', '.') }}
        </td>
        <td style="font-weight:bold; border:1px solid #000;">Saldo Akhir</td>
        <td style="border:1px solid #000;">
            Rp {{ number_format($summary['balance'] ?? 0, 0, ',', '.') }}
        </td>
        <td style="font-weight:bold; border:1px solid #000;">Total Tunggakan</td>
        <td style="border:1px solid #000;">
            Rp {{ number_format($summary['total_arrears'] ?? 0, 0, ',', '.') }}
        </td>
    </tr>

    <tr>
        <td style="font-weight:bold; border:1px solid #000;">Jumlah Periode</td>
        <td style="border:1px solid #000;">
            {{ $summary['period_count'] ?? 0 }} periode
        </td>
        <td style="font-weight:bold; border:1px solid #000;">Jumlah Siswa Aktif</td>
        <td style="border:1px solid #000;">
            {{ $summary['student_count'] ?? 0 }} siswa
        </td>
        <td style="font-weight:bold; border:1px solid #000;">Nominal Kas / Minggu</td>
        <td style="border:1px solid #000;">
            Rp {{ number_format($summary['kas_nominal'] ?? 0, 0, ',', '.') }}
        </td>
        <td style="border:1px solid #000;"></td>
        <td style="border:1px solid #000;"></td>
    </tr>

    <tr><td colspan="8"></td></tr>

    {{-- ================== REKAP PER PERIODE ================== --}}
    <tr>
        <th colspan="8" style="background-color:#e5e5e5; font-weight:bold; border:1px solid #000;">
            REKAP PER PERIODE (MINGGU)
        </th>
    </tr>

    <tr>
        <th style="border:1px solid #000;">Periode</th>
        <th style="border:1px solid #000;">Tanggal Mulai</th>
        <th style="border:1px solid #000;">Tanggal Selesai</th>
        <th style="border:1px solid #000;">Target</th>
        <th style="border:1px solid #000;">Masuk</th>
        <th style="border:1px solid #000;">Tunggakan</th>
        <th style="border:1px solid #000;">% Tercapai</th>
        <th style="border:1px solid #000;">Siswa Lunas / Belum</th>
    </tr>

    @forelse ($periods as $p)
        <tr>
            <td style="border:1px solid #000;">
                {{ $p['label'] ?? '-' }}
            </td>
            <td style="border:1px solid #000;">
                {{ isset($p['date_start']) ? \Illuminate\Support\Carbon::parse($p['date_start'])->format('d/m/Y') : '-' }}
            </td>
            <td style="border:1px solid #000;">
                {{ isset($p['date_end']) ? \Illuminate\Support\Carbon::parse($p['date_end'])->format('d/m/Y') : '-' }}
            </td>
            <td style="border:1px solid #000;">
                Rp {{ number_format($p['target'] ?? 0, 0, ',', '.') }}
            </td>
            <td style="border:1px solid #000;">
                Rp {{ number_format($p['paid'] ?? 0, 0, ',', '.') }}
            </td>
            <td style="border:1px solid #000;">
                Rp {{ number_format($p['arrears'] ?? 0, 0, ',', '.') }}
            </td>
            <td style="border:1px solid #000;">
                {{ $p['completion_percent'] ?? 0 }}%
            </td>
            <td style="border:1px solid #000;">
                {{ $p['paid_students_count'] ?? 0 }} lunas /
                {{ $p['unpaid_students_count'] ?? 0 }} belum
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="border:1px solid #000; text-align:center;">
                Belum ada periode kas untuk tahun ajaran ini.
            </td>
        </tr>
    @endforelse

    <tr><td colspan="8"></td></tr>

    {{-- ================== PENGELUARAN PER KATEGORI ================== --}}
    <tr>
        <th colspan="8" style="background-color:#e5e5e5; font-weight:bold; border:1px solid #000;">
            PENGELUARAN PER KATEGORI
        </th>
    </tr>

    <tr>
        <th style="border:1px solid #000;">Kategori</th>
        <th style="border:1px solid #000;">Total Pengeluaran</th>
        <th colspan="6" style="border:1px solid #000;"></th>
    </tr>

    @forelse ($categories as $c)
        <tr>
            <td style="border:1px solid #000;">
                {{ $c['category_name'] ?? '-' }}
            </td>
            <td style="border:1px solid #000;">
                Rp {{ number_format($c['total'] ?? 0, 0, ',', '.') }}
            </td>
            <td colspan="6" style="border:1px solid #000;"></td>
        </tr>
    @empty
        <tr>
            <td colspan="8" style="border:1px solid #000; text-align:center;">
                Belum ada pengeluaran tercatat.
            </td>
        </tr>
    @endforelse

    {{-- ================== RIWAYAT PRIBADI SISWA (OPSIONAL) ================== --}}
    @if ($role === 'siswa' && $personalHistory && count($personalHistory))
        <tr><td colspan="8"></td></tr>

        <tr>
            <th colspan="8" style="background-color:#e5e5e5; font-weight:bold; border:1px solid #000;">
                RIWAYAT PEMBAYARAN SAYA
            </th>
        </tr>

        <tr>
            <th style="border:1px solid #000;">Periode</th>
            <th style="border:1px solid #000;">Tanggal Bayar</th>
            <th style="border:1px solid #000;">Nominal</th>
            <th style="border:1px solid #000;">Status</th>
            <th colspan="4" style="border:1px solid #000;"></th>
        </tr>

        @foreach ($personalHistory as $row)
            <tr>
                <td style="border:1px solid #000;">
                    {{ $row['period_label'] ?? '-' }}
                </td>
                <td style="border:1px solid #000;">
                    {{ isset($row['date']) ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '-' }}
                </td>
                <td style="border:1px solid #000;">
                    Rp {{ number_format($row['amount'] ?? 0, 0, ',', '.') }}
                </td>
                <td style="border:1px solid #000;">
                    {{ $row['status'] ?? '-' }}
                </td>
                <td colspan="4" style="border:1px solid #000;"></td>
            </tr>
        @endforeach
    @endif
</table>
