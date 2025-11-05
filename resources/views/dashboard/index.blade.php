@extends('layouts.app')

@section('content')
<div class="container mt-4">

    {{-- ========= HERO MINI ========= --}}
    <div class="hero-soft mb-4 p-3 p-md-4 rounded-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h3 class="mb-1 fw-bold">Selamat datang, {{ optional($u)->name }} 👋</h3>
                <p class="mb-0 text-muted" style="font-size:.9rem;">
                    Terakhir login: {{ now()->format('d M Y, H:i') }} WIB
                </p>
            </div>
            <div class="text-end">
                <a href="{{ route('year.index') }}" class="btn btn-soft-primary rounded-pill px-3">
                    Kelola Tahun Ajaran
                </a>
            </div>
        </div>
    </div>

    {{-- ========= INFORMASI AKUN ========= --}}
    <div class="card-soft mb-4">
        <div class="card-soft-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h5 class="mb-0">Informasi Akun</h5>
                <div>
                    @if($u && ($u->active ?? false))
                        <span class="badge badge-soft-success">Aktif</span>
                    @else
                        <span class="badge badge-soft-danger">Nonaktif</span>
                    @endif
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><span class="text-muted">Nama</span> <div class="fw-semibold">{{ optional($u)->name }}</div></li>
                        <li class="mb-2"><span class="text-muted">Email</span> <div class="fw-semibold">{{ optional($u)->email }}</div></li>
                        <li><span class="text-muted">Jenis Kelamin</span> <div class="fw-semibold">{{ $u->gender ?? '-' }}</div></li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div>
                            <div class="text-muted small mb-1">Role (Spatie)</div>
                            <span class="badge badge-soft-success">
                                {{ $u ? $u->getRoleNames()->implode(', ') : '-' }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('year.index') }}" class="btn btn-outline-primary rounded-pill btn-sm">
                            Kelola Tahun Ajaran
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== DASHBOARD UNTUK GURU ===================== --}}
    @if($u && $u->hasRole('guru'))
        <div class="mb-4">
            <h5 class="mb-3">📊 Ringkasan Kelas (Guru / Wali Kelas)</h5>

            {{-- Kartu statistik --}}
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="stat-card h-100">
                        <div class="stat-icon">
                            <i class="mdi mdi-cash-multiple"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-label">Saldo Kas Saat Ini</div>
                            <div class="stat-value">Rp {{ number_format($saldoKas, 0, ',', '.') }}</div>
                            <div class="stat-hint">Update minggu ke-{{ $transparansiKelas['minggu_berjalan'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card h-100">
                        <div class="stat-icon">
                            <i class="mdi mdi-timer-sand"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-label">Pengeluaran Pending</div>
                            <div class="stat-value">{{ $pendingPengeluaran->count() }} request</div>
                            <div class="stat-hint">Menunggu persetujuan Anda</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card h-100">
                        <div class="stat-icon">
                            <i class="mdi mdi-chart-line"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-label">Laporan Bulan Ini</div>
                            <div class="d-flex flex-wrap gap-4 align-items-center">
                                <div>
                                    <div class="fw-semibold">Masuk</div>
                                    <div class="stat-mini">Rp {{ number_format($bulanIniMasuk, 0, ',', '.') }}</div>
                                </div>
                                <div>
                                    <div class="fw-semibold">Keluar</div>
                                    <div class="stat-mini">Rp {{ number_format($bulanIniKeluar, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <a href="#" class="btn btn-soft-secondary btn-sm rounded-pill mt-2">
                                <i class="mdi mdi-magnify me-1"></i> Lihat Laporan Lengkap
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabel detail pengeluaran pending --}}
            <div class="card-soft mt-4">
                <div class="card-soft-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h6 class="mb-0">Butuh Persetujuan Anda</h6>
                        <span class="badge badge-soft-warning">Pending: {{ $pendingPengeluaran->count() }}</span>
                    </div>

                    @if($pendingPengeluaran->count())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle table-soft mb-0">
                                <thead>
                                    <tr>
                                        <th>Deskripsi</th>
                                        <th class="text-end">Nominal</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingPengeluaran as $p)
                                        <tr>
                                            <td>{{ $p['deskripsi'] }}</td>
                                            <td class="text-end">Rp {{ number_format($p['nominal'], 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-soft-warning">{{ $p['status'] }}</span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group-soft">
                                                    <button class="btn btn-success btn-sm rounded-pill">
                                                        <i class="mdi mdi-check-circle-outline me-1"></i> Setujui
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm rounded-pill">
                                                        <i class="mdi mdi-close-circle-outline me-1"></i> Tolak
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0 small">Tidak ada request pengeluaran.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ===================== DASHBOARD UNTUK BENDAHARA ===================== --}}
    @if($u && $u->hasRole('bendahara'))
        <div class="mb-4">
            <h5 class="mb-3">💼 Panel Bendahara</h5>

            {{-- Kartu statistik --}}
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="stat-card h-100">
                        <div class="stat-icon">
                            <i class="mdi mdi-wallet"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-label">Saldo Aktif</div>
                            <div class="stat-value">Rp {{ number_format($saldoKas, 0, ',', '.') }}</div>
                            <div class="stat-hint">Uang kas yang masih tersedia</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card h-100">
                        <div class="stat-icon">
                            <i class="mdi mdi-alert-circle-outline"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-label">Siswa Belum Bayar</div>
                            <div class="stat-value">{{ $siswaBelumBayar->count() }} siswa</div>
                            <div class="stat-hint">Minggu ke-{{ $transparansiKelas['minggu_berjalan'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card h-100">
                        <div class="stat-icon">
                            <i class="mdi mdi-check-decagram-outline"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-label">Request ACC</div>
                            <div class="stat-value">{{ $pendingPengeluaran->count() }} pending</div>
                            <div class="stat-hint">Menunggu persetujuan guru</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabel siswa belum bayar --}}
            <div class="card-soft mt-4">
                <div class="card-soft-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h6 class="mb-0">Siswa Belum Lunas Minggu Ini</h6>
                        <span class="badge badge-soft-danger">{{ $siswaBelumBayar->count() }} siswa</span>
                    </div>

                    @if($siswaBelumBayar->count())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle table-soft mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th class="text-center">Minggu Ke-</th>
                                        <th class="text-end">Kurang</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($siswaBelumBayar as $t)
                                        <tr>
                                            <td>{{ $t['nama'] }}</td>
                                            <td class="text-center">{{ $t['minggu_ke'] }}</td>
                                            <td class="text-end">Rp {{ number_format($t['nominal'], 0, ',', '.') }}</td>
                                            <td class="text-end">
                                                <div class="btn-group-soft">
                                                    <button class="btn btn-outline-primary btn-sm rounded-pill">
                                                        <i class="mdi mdi-bell-ring-outline me-1"></i> Tagih
                                                    </button>
                                                    <button class="btn btn-soft-success btn-sm rounded-pill">
                                                        <i class="mdi mdi-check-circle-outline me-1"></i> Tandai Lunas
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0 small">Semua siswa sudah bayar 🎉</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ===================== DASHBOARD UNTUK SISWA ===================== --}}
    @if($u && $u->hasRole('siswa'))
        <div class="mb-4">
            <h5 class="mb-3">📒 Ringkasan Kas Saya</h5>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="stat-card h-100">
                        <div class="stat-icon">
                            <i class="mdi mdi-cash-multiple"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-label">Total yang Sudah Disetor</div>
                            @php $totalSetor = $riwayatBayarSaya->sum('jumlah'); @endphp
                            <div class="stat-value">Rp {{ number_format($totalSetor, 0, ',', '.') }}</div>
                            <div class="stat-hint">Sampai minggu ke-{{ $transparansiKelas['minggu_berjalan'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card-soft h-100">
                        <div class="card-soft-body">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <div class="text-muted small">Transparansi Kelas</div>
                                <span class="badge badge-soft-primary">Rekap Singkat</span>
                            </div>
                            <div class="d-flex flex-wrap gap-4">
                                <div>
                                    <div class="text-muted small">Total Masuk</div>
                                    <div class="fw-bold fs-6">Rp {{ number_format($transparansiKelas['total_masuk'], 0, ',', '.') }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small">Total Keluar</div>
                                    <div class="fw-bold fs-6">Rp {{ number_format($transparansiKelas['total_keluar'], 0, ',', '.') }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small">Saldo Sisa</div>
                                    <div class="fw-bold fs-6">Rp {{ number_format($transparansiKelas['saldo_sisa'], 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <p class="text-muted mt-2 mb-0 small">
                                Kamu dan teman-teman bisa lihat ke mana uang kas dipakai. Transparan 👍
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Riwayat bayar saya --}}
            <div class="card-soft mt-4">
                <div class="card-soft-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h6 class="mb-0">Riwayat Setoran Saya</h6>
                        <span class="badge badge-soft-secondary">{{ $riwayatBayarSaya->count() }} transaksi</span>
                    </div>

                    @if($riwayatBayarSaya->count())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle table-soft mb-0">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th class="text-center">Minggu Ke-</th>
                                        <th class="text-end">Jumlah</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($riwayatBayarSaya as $r)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($r['tanggal'])->format('d M Y') }}</td>
                                            <td class="text-center">{{ $r['minggu_ke'] }}</td>
                                            <td class="text-end">Rp {{ number_format($r['jumlah'], 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-success">{{ $r['status'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0 small">Belum ada pembayaran tercatat.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

</div>

{{-- ============== STYLE UPGRADE (SOFT UI) ============== --}}
<style>
    :root{
        --soft-primary: #2563eb;
        --soft-bg: #f8fafc;
        --soft-border: #e8edf4;
        --soft-text: #667085;
        --soft-success: #22c55e;
        --soft-warning: #f59e0b;
        --soft-danger: #ef4444;
    }

    .hero-soft{
        background: linear-gradient(135deg, rgba(37,99,235,.08), rgba(37,99,235,.03));
        border:1px solid var(--soft-border);
    }
    .card-soft{ background:#fff; border:1px solid var(--soft-border); border-radius:1rem; }
    .card-soft-body{ padding:1.25rem; }

    .badge-soft-primary{ background:rgba(37,99,235,.12); color:#1d4ed8; border:1px solid rgba(37,99,235,.25); }
    .badge-soft-success{ background:rgba(34,197,94,.12); color:#15803d; border:1px solid rgba(34,197,94,.25); }
    .badge-soft-secondary{ background:#f2f4f7; color:#475467; border:1px solid #e7eaee; }
    .badge-soft-warning{ background:rgba(245,158,11,.14); color:#b45309; border:1px solid rgba(245,158,11,.25); }
    .badge-soft-danger{ background:rgba(239,68,68,.12); color:#b91c1c; border:1px solid rgba(239,68,68,.25); }

    .btn-soft-primary{
        background:rgba(37,99,235,.08); color:var(--soft-primary); border:1px solid rgba(37,99,235,.25);
    }
    .btn-soft-primary:hover{ background:var(--soft-primary); color:#fff; }
    .btn-soft-success{
        background:rgba(34,197,94,.08); color:#15803d; border:1px solid rgba(34,197,94,.25);
    }
    .btn-soft-success:hover{ background:#16a34a; color:#fff; }
    .btn-soft-secondary{
        background:#f3f5f8; color:#374151; border:1px solid #e5e9f0;
    }
    .btn-soft-secondary:hover{ background:#e8ecf3; }

    .table-soft thead{ background:#f7f9fc; }
    .table-soft thead th{ color:#64748b; font-weight:600; border-bottom:1px solid var(--soft-border) !important; }
    .table-soft tbody td{ border-top:1px solid var(--soft-border); }

    .btn-group-soft .btn{ margin-left:.25rem; }
    .btn-group-soft .btn:first-child{ margin-left:0; }

    /* ===== Stat Cards ===== */
    .stat-card{
        display:flex; gap:12px; padding:16px; border:1px solid var(--soft-border);
        border-radius:1rem; background:#fff; box-shadow: 0 6px 20px rgba(2,6,23,.03);
    }
    .stat-icon{
        width:42px; height:42px; border-radius:12px; display:grid; place-items:center;
        flex:0 0 auto; color:#fff; background:var(--soft-primary);
        box-shadow:0 10px 18px rgba(37,99,235,.15);
    }
    .stat-icon i{
        font-size:22px; line-height:1; color:#fff;
    }

    .stat-body{ flex:1; }
    .stat-label{ font-size:.8rem; color:var(--soft-text); margin-bottom:2px; }
    .stat-value{ font-size:1.35rem; font-weight:700; line-height:1.2; }
    .stat-hint{ color:var(--soft-text); font-size:.8rem; margin-top:2px; }
    .stat-mini{ font-size:1rem; color:#111827; }

    /* Inputs hover/focus */
    .form-control:focus, .form-select:focus, .btn:focus{
        box-shadow:0 0 0 .2rem rgba(37,99,235,.15);
        border-color:var(--soft-primary);
    }

    @media (max-width:576px){
        .card-soft-body{ padding:1rem; }
        .stat-value{ font-size:1.2rem; }
    }
</style>
@endsection
