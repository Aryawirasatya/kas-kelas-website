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
                            <th class="text-end">Aksi</th>

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
                                <td class="text-end">
                                    @if (in_array($role, ['guru', 'bendahara']))
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary btn-arrears-detail"
                                            data-period-id="{{ $p['id'] ?? '' }}"
                                            data-period-label="{{ $p['label'] ?? '' }}"
                                        >
                                            Detail Tunggakan
                                        </button>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">
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
{{-- MODAL DETAIL TUNGGAKAN --}}
{{-- MODAL DETAIL TUNGGAKAN (Modern) --}}
<div class="modal fade" id="arrearsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content arrears-modal">

      {{-- Header --}}
      <div class="modal-header border-0 pb-0">
        <div class="w-100">
          <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
              <div class="text-muted small mb-1">Detail Tunggakan</div>
              <h5 class="modal-title fw-semibold mb-0">
                <span id="arrearsModalTitle">Periode</span>
              </h5>
              <div class="text-muted small mt-1" id="arrearsModalSubTitle">
                Menampilkan siswa yang belum lunas pada periode ini.
              </div>
            </div>

            <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>

          {{-- Meta info --}}
          <div class="d-flex flex-wrap gap-2 mt-3">
            <span class="badge rounded-pill text-bg-light border">
              Nominal/minggu: <span class="fw-semibold">Rp <span id="arrearsNominal">0</span></span>
            </span>
            <span class="badge rounded-pill text-bg-danger">
              Total nunggak: <span class="fw-semibold" id="arrearsCount">0</span> siswa
            </span>
            <span class="badge rounded-pill text-bg-secondary">
              Filter: periode ini saja
            </span>
          </div>
        </div>
      </div>

      {{-- Body --}}
      <div class="modal-body pt-3">

        {{-- Controls --}}
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="input-group input-group-sm arrears-search">
              <span class="input-group-text bg-white border-end-0">
                <i class="mdi mdi-magnify"></i>
              </span>
              <input type="text" class="form-control border-start-0"
                     id="arrearsSearchInput"
                     placeholder="Cari nama / NIS / NISN...">
            </div>

            <select class="form-select form-select-sm w-auto" id="arrearsSortSelect">
              <option value="weeks_desc" selected>Urutkan: Nunggak terbanyak</option>
              <option value="weeks_asc">Urutkan: Nunggak tersedikit</option>
              <option value="name_asc">Urutkan: Nama A-Z</option>
              <option value="name_desc">Urutkan: Nama Z-A</option>
            </select>
          </div>

          <div class="small text-muted">
            Klik baris untuk lihat detail minggu nunggak.
          </div>
        </div>

        {{-- Loading --}}
        <div id="arrearsModalLoading" class="arrears-loading">
          <div class="skeleton-line w-50"></div>
          <div class="skeleton-line w-75"></div>
          <div class="skeleton-table mt-3">
            <div class="skeleton-row"></div>
            <div class="skeleton-row"></div>
            <div class="skeleton-row"></div>
          </div>
        </div>

        {{-- Empty --}}
        <div id="arrearsModalEmpty" class="text-muted text-center py-5" style="display:none;">
          <div class="fs-1 mb-2">🎉</div>
          <div class="fw-semibold">Tidak ada tunggakan</div>
          <div class="small">Semua siswa lunas pada periode ini.</div>
        </div>

        {{-- Content --}}
        <div id="arrearsModalContent" style="display:none;">
          <div class="table-responsive">
            <table class="table table-sm align-middle arrears-table mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:28px;"></th>
                  <th>Nama</th>
                  <th style="width:120px;">NIS</th>
                  <th style="width:140px;">NISN</th>
                  <th class="text-end" style="width:140px;">Bayar</th>
                  <th class="text-end" style="width:140px;">Kurang</th>
                  <th class="text-center" style="width:120px;">Nunggak</th>
                  <th style="width:220px;">Alasan (Periode ini)</th>
                </tr>
              </thead>
              <tbody id="arrearsTableBody"></tbody>
            </table>
          </div>

          <div class="small text-muted mt-3">
            * “Nunggak” dihitung dari minggu 1 sampai minggu periode ini.
          </div>
        </div>
      </div>

      {{-- Footer --}}
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">
          Tutup
        </button>
      </div>

    </div>
  </div>
</div>


