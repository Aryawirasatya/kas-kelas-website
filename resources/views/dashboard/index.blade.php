@extends('layouts.app')

@section('content')
<div class="container py-4">

    @php
        /** @var \Illuminate\Support\Collection $pendingPengeluaran */
        /** @var \Illuminate\Support\Collection $siswaBelumBayar */
        /** @var \Illuminate\Support\Collection $riwayatBayarSaya */

        $u              = $u ?? auth()->user();
        $classYear      = $classYear ?? null;
        $period         = $period ?? null;
        $nominal        = (int)($nominal ?? 0);
        $saldoKas       = (int)($saldoKas ?? 0);
        $bulanIniMasuk  = (int)($bulanIniMasuk ?? 0);
        $bulanIniKeluar = (int)($bulanIniKeluar ?? 0);

        $pendingPengeluaran  = collect($pendingPengeluaran ?? []);
        $siswaBelumBayar     = collect($siswaBelumBayar ?? []);
        $riwayatBayarSaya    = collect($riwayatBayarSaya ?? []);
        $transparansiKelas   = is_array($transparansiKelas ?? null) ? $transparansiKelas : [];

        $mingguBerjalan      = $transparansiKelas['minggu_berjalan'] ?? null;
        $jumlahBelumBayar    = $siswaBelumBayar->count();
        $pendingCount        = $pendingPengeluaran->count();

        // data untuk grafik
        $chartLabels         = $chartLabels ?? [];
        $chartMasuk          = $chartMasuk ?? [];
        $chartKeluar         = $chartKeluar ?? [];

        // ringkasan tunggakan siswa (global)
        $studentTotalSetorAll   = (int)($studentTotalSetorAll ?? 0);
        $studentKewajibanTotal  = (int)($studentKewajibanTotal ?? 0);
        $studentTunggakanTotal  = (int)($studentTunggakanTotal ?? 0);
    @endphp

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="mdi mdi-check-circle-outline me-1"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="mdi mdi-alert-octagon-outline me-1"></i>
            Terjadi kesalahan:
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    {{-- ========== HEADER RINGKAS ========== --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3 py-md-4">
            <div class="row align-items-center g-3">
                {{-- Kiri: salam + info kelas --}}
                <div class="col-lg-8 col-md-7 col-12">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:48px;height:48px;">
                            <i class="mdi mdi-account-circle fs-4"></i>
                        </div>
                        <div class="min-w-0">
                            <h4 class="mb-0 fw-semibold text-truncate">
                                Halo, {{ optional($u)->name }}
                            </h4>
                            <div class="text-muted small mt-1">
                                <i class="mdi mdi-clock-outline me-1"></i>
                                {{ now()->format('d M Y, H:i') }} WIB
                            </div>

                            <div class="mt-2 small text-muted">
                                @if($classYear)
                                    <span class="me-2">
                                        <i class="mdi mdi-school-outline me-1"></i>
                                        {{ $classYear->class_label ?? '-' }}
                                        ({{ $classYear->academic_year ?? '-' }})
                                    </span>
                                @else
                                    <span class="text-danger">
                                        <i class="mdi mdi-alert-circle-outline me-1"></i>
                                        Belum ada tahun ajaran aktif
                                    </span>
                                @endif

                                @if($period)
                                    <span class="ms-2">
                                        • Minggu #{{ $period->week_no ?? '-' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kanan: tombol cepat --}}
                <div class="col-lg-4 col-md-5 col-12 text-md-end text-start">
                    <div class="d-flex flex-wrap gap-2 justify-content-md-end justify-content-start">
                        @if($u && $u->hasRole('guru'))
                            <a href="{{ route('year.index') }}" class="btn btn-sm btn-outline-primary">
                                <i class="mdi mdi-calendar-cog-outline me-1"></i> Tahun Ajaran
                            </a>
                        @endif

                        @if($u && $u->hasRole('bendahara'))
                            <a href="{{ route('cash.index') }}" class="btn btn-sm btn-primary">
                                <i class="mdi mdi-cash-multiple me-1"></i> Input Kas
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== ROW: RINGKASAN UMUM (SEMUA ROLE) ========== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card mini-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="mini-label">Saldo Kas</div>
                        <div class="mini-value">
                            Rp {{ number_format($saldoKas, 0, ',', '.') }}
                        </div>
                    </div>
                    <span class="mini-icon bg-primary-subtle text-primary">
                        <i class="mdi mdi-wallet-outline"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mini-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="mini-label">Kas Masuk (bulan ini)</div>
                        <div class="mini-value">
                            Rp {{ number_format($bulanIniMasuk, 0, ',', '.') }}
                        </div>
                    </div>
                    <span class="mini-icon bg-success-subtle text-success">
                        <i class="mdi mdi-arrow-down-bold-circle-outline"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mini-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="mini-label">Kas Keluar (bulan ini)</div>
                        <div class="mini-value">
                            Rp {{ number_format($bulanIniKeluar, 0, ',', '.') }}
                        </div>
                    </div>
                    <span class="mini-icon bg-danger-subtle text-danger">
                        <i class="mdi mdi-arrow-up-bold-circle-outline"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== GRAFIK (Chart.js) - GURU & BENDAHARA ========== --}}
    @if($u && ($u->hasRole('guru') || $u->hasRole('bendahara')))
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="mdi mdi-chart-line-variant me-1"></i>
                    Grafik Kas 6 Bulan Terakhir
                </span>
                <span class="small text-muted">
                    Ringkasan total kas masuk & keluar per bulan
                </span>
            </div>
            <div class="card-body">
                @if(empty($chartLabels))
                    <p class="text-muted mb-0 small">
                        Belum ada data kas untuk digrafikkan.
                    </p>
                @else
                    <div style="max-width: 100%; min-height: 220px;">
                        <canvas id="cashTrendChart" height="120"></canvas>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        Grafik ini membantu guru & bendahara memantau tren kas kelas per bulan.
                    </p>
                @endif
            </div>
        </div>
    @endif

    {{-- ===================================================== --}}
    {{-- BENDAHARA PANEL                                       --}}
    {{-- ===================================================== --}}
    @if($u && $u->hasRole('bendahara'))
        @php
            $periodeLabel = $period
                ? \Carbon\Carbon::parse($period->date_start)->translatedFormat('d M Y') . ' – ' .
                  \Carbon\Carbon::parse($period->date_end)->translatedFormat('d M Y')
                : '—';

            $isOpen = $period && $period->status === 'open';
        @endphp

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <i class="mdi mdi-cash-register me-1"></i>
                            Ringkasan Minggu Berjalan (Bendahara)
                        </span>
                        @if($period)
                            <span class="badge rounded-pill {{ $isOpen ? 'bg-success' : 'bg-secondary' }}">
                                {{ strtoupper($period->status) }} · #{{ $period->week_no }}
                            </span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if(!$classYear)
                            <p class="text-muted mb-0">
                                Belum ada tahun ajaran aktif. Minta guru mengaktifkan tahun ajaran terlebih dahulu.
                            </p>
                        @elseif(!$period)
                            <p class="text-muted mb-0">
                                Belum ada periode kas yang <strong>OPEN</strong>. Minta guru/bendahara membuka periode di menu
                                <em>Periode / Minggu Kas</em>.
                            </p>
                        @else
                            <div class="row g-3 mb-3">
                                <div class="col-sm-4">
                                    <div class="border rounded-3 p-2 bg-light">
                                        <div class="small text-muted">Minggu ke</div>
                                        <div class="fw-semibold fs-6">#{{ $period->week_no }}</div>
                                        <div class="text-muted small">
                                            {{ $periodeLabel }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="border rounded-3 p-2 bg-light">
                                        <div class="small text-muted">Nominal / minggu</div>
                                        <div class="fw-semibold clamp-number">
                                            @if($nominal > 0)
                                                Rp {{ number_format($nominal, 0, ',', '.') }}
                                            @else
                                                <span class="text-danger">Belum diset</span>
                                            @endif
                                        </div>
                                        @if($nominal <= 0)
                                            <div class="small text-muted">
                                                Atur di menu Tahun Ajaran.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="border rounded-3 p-2 bg-light">
                                        <div class="small text-muted">Status kelas</div>
                                        <div class="fw-semibold">
                                            {{ $jumlahBelumBayar }} siswa belum bayar
                                        </div>
                                        <div class="small text-muted">
                                            {{ $pendingCount }} pengeluaran pending
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('cash.index', ['only' => 'belum']) }}" class="btn btn-sm btn-primary">
                                    <i class="mdi mdi-cash-plus me-1"></i> Catat pembayaran
                                </a>
                                <a href="{{ route('period.index') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="mdi mdi-calendar-week-outline me-1"></i> Kelola periode
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Kartu kecil: jumlah belum bayar + pending --}}
            <div class="col-lg-4">
                <div class="card mb-3 h-100">
                    <div class="card-header fw-semibold">
                        <i class="mdi mdi-information-outline me-1"></i>
                        Angka Singkat
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Siswa belum bayar</span>
                            <span class="badge bg-warning text-dark">
                                {{ $jumlahBelumBayar }} siswa
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Pengeluaran pending</span>
                            <span class="badge bg-info text-dark">
                                {{ $pendingCount }} request
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel siswa belum bayar --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="mdi mdi-account-alert-outline me-1"></i>
                    Siswa Belum Bayar Minggu Ini
                </span>
                <span class="small text-muted">
                    Total: {{ $jumlahBelumBayar }} siswa
                </span>
            </div>
            <div class="card-body p-0">
                @if($jumlahBelumBayar === 0)
                    <p class="text-muted text-center my-3">
                        Semua siswa sudah membayar kas minggu ini.
                    </p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama</th>
                                    <th class="text-center" style="width:120px;">Minggu ke</th>
                                    <th class="text-end" style="width:180px;">Kurang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($siswaBelumBayar as $row)
                                    @php
                                        $nama     = $row['nama'] ?? '-';
                                        $kurang   = (int)($row['nominal'] ?? 0);
                                        $mingguKe = $row['minggu_ke'] ?? $mingguBerjalan;
                                    @endphp
                                    <tr>
                                        <td class="text-truncate">{{ $nama }}</td>
                                        <td class="text-center">{{ $mingguKe }}</td>
                                        <td class="text-end">
                                            Rp {{ number_format($kurang, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ===================================================== --}}
    {{-- SISWA PANEL                                           --}}
    {{-- ===================================================== --}}
    @if($u && $u->hasRole('siswa'))
        @php
            $mingguSiswa        = $mingguBerjalan;
            $setorMingguIni     = (int)$riwayatBayarSaya->where('minggu_ke', $mingguSiswa)->sum('jumlah');
            $statusMingguIni    = ($nominal > 0 && $setorMingguIni >= $nominal) ? 'LUNAS' : 'BELUM';
            $progressPct        = ($nominal > 0)
                                  ? round(100 * min(1, $setorMingguIni / max(1, $nominal)))
                                  : 0;
            $sisaMingguIni      = max(0, $nominal - $setorMingguIni);

            $totalSetor         = (int)$riwayatBayarSaya->sum('jumlah');   // 10 transaksi terakhir
            $countTransaksi     = $riwayatBayarSaya->count();

            $totalMasukKelas    = (int)($transparansiKelas['total_masuk']  ?? 0);
            $totalKeluarKelas   = (int)($transparansiKelas['total_keluar'] ?? 0);
            $saldoSisaKelas     = (int)($transparansiKelas['saldo_sisa']   ?? 0);

            $periodeLabelSiswa = $period
                ? \Carbon\Carbon::parse($period->date_start)->translatedFormat('d M Y') . ' – ' .
                  \Carbon\Carbon::parse($period->date_end)->translatedFormat('d M Y')
                : '—';
        @endphp

        <div class="card mb-4">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span>
                    <i class="mdi mdi-cash-multiple me-1"></i>
                    Status Kas Saya (Siswa)
                </span>
            </div>
            <div class="card-body">
                @if(!$classYear || !$period)
                    <p class="text-muted mb-0">
                        Data kas belum tersedia. Tunggu sampai wali kelas mengaktifkan tahun ajaran dan periode kas.
                    </p>
                @else
                    <div class="row g-3">
                        {{-- Kartu progress minggu ini --}}
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <div class="small text-muted">Minggu ke</div>
                                        <div class="fw-semibold fs-6">
                                            #{{ $mingguSiswa ?? '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ $periodeLabelSiswa }}
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="small text-muted mb-1">Status</div>
                                        <span class="badge rounded-pill {{ $statusMingguIni === 'LUNAS' ? 'bg-success' : 'bg-warning text-dark' }}">
                                            {{ $statusMingguIni }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>Progress minggu ini</span>
                                        <span>{{ $progressPct }}%</span>
                                    </div>
                                    <div class="progress" style="height:8px;">
                                        <div class="progress-bar {{ $statusMingguIni === 'LUNAS' ? 'bg-success' : '' }}"
                                             role="progressbar"
                                             style="width: {{ $progressPct }}%;"
                                             aria-valuenow="{{ $progressPct }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                        </div>
                                    </div>

                                    <ul class="list-unstyled small mt-2 mb-0">
                                        <li>Nominal / minggu: <strong>Rp {{ number_format($nominal,0,',','.') }}</strong></li>
                                        <li>Disetor minggu ini: <strong>Rp {{ number_format($setorMingguIni,0,',','.') }}</strong></li>
                                        <li>
                                            Sisa:
                                            <strong>
                                                @if($statusMingguIni === 'LUNAS')
                                                    Lunas
                                                @else
                                                    Rp {{ number_format($sisaMingguIni,0,',','.') }}
                                                @endif
                                            </strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- Transparansi kelas + ringkasan tunggakan --}}
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100 d-flex flex-column gap-3">
                                <div>
                                    <div class="small text-muted mb-1">Transparansi kas kelas</div>
                                    <ul class="list-unstyled mb-2">
                                        <li class="d-flex justify-content-between">
                                            <span>Total masuk</span>
                                            <span class="fw-semibold">
                                                Rp {{ number_format($totalMasukKelas,0,',','.') }}
                                            </span>
                                        </li>
                                        <li class="d-flex justify-content-between">
                                            <span>Total keluar</span>
                                            <span class="fw-semibold">
                                                Rp {{ number_format($totalKeluarKelas,0,',','.') }}
                                            </span>
                                        </li>
                                        <li class="d-flex justify-content-between">
                                            <span>Saldo sisa</span>
                                            <span class="fw-semibold">
                                                Rp {{ number_format($saldoSisaKelas,0,',','.') }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>

                                {{-- RINGKASAN CICILAN / TUNGGAKAN --}}
                                <div class="border-top pt-2">
                                    <div class="small text-muted mb-1">
                                        Ringkasan kewajiban & cicilan kamu
                                    </div>
                                    @if($nominal <= 0 || $studentKewajibanTotal === 0)
                                        <p class="small text-muted mb-0">
                                            Kewajiban total belum bisa dihitung karena nominal/minggu belum diatur
                                            atau periode kas belum berjalan penuh.
                                        </p>
                                    @else
                                        <ul class="list-unstyled small mb-2">
                                            <li class="d-flex justify-content-between">
                                                <span>Kewajiban s/d minggu ini</span>
                                                <span class="fw-semibold">
                                                    Rp {{ number_format($studentKewajibanTotal,0,',','.') }}
                                                </span>
                                            </li>
                                            <li class="d-flex justify-content-between">
                                                <span>Total setor semua minggu</span>
                                                <span class="fw-semibold">
                                                    Rp {{ number_format($studentTotalSetorAll,0,',','.') }}
                                                </span>
                                            </li>
                                            <li class="d-flex justify-content-between">
                                                <span>Sisa tunggakan</span>
                                                <span class="fw-semibold {{ $studentTunggakanTotal > 0 ? 'text-danger' : 'text-success' }}">
                                                    @if($studentTunggakanTotal > 0)
                                                        Rp {{ number_format($studentTunggakanTotal,0,',','.') }}
                                                    @else
                                                        Tidak ada tunggakan
                                                    @endif
                                                </span>
                                            </li>
                                        </ul>
                                        <p class="small text-muted mb-0">
                                            Angka ini sudah otomatis menghitung pembayaran cicilan dari minggu-minggu sebelumnya.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Riwayat setoran saya --}}
                    <hr class="my-3">
                    <h6 class="fw-semibold mb-2">
                        <i class="mdi mdi-receipt-text-outline me-1"></i>
                        Riwayat Setoran Terakhir
                    </h6>

                    @if($countTransaksi === 0)
                        <p class="text-muted mb-0 small">
                            Belum ada pembayaran tercatat atas nama kamu.
                        </p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th class="text-center" style="width:110px;">Minggu ke</th>
                                        <th class="text-end" style="width:160px;">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($riwayatBayarSaya as $row)
                                        @php
                                            $tgl = \Carbon\Carbon::parse($row['tanggal'] ?? now())->translatedFormat('d M Y');
                                            $mg  = $row['minggu_ke'] ?? '-';
                                            $jm  = (int)($row['jumlah'] ?? 0);
                                        @endphp
                                        <tr>
                                            <td>{{ $tgl }}</td>
                                            <td class="text-center">{{ $mg }}</td>
                                            <td class="text-end">
                                                Rp {{ number_format($jm, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    {{-- ===================================================== --}}
    {{-- GURU PANEL                                            --}}
    {{-- ===================================================== --}}
    @if($u && $u->hasRole('guru'))
        @php $pendingCount = $pendingPengeluaran->count(); @endphp

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="mdi mdi-chart-box-outline me-1"></i>
                    Ringkasan Kelas (Guru)
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted">Saldo kas saat ini</div>
                            <div class="fw-semibold fs-5">
                                Rp {{ number_format($saldoKas, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted">Pengeluaran pending</div>
                            <div class="fw-semibold fs-5">{{ $pendingCount }} request</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted">Laporan bulan ini</div>
                            <div class="d-flex justify-content-between small">
                                <span>Masuk</span>
                                <span class="fw-semibold">
                                    Rp {{ number_format($bulanIniMasuk, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Keluar</span>
                                <span class="fw-semibold">
                                    Rp {{ number_format($bulanIniKeluar, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-semibold mb-2">
                    <i class="mdi mdi-clipboard-text-outline me-1"></i>
                    Pengeluaran Menunggu Persetujuan
                </h6>

                @if($pendingCount === 0)
                    <p class="text-muted mb-0 small">
                        Tidak ada pengajuan pengeluaran yang pending.
                    </p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Deskripsi</th>
                                    <th class="text-end" style="width:160px;">Nominal</th>
                                    <th class="text-center" style="width:120px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingPengeluaran as $p)
                                    <tr>
                                        <td class="text-truncate">{{ $p->deskripsi ?? '-' }}</td>
                                        <td class="text-end">
                                            Rp {{ number_format((int)($p->nominal ?? 0), 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark">
                                                {{ strtoupper($p->status ?? 'pending') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>

{{-- CSS KECIL KHUSUS HALAMAN --}}
<style>
    .mini-card {
        border-radius: .9rem;
        border: 1px solid #e5e7eb;
    }
    .mini-label {
        font-size: .8rem;
        color: #6b7280;
    }
    .mini-value {
        font-size: 1.2rem;
        font-weight: 700;
    }
    .mini-icon {
        width: 40px;
        height: 40px;
        border-radius: .9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .clamp-number {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

{{-- SCRIPT GRAFIK Chart.js --}}
@if($u && ($u->hasRole('guru') || $u->hasRole('bendahara')) && !empty($chartLabels))
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('cashTrendChart');
            if (!ctx) return;

            const labels = @json($chartLabels);
            const dataMasuk = @json($chartMasuk);
            const dataKeluar = @json($chartKeluar);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Kas Masuk',
                            data: dataMasuk,
                            backgroundColor: 'rgba(34,197,94,0.6)',
                            borderRadius: 8,
                        },
                        {
                            label: 'Kas Keluar',
                            data: dataKeluar,
                            backgroundColor: 'rgba(239,68,68,0.6)',
                            borderRadius: 8,
                        },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    // format ke "Rp X.xxx"
                                    const n = (value || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                    return 'Rp ' + n;
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                        },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    const val = ctx.parsed.y || 0;
                                    const n = val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                                    return ctx.dataset.label + ': Rp ' + n;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endif
@endsection
