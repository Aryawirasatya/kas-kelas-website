{{-- resources/views/reports/index.blade.php --}}
@php
    /** @var \App\Models\User $user */
    $user            = $user ?? auth()->user();

    // Role utama
    $role            = $role ?? ($report['role'] ?? 'guest');

    // Data utama laporan
    $summary         = $summary ?? ($report['summary'] ?? []);
    $periods         = $periods ?? ($report['periods'] ?? []);
    $categories      = $categories ?? ($report['categories'] ?? []);
    $personalHistory = $personalHistory ?? ($report['personalHistory'] ?? null);
    $classYear       = $classYear ?? ($report['classYear'] ?? null);

    // Data untuk chart & filter (akan diisi dari controller kalau sudah siap)
    $chart           = $chart ?? ($report['chart'] ?? null);
    $monthOptions    = $monthOptions ?? [];
    $periodOptions   = $periodOptions ?? [];
    $categoryOptions = $categoryOptions ?? [];
    $filters         = $filters ?? [
        'month'       => null,
        'period_id'   => null,
        'category_id' => null,
    ];
@endphp

@extends('layouts.app')

@section('title', 'Laporan Kas Kelas')

@section('content')
<div class="content-wrapper">

    {{-- HEADER ATAS: Judul + Role + Kelas + Tombol Export --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="page-title mb-1">
                Laporan Kas Kelas
            </h3>
            <small class="text-muted">
                Ringkasan pemasukan, pengeluaran, saldo, dan status kas per periode.
            </small>
            @if($classYear)
                <div class="mt-1 small text-muted">
                    Kelas {{ $classYear->class_label ?? '-' }} • TA {{ $classYear->academic_year ?? '-' }}
                </div>
            @endif
        </div>

        <div class="text-end">
            <div class="mb-2">
                <span class="badge bg-primary">
                    Role: {{ strtoupper($role) }}
                </span>
            </div>

            {{-- TOMBOL EXPORT (hanya guru & bendahara) --}}
            @if (in_array($role, ['guru', 'bendahara']))
                <div class="btn-group mb-1" role="group" aria-label="Export group">
                    <a href="{{ route('reports.export.pdf') }}" class="btn btn-sm btn-outline-danger">
                        <i class="mdi mdi-file-pdf-box"></i> PDF
                    </a>
                    <a href="{{ route('reports.export.excel') }}" class="btn btn-sm btn-outline-success">
                        <i class="mdi mdi-file-excel-box"></i> Excel
                    </a>
                </div>
            @endif

            {{-- Export riwayat pribadi untuk siswa (kalau route-nya sudah dibuat) --}}
            @if ($role === 'siswa')
                <div class="btn-group mb-1" role="group" aria-label="Export personal">
                    <a href="{{ route('reports.export.personal.pdf') }}"
                       class="btn btn-sm btn-outline-danger">
                        <i class="mdi mdi-file-pdf-box"></i> PDF Saya
                    </a>
                    <a href="{{ route('reports.export.personal.excel') }}"
                       class="btn btn-sm btn-outline-success">
                        <i class="mdi mdi-file-excel-box"></i> Excel Saya
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- BREADCRUMB --}}
    <div class="page-header mb-3 pb-0">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">Kas Kelas</li>
                <li class="breadcrumb-item active" aria-current="page">Laporan</li>
            </ol>
        </nav>
    </div>

    {{-- Alert pesan sukses/error --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- =========================
         FILTER (Bulan / Periode / Kategori)
         (Akan aktif kalau controller sudah kirim data filter)
       ========================= --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Bulan</label>
                    <select name="month" class="form-select form-select-sm">
                        <option value="">Semua Bulan</option>
                        @foreach($monthOptions as $opt)
                            <option value="{{ $opt['value'] }}"
                                {{ ($filters['month'] ?? null) === $opt['value'] ? 'selected' : '' }}>
                                {{ $opt['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Periode (Minggu)</label>
                    <select name="period_id" class="form-select form-select-sm">
                        <option value="">Semua Periode</option>
                        @foreach($periodOptions as $opt)
                            <option value="{{ $opt['id'] }}"
                                {{ (string)($filters['period_id'] ?? '') === (string)$opt['id'] ? 'selected' : '' }}>
                                {{ $opt['label'] ?? 'Minggu #' . $opt['id'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Kategori Pengeluaran</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">Semua Kategori</option>
                        @foreach($categoryOptions as $opt)
                            <option value="{{ $opt['category_id'] }}"
                                {{ (string)($filters['category_id'] ?? '') === (string)$opt['category_id'] ? 'selected' : '' }}>
                                {{ $opt['category_name'] ?? 'Tanpa Kategori' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 text-end">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-filter-variant"></i> Terapkan Filter
                    </button>
                    <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================
         GRAFIK BULANAN (Chart.js)
       ========================= --}}
    @if($chart && !empty($chart['labels'] ?? []))
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Grafik Pemasukan vs Pengeluaran (Bulanan)</h4>
                <div style="height: 260px;">
                    <canvas id="chart-income-expense"></canvas>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================
         RINGKASAN KAS (4 CARD UTAMA)
       ========================= --}}
    <div class="row mb-4">
        <div class="col-md-3 grid-margin">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="mb-1 text-muted small">Total Pemasukan</p>
                    <h4 class="fw-semibold mb-0">
                        Rp {{ number_format($summary['total_income'] ?? 0, 0, ',', '.') }}
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="mb-1 text-muted small">Total Pengeluaran</p>
                    <h4 class="fw-semibold mb-0">
                        Rp {{ number_format($summary['total_expense'] ?? 0, 0, ',', '.') }}
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="mb-1 text-muted small">Saldo Akhir</p>
                    <h4 class="fw-semibold mb-0">
                        Rp {{ number_format($summary['balance'] ?? 0, 0, ',', '.') }}
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="mb-1 text-muted small">Total Tunggakan Kelas</p>
                    <h4 class="fw-semibold mb-0 text-danger">
                        Rp {{ number_format($summary['total_arrears'] ?? 0, 0, ',', '.') }}
                    </h4>
                </div>
            </div>
        </div>
    </div>

    {{-- INFO SINGKAT DI BAWAH RINGKASAN --}}
    <div class="row mb-4">
        <div class="col-md-4 grid-margin">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 text-muted small">Jumlah Periode Kas</p>
                        <h5 class="fw-semibold mb-0">{{ $summary['period_count'] ?? 0 }} periode</h5>
                    </div>
                    <i class="mdi mdi-calendar-multiple-check fs-3 text-muted"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 grid-margin">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 text-muted small">Jumlah Siswa Aktif</p>
                        <h5 class="fw-semibold mb-0">{{ $summary['student_count'] ?? 0 }} siswa</h5>
                    </div>
                    <i class="mdi mdi-account-multiple fs-3 text-muted"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 grid-margin">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 text-muted small">Nominal Kas per Minggu</p>
                        <h5 class="fw-semibold mb-0">
                            Rp {{ number_format($summary['kas_nominal'] ?? 0, 0, ',', '.') }}
                        </h5>
                    </div>
                    <i class="mdi mdi-cash fs-3 text-muted"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- REKAP PER PERIODE --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="card-title mb-0">Rekap Per Periode (Minggu)</h4>
                <small class="text-muted">
                    Target = jumlah siswa aktif × nominal kas per minggu.
                </small>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Periode</th>
                            <th>Rentang Tanggal</th>
                            <th class="text-end">Target</th>
                            <th class="text-end">Masuk</th>
                            <th class="text-end">Tunggakan</th>
                            <th class="text-center">% Tercapai</th>
                            <th class="text-center">Sudah Lunas</th>
                            <th class="text-center">Belum Lunas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $p)
                            <tr>
                                <td>{{ $p['label'] ?? '-' }}</td>
                                <td>
                                    {{ isset($p['date_start']) ? \Illuminate\Support\Carbon::parse($p['date_start'])->format('d/m/Y') : '-' }}
                                    -
                                    {{ isset($p['date_end']) ? \Illuminate\Support\Carbon::parse($p['date_end'])->format('d/m/Y') : '-' }}
                                </td>
                                <td class="text-end">
                                    Rp {{ number_format($p['target'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    Rp {{ number_format($p['paid'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    Rp {{ number_format($p['arrears'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ ($p['completion_percent'] ?? 0) >= 80 ? 'success' : 'warning' }}">
                                        {{ $p['completion_percent'] ?? 0 }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    {{ $p['paid_students_count'] ?? 0 }} siswa
                                </td>
                                <td class="text-center">
                                    {{ $p['unpaid_students_count'] ?? 0 }} siswa
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    Belum ada periode kas untuk tahun ajaran ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- PENGELUARAN PER KATEGORI --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="card-title mb-0">Pengeluaran per Kategori</h4>
                <small class="text-muted">
                    Menunjukkan penggunaan dana kas berdasarkan jenis pengeluaran.
                </small>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kategori</th>
                            <th class="text-end">Total Pengeluaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $c)
                            <tr>
                                <td>{{ $c['category_name'] ?? '-' }}</td>
                                <td class="text-end">
                                    Rp {{ number_format($c['total'] ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">
                                    Belum ada pengeluaran tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- RIWAYAT PRIBADI SISWA --}}
    @if ($role === 'siswa')
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Riwayat Pembayaran Saya</h4>
                <p class="text-muted small mb-3">
                    Tabel ini hanya menampilkan pembayaran atas nama akun Anda, bukan siswa lain.
                </p>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Periode</th>
                                <th>Tanggal Bayar</th>
                                <th class="text-end">Nominal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($personalHistory && count($personalHistory))
                                @foreach ($personalHistory as $row)
                                    <tr>
                                        <td>{{ $row['period_label'] ?? '-' }}</td>
                                        <td>
                                            {{ isset($row['date']) ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="text-end">
                                            Rp {{ number_format($row['amount'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ ($row['status'] ?? '') === 'Lunas' ? 'success' : 'warning' }}">
                                                {{ $row['status'] ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        Belum ada pembayaran kas yang tercatat atas nama Anda.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
    @if($chart && !empty($chart['labels'] ?? []))
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const canvas = document.getElementById('chart-income-expense');
                if (!canvas) return;

                const dataLabels = @json($chart['labels']);
                const incomeData = @json($chart['income']);
                const expenseData = @json($chart['expense']);

                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: dataLabels,
                        datasets: [
                            {
                                label: 'Pemasukan',
                                data: incomeData,
                                borderWidth: 1,
                            },
                            {
                                label: 'Pengeluaran',
                                data: expenseData,
                                borderWidth: 1,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
        </script>
    @endif
@endpush