</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('arrearsModal');
  if (!modalEl) return;

  // pastikan bootstrap modal tersedia
  if (typeof bootstrap === 'undefined') {
    console.error('Bootstrap JS belum ter-load. Modal tidak bisa jalan.');
    return;
  }

  const modal = new bootstrap.Modal(modalEl);

  const loadingEl = document.getElementById('arrearsModalLoading');
  const contentEl = document.getElementById('arrearsModalContent');
  const emptyEl   = document.getElementById('arrearsModalEmpty');

  const titleEl   = document.getElementById('arrearsModalTitle');
  const nominalEl = document.getElementById('arrearsNominal');
  const countEl   = document.getElementById('arrearsCount');
  const tbodyEl   = document.getElementById('arrearsTableBody');

  const searchInput = document.getElementById('arrearsSearchInput');
  const sortSelect  = document.getElementById('arrearsSortSelect');

  let cachedRows = [];

  function rupiah(n) {
    n = parseInt(n || 0);
    return n.toLocaleString('id-ID');
  }

  function escapeHtml(str) {
    return (str ?? '').toString()
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function renderTable(rows) {
    tbodyEl.innerHTML = '';

    if (!rows || rows.length === 0) {
      contentEl.style.display = 'none';
      emptyEl.style.display = 'block';
      return;
    }

    rows.forEach((row, idx) => {
      const unpaidWeeks = row.unpaid_weeks || [];

      const detailHtml = unpaidWeeks.map(w => {
        return `
          <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
            <span class="arrears-chip">
              Minggu <strong>${escapeHtml(w.week_no)}</strong>
            </span>
            <span class="arrears-chip arrears-badge-danger">
              Kurang Rp ${rupiah(w.arrears)}
            </span>
            <span class="arrears-chip">
              Bayar Rp ${rupiah(w.paid)}
            </span>
          </div>
          <div class="small text-muted mb-2">
            Alasan: ${w.reason ? escapeHtml(w.reason) : '-'}
          </div>
        `;
      }).join('');

      const tr = document.createElement('tr');
      tr.classList.add('arrears-row');
      tr.dataset.idx = idx;

      tr.innerHTML = `
        <td class="text-muted">${idx + 1}</td>
        <td>
          <div class="fw-semibold">${escapeHtml(row.name)}</div>
          <div class="text-muted small">${escapeHtml(row.email ?? '')}</div>

          <div class="arrears-detail" id="arrearsDetail-${idx}">
            <div class="card mt-2">
              <div class="card-body">
                <div class="fw-semibold mb-2">Detail Minggu Nunggak</div>
                ${detailHtml || '<div class="text-muted small">Tidak ada detail.</div>'}
              </div>
            </div>
          </div>
        </td>
        <td>${escapeHtml(row.nis ?? '-')}</td>
        <td>${escapeHtml(row.nisn ?? '-')}</td>
        <td class="text-end">Rp ${rupiah(row.paid)}</td>
        <td class="text-end text-danger fw-semibold">Rp ${rupiah(row.arrears)}</td>
        <td class="text-center">
          <span class="arrears-chip arrears-badge-danger">
            ${escapeHtml(row.unpaid_weeks_count)}
          </span>
        </td>
        <td class="arrears-reason">
          ${row.reason ? escapeHtml(row.reason) : '<small>-</small>'}
        </td>
      `;

      // klik baris untuk toggle detail
      tr.addEventListener('click', () => {
        const detail = document.getElementById(`arrearsDetail-${idx}`);
        if (!detail) return;

        // close semua dulu biar rapi
        document.querySelectorAll('.arrears-detail').forEach(el => {
          if (el !== detail) el.style.display = 'none';
        });

        detail.style.display = (detail.style.display === 'none' || detail.style.display === '')
          ? 'block'
          : 'none';
      });

      tbodyEl.appendChild(tr);
    });

    emptyEl.style.display = 'none';
    contentEl.style.display = 'block';
  }

  function applySearchAndSort() {
    const q = (searchInput?.value ?? '').toLowerCase().trim();
    const sort = sortSelect?.value ?? 'weeks_desc';

    let rows = [...cachedRows];

    // search
    if (q) {
      rows = rows.filter(r => {
        const name = (r.name ?? '').toLowerCase();
        const nis  = (r.nis ?? '').toString().toLowerCase();
        const nisn = (r.nisn ?? '').toString().toLowerCase();
        return name.includes(q) || nis.includes(q) || nisn.includes(q);
      });
    }

    // sort
    rows.sort((a, b) => {
      if (sort === 'weeks_desc') return (b.unpaid_weeks_count ?? 0) - (a.unpaid_weeks_count ?? 0);
      if (sort === 'weeks_asc')  return (a.unpaid_weeks_count ?? 0) - (b.unpaid_weeks_count ?? 0);
      if (sort === 'name_asc')   return (a.name ?? '').localeCompare(b.name ?? '');
      if (sort === 'name_desc')  return (b.name ?? '').localeCompare(a.name ?? '');
      return 0;
    });

    renderTable(rows);
  }

  // bind search + sort
  if (searchInput) searchInput.addEventListener('input', applySearchAndSort);
  if (sortSelect) sortSelect.addEventListener('change', applySearchAndSort);

  // tombol detail tunggakan di tabel periode
  document.querySelectorAll('.btn-arrears-detail').forEach(btn => {
    btn.addEventListener('click', async () => {
      const periodId = btn.dataset.periodId;
      const periodLabel = btn.dataset.periodLabel || ('Periode #' + periodId);

      titleEl.textContent = periodLabel;

      loadingEl.style.display = 'block';
      contentEl.style.display = 'none';
      emptyEl.style.display = 'none';
      tbodyEl.innerHTML = '';
      cachedRows = [];
      if (searchInput) searchInput.value = '';

      modal.show();

      try {
        const url = `{{ url('/reports/period') }}/${periodId}/arrears`;
        const res = await fetch(url, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const data = await res.json();

        loadingEl.style.display = 'none';

        if (!data.ok) {
          emptyEl.style.display = 'block';
          emptyEl.textContent = data.message || 'Gagal memuat data.';
          return;
        }

        nominalEl.textContent = rupiah(data.kas_nominal);
        countEl.textContent = data.count ?? 0;

        cachedRows = data.rows || [];

        applySearchAndSort();

      } catch (err) {
        loadingEl.style.display = 'none';
        emptyEl.style.display = 'block';
        emptyEl.textContent = 'Error: ' + err.message;
      }
    });
  });
});
</script>

 
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
<style>
  .arrears-modal{
    border-radius: 1.25rem;
    box-shadow: 0 24px 70px rgba(0,0,0,.35);
    overflow: hidden;
  }

  .arrears-search{
    min-width: 280px;
    max-width: 360px;
  }

  .arrears-table thead th{
    font-size: .78rem;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
  }

  .arrears-table tbody tr{
    cursor: pointer;
    transition: all .12s ease;
  }
  .arrears-table tbody tr:hover{
    background: rgba(37,99,235,.05);
  }

  .arrears-chip{
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    padding:.25rem .55rem;
    border-radius: 999px;
    font-size: .78rem;
    border: 1px solid #e5e7eb;
    background:#fff;
    color:#111827;
    white-space: nowrap;
  }

  .arrears-badge-danger{
    background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.25);
    color: #b91c1c;
  }

  .arrears-badge-ok{
    background: rgba(34,197,94,.1);
    border: 1px solid rgba(34,197,94,.25);
    color: #15803d;
  }

  .arrears-reason{
    font-size: .85rem;
    color:#111827;
  }
  .arrears-reason small{
    color:#6b7280;
  }

  /* Accordion detail */
  .arrears-detail{
    margin-top: .35rem;
    display:none;
  }
  .arrears-detail .card{
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
    overflow:hidden;
  }
  .arrears-detail .card-body{
    padding: .75rem .85rem;
    background:#fafafa;
  }

  /* Skeleton */
  .arrears-loading .skeleton-line{
    height: 12px;
    border-radius: 999px;
    background: #e5e7eb;
    margin-bottom: 10px;
    animation: pulse 1.1s infinite ease-in-out;
  }
  .arrears-loading .skeleton-table{
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: .75rem;
    background:#fff;
  }
  .arrears-loading .skeleton-row{
    height: 36px;
    border-radius: .75rem;
    background: #e5e7eb;
    margin-bottom: 10px;
    animation: pulse 1.1s infinite ease-in-out;
  }

  @keyframes pulse{
    0%{ opacity:.55 }
    50%{ opacity:1 }
    100%{ opacity:.55 }
  }
</style>
