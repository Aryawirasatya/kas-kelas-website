@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1100px;">

  {{-- Header Tahun Ajaran --}}
  <div class="rounded-4 shadow-sm p-3 p-md-4 bg-white mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="d-flex align-items-center gap-3">
        <div class="status-dot {{ $year->status }}"></div>
        <div>
          <div class="fw-semibold">
            {{ $year->class_label }} ({{ $year->level }})
          </div>
          <div class="text-muted small">
            Tahun: {{ $year->academic_year }}
            @if($year->homeroom_name)
              • Wali: {{ $year->homeroom_name }}
            @endif
          </div>
        </div>
      </div>
      <div class="text-end">
        <span class="badge rounded-pill
          {{ $year->status === 'active' ? 'bg-success' : ($year->status === 'draft' ? 'bg-warning text-dark' : 'bg-secondary') }}">
          {{ ucfirst($year->status) }}
        </span>
        <div class="mt-1 small">
          <a href="{{ route('year.index') }}" class="text-decoration-none">
            &larr; Kembali ke daftar tahun ajaran
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- Ringkasan Kas --}}
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card mini-card h-100">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="mini-label">Total Kas Masuk</div>
            <div class="mini-value">
              Rp {{ number_format($totalMasuk, 0, ',', '.') }}
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
            <div class="mini-label">Total Kas Keluar</div>
            <div class="mini-value">
              Rp {{ number_format($totalKeluar, 0, ',', '.') }}
            </div>
          </div>
          <span class="mini-icon bg-danger-subtle text-danger">
            <i class="mdi mdi-arrow-up-bold-circle-outline"></i>
          </span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card mini-card h-100">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="mini-label">Saldo Akhir</div>
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
  </div>

  {{-- Info Siswa & Bendahara --}}
  <div class="card mb-4">
    <div class="card-header fw-semibold">
      <i class="mdi mdi-account-group-outline me-1"></i>
      Ringkasan Siswa & Bendahara
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="border rounded-3 p-3 h-100">
            <div class="mini-label">Total Siswa (di tahun ini)</div>
            <div class="fw-semibold fs-5">{{ $totalSiswa }}</div>
            <div class="small text-muted">Termasuk aktif & nonaktif.</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded-3 p-3 h-100">
            <div class="mini-label">Siswa Aktif</div>
            <div class="fw-semibold fs-5">{{ $siswaAktif }}</div>
            <div class="small text-muted">
              Nonaktif: {{ $siswaNonaktif }}
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded-3 p-3 h-100">
            <div class="mini-label">Bendahara</div>
            @if($bendaharaList->isEmpty())
              <div class="text-muted small mt-1">
                Tidak tercatat.
              </div>
            @else
              <ul class="mb-0 small ps-3 mt-1">
                @foreach($bendaharaList as $b)
                  <li>{{ $b }}</li>
                @endforeach
              </ul>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Rekap Per Periode (Minggu) --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">
        <i class="mdi mdi-calendar-week-outline me-1"></i>
        Rekap Periode / Minggu Kas
      </span>
      <span class="small text-muted">
        {{ $rekapPeriode->count() }} periode
      </span>
    </div>
    <div class="card-body p-0">
      @if($rekapPeriode->isEmpty())
        <p class="text-muted text-center my-3 small mb-0">
          Belum ada periode kas tercatat untuk tahun ajaran ini.
        </p>
      @else
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:90px;">Minggu</th>
                <th style="width:220px;">Rentang Tanggal</th>
                <th class="text-end" style="width:160px;">Masuk</th>
                <th class="text-end" style="width:160px;">Keluar</th>
                <th class="text-end" style="width:160px;">Saldo (+)</th>
                <th class="text-center" style="width:100px;">Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($rekapPeriode as $p)
                @php
                  $in  = (int) $p->total_masuk;
                  $out = (int) $p->total_keluar;
                  $net = $in - $out;
                @endphp
                <tr>
                  <td>#{{ $p->week_no }}</td>
                  <td>
                    {{ \Illuminate\Support\Carbon::parse($p->date_start)->translatedFormat('d M Y') }}
                    &ndash;
                    {{ \Illuminate\Support\Carbon::parse($p->date_end)->translatedFormat('d M Y') }}
                  </td>
                  <td class="text-end">
                    Rp {{ number_format($in, 0, ',', '.') }}
                  </td>
                  <td class="text-end text-danger">
                    Rp {{ number_format($out, 0, ',', '.') }}
                  </td>
                  <td class="text-end {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($net, 0, ',', '.') }}
                  </td>
                  <td class="text-center">
                    <span class="badge rounded-pill
                      {{ $p->status === 'open'
                           ? 'bg-success-subtle text-success border-success-subtle'
                           : 'bg-secondary-subtle text-secondary border-secondary-subtle' }}">
                      {{ strtoupper($p->status) }}
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

</div>

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

  .status-dot{ width:10px; height:10px; border-radius:50%; }
  .status-dot.draft{ background:#f59e0b; }
  .status-dot.active{ background:#22c55e; }
  .status-dot.archived{ background:#9ca3af; }

  .bg-success-subtle{ background:#eaf7ee !important; }
  .bg-secondary-subtle{ background:#f2f4f7 !important; }
  .border-success-subtle{ border-color:#c7ecd3 !important; }
  .border-secondary-subtle{ border-color:#e6e9ef !important; }
</style>
@endsection
