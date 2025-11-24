@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1180px;">

  {{-- Flash --}}
  @if(session('success'))
    <div class="alert alert-success rounded-4 shadow-sm mb-3">
      <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger rounded-4 shadow-sm mb-3">
      <div class="d-flex align-items-start">
        <i class="mdi mdi-alert-octagon-outline me-2 fs-4"></i>
        <div>
          <div class="fw-semibold mb-1">Terjadi kesalahan</div>
          <ul class="mb-0 ps-3 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
      </div>
    </div>
  @endif

  {{-- Header sticky --}}
  <div class="glass card border-0 shadow-sm sticky-top top-0 mb-4" style="z-index:3;">
    <div class="card-body p-3 p-md-4">

      <div class="row align-items-center g-3">
        <div class="col-12 col-lg">
          <div class="d-flex align-items-start gap-3">
            <div
              class="avatar bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
              style="width:44px;height:44px;">
              <i class="mdi mdi-cash-multiple fs-5"></i>
            </div>

            <div class="flex-grow-1 min-w-0">
              <div class="fw-semibold text-truncate" id="header-title">
                @if($year)
                  {{ $year->class_label }} ({{ $year->level }}) · Tahun {{ $year->academic_year }}
                @else
                  — Tidak ada tahun aktif —
                @endif
              </div>

              <div class="text-muted small mt-1" id="header-subinfo">
                @php
                  $periodIsOpen = $period && ($period->status === 'open');
                @endphp

                @if($period)
                  <div class="d-inline-flex align-items-center gap-1">
                    <span>Periode</span>
                    <span class="badge rounded-pill {{ $periodIsOpen ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">
                      {{ strtoupper($period->status) }} #{{ $period->week_no }}
                    </span>
                  </div>

                  <div class="mt-1">
                    {{ \Illuminate\Support\Carbon::parse($period->date_start)->translatedFormat('d M Y') }}
                    &ndash; {{ \Illuminate\Support\Carbon::parse($period->date_end)->translatedFormat('d M Y') }}
                  </div>

                  <div class="mt-1">
                    Nominal/minggu: <strong>Rp {{ number_format($nominal,0,',','.') }}</strong>
                  </div>
                @else
                  <i class="mdi mdi-alert-circle-outline"></i>
                  Tidak ada periode OPEN. Minta Guru membuka periode.
                @endif
              </div>
            </div>
          </div>
        </div>

        {{-- Kanan: KPI 2x2 --}}
        <div class="col-12 col-lg-auto">
           <div class="kpi-grid kpi-grid-2x2" id="kpi-grid">
            <div class="kpi-chip">
              <div class="kpi-label">Siswa</div>
              <div class="kpi-value" id="kpi-siswa">{{ $rekap['total_siswa'] ?? 0 }}</div>
            </div>
            <div class="kpi-chip kpi-green">
              <div class="kpi-label">Lunas</div>
              <div class="kpi-value" id="kpi-lunas">{{ $rekap['lunas'] ?? 0 }}</div>
            </div>
            <div class="kpi-chip kpi-amber">
              <div class="kpi-label">Belum</div>
              <div class="kpi-value" id="kpi-belum">{{ $rekap['belum'] ?? 0 }}</div>
            </div>
            <div class="kpi-chip kpi-blue">
              <div class="kpi-label">Terkumpul minggu ini</div>
              <div class="kpi-value" id="kpi-terkumpul">
                Rp {{ number_format($rekap['terkumpul'] ?? 0,0,',','.') }}
              </div>
            </div>
            <div class="kpi-chip kpi-amber">
              <div class="kpi-label">Tunggakan (kelas)</div>
              <div class="kpi-value">
                Rp {{ number_format($rekap['tunggakan_total'] ?? 0,0,',','.') }}
              </div>
            </div>
           </div>

        </div>
      </div>

      @if($period)
        <div class="mt-1 small text-muted d-flex align-items-center gap-1">
          <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle">
            <i class="mdi mdi-information-outline me-1"></i>
            Saldo minggu ini bisa termasuk pembayaran dari minggu lain (auto-tunggakan)
          </span>
        </div>
      @endif


      {{-- BAR FILTER --}}
      <div class="mt-3 pt-3 border-top">
        <div class="row g-2 align-items-center">
          <div class="col-12 col-md">
            <div class="d-flex flex-wrap gap-2">
              @php $only = request('only'); @endphp
              <a href="{{ route('cash.index') }}"
                 class="btn btn-sm rounded-pill {{ $only ? 'btn-outline-primary' : 'btn-primary' }}">
                <i class="mdi mdi-format-list-bulleted me-1"></i> Semua
              </a>
              <a href="{{ route('cash.index', ['only'=>'belum']) }}"
                 class="btn btn-sm rounded-pill {{ $only==='belum' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="mdi mdi-progress-wrench me-1"></i> Belum Lunas
              </a>
              <a href="{{ route('cash.index', ['only'=>'lunas']) }}"
                 class="btn btn-sm rounded-pill {{ $only==='lunas' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="mdi mdi-check-all me-1"></i> Lunas
              </a>
            </div>
          </div>

          @if($period)
            @php
              $total = (int)($rekap['total_siswa'] ?? 0);
              $lunas = (int)($rekap['lunas'] ?? 0);
              $persen = $total > 0 ? round(100 * ($lunas / max(1, $total))) : 0;
            @endphp
            <div class="col-12 col-md-auto">
              <div class="d-flex align-items-center gap-2">
                <span class="small text-muted d-none d-md-inline">Progress minggu ini</span>
                <div class="progress flex-grow-1" style="height:10px; min-width:180px; max-width:260px;" role="progressbar"
                     aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                  <div class="progress-bar" id="header-progress-bar" style="width: {{ $persen }}%"></div>
                </div>
                <span class="small fw-semibold" id="header-progress-text">{{ $persen }}%</span>
              </div>
            </div>
          @endif
        </div>
      </div>

    </div>
  </div>

  {{-- Body --}}
  <div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
      @if($rows->isEmpty())
        {{-- Empty state --}}
        <div class="p-5 text-center text-muted">
          <div class="mb-2">
            <i class="mdi mdi-account-group-outline fs-1"></i>
          </div>
          <div class="fw-semibold mb-1">Belum ada siswa aktif</div>
          <div class="small">Tambahkan/aktifkan siswa pada menu <em>Tahun Ajaran &amp; Kelas</em>.</div>
        </div>
      @else
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="cash-table">
            <thead class="bg-body-tertiary small text-uppercase text-muted">
              <tr>
                <th class="ps-4">Siswa</th>
                <th class="text-end" style="width:150px">Kas Minggu ini</th>
                <th class="text-end" style="width:150px">Sisa</th>
                <th style="width:160px">Status</th>
                <th class="text-end pe-4" style="width:320px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @foreach($rows as $r)
                 @php
                  // --- data dasar per siswa ---
                  $enrollment = $r->enrollment;
                  $enrollId   = $enrollment->id;
                  $student    = $r->user;

                  // ID untuk masing-masing modal
                  $modalPayId    = 'payModal_'    . $enrollId;
                  $modalHisId    = 'historyModal_' . $enrollId;
                  $modalReasonId = 'reasonModal_' . $enrollId;

                  // Riwayat pembayaran minggu ini (hasil groupBy di controller)
                  $history = $paymentsByStudent[$enrollId] ?? collect();

                  // Data tunggakan dari controller (array [enrollment_id => Collection])
                  $lateList = collect($arrearsByStudent[$enrollId] ?? []);

                  // Periode yang bisa dipilih di modal "Periode Pembayaran"
                  $availablePeriods = $lateList->isNotEmpty()
                      ? \App\Models\CashPeriod::whereIn('id', $lateList->pluck('period_id')->all())
                          ->orderBy('date_start')
                          ->get()
                      : collect();

                  // ---- Ringkasan tunggakan lama (untuk tampilan) ----
                  // GANTI 'remaining' dengan field yang benar di datamu (misal 'sisa')
                  $arrearTotal = (int) $lateList->sum('sisa');
                  $arrearCount = $lateList->pluck('period_id')->unique()->count();

                  // ---- Hitung kas minggu ini (efektif vs raw) ----
                  $effectiveForCurrent = (int) $r->total;
                  $rawPaid = $rawPaidByStudent[$enrollId] ?? $effectiveForCurrent;
                  $arrearPart = max(0, $rawPaid - $effectiveForCurrent);

                  $progress = $nominal > 0
                      ? min(100, round(100 * ($effectiveForCurrent / $nominal)))
                      : 0;
                @endphp


                <tr>
                  <td class="ps-4">
                    <div class="d-flex align-items-center gap-3">
                      <div class="avatar avatar-sm bg-primary-subtle text-primary">
                        <i class="mdi mdi-account-outline"></i>
                      </div>
                      <div class="min-w-0">
                        <div class="fw-semibold text-truncate">{{ $student->name }}</div>
                        <div class="small text-muted text-truncate">{{ $student->email }}</div>
                      </div>
                    </div>
                  </td>

                  <td class="text-end">
                    {{-- Kas fisik masuk minggu ini --}}
                    <div class="fw-semibold">
                      Rp {{ number_format($rawPaid,0,',','.') }}
                    </div>

                    {{-- Breakdown: bagian untuk minggu ini + (opsional) bagian untuk tunggakan --}}
                    <div class="small text-muted mt-1">
                      <span>Untuk minggu ini: <strong>Rp {{ number_format($effectiveForCurrent,0,',','.') }}</strong></span>
                      @if($arrearPart > 0)
                        <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle ms-1">
                          + Rp {{ number_format($arrearPart,0,',','.') }} bayar tunggakan
                        </span>
                      @endif
                    </div>

                    @if($nominal>0)
                      <div class="progress mt-1" style="height:6px">
                        <div class="progress-bar {{ $progress>=100?'bg-success':'' }}" style="width: {{ $progress }}%"></div>
                      </div>
                    @endif
                  </td>


                  <td class="text-end">
                  {{-- Sisa minggu ini --}}
                  <div class="mb-1">
                    <span class="badge rounded-pill {{ $r->sisa>0 ? 'bg-warning text-dark' : 'bg-success' }}">
                      Sisa minggu ini: Rp {{ number_format($r->sisa,0,',','.') }}
                    </span>
                  </div>

                  {{-- Info tunggakan lama --}}
                  @if($arrearTotal > 0)
                    <div class="small text-muted">
                      Tunggakan lama:
                      <strong>Rp {{ number_format($arrearTotal,0,',','.') }}</strong>
                      <span class="d-inline-block ms-1">
                        ({{ $arrearCount }} minggu)
                      </span>
                    </div>
                  @else
                    <div class="small text-muted">Tidak ada tunggakan lama</div>
                  @endif
                </td>


                  <td>
                    @if($r->status === 'LUNAS')
                      <span class="badge status-badge status-green">
                        <i class="mdi mdi-check-circle-outline me-1"></i>LUNAS
                      </span>
                    @else
                      <span class="badge status-badge status-amber">
                        <i class="mdi mdi-timer-sand-empty me-1"></i>BELUM
                      </span>
                    @endif
                  </td>

                  <td class="text-end pe-4">
                    <div class="d-flex flex-wrap justify-content-end gap-2 action-cell" role="toolbar" aria-label="Aksi siswa">
                       <button class="btn btn-primary" 
                          data-bs-toggle="modal" 
                          data-bs-target="#{{ $modalPayId }}"
                          {{ (!$periodIsOpen && $lateList->isEmpty()) ? 'disabled' : '' }}>
                          <i class="mdi mdi-cash-multiple me-1"></i> Bayar
                        </button>



                      @php $hasReason = filled($r->reason ?? null); @endphp
                      @if($r->status !== 'LUNAS')
                        <button
                          class="btn {{ $hasReason ? 'btn-outline-warning' : 'btn-warning' }} btn-sm rounded-pill px-3 btn-action"
                          data-bs-toggle="modal"
                          data-bs-target="#{{ $modalReasonId }}"
                          title="{{ $hasReason ? 'Edit Alasan' : 'Isi Alasan' }}"
                        >
                          <i class="mdi mdi-comment-edit-outline me-1"></i>{{ $hasReason ? 'Edit Alasan' : 'Isi Alasan' }}
                        </button>
                      @endif

                      <button
                        class="btn btn-outline-primary btn-sm rounded-pill px-3 btn-action"
                        data-bs-toggle="modal"
                        data-bs-target="#{{ $modalHisId }}"
                        title="Lihat riwayat minggu ini"
                      >
                        <i class="mdi mdi-history me-1"></i> Riwayat
                      </button>
                    </div>
                  </td>
                </tr>

                {{-- ==== KUMPULKAN SEMUA MODAL DENGAN @push (KELUAR DARI TABEL!) ==== --}}
                @push('modals')
               {{-- MODAL: BAYAR --}}
                  <div class="modal fade" id="{{ $modalPayId }}" tabindex="-1" aria-labelledby="{{ $modalPayId }}Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
                      <form class="modal-content rounded-4 glass-modal" method="POST" action="{{ route('cash.store') }}">
                        @csrf
                        <input type="hidden" name="enrollment_id" value="{{ $enrollId }}">
                        <input type="hidden" name="period_id" id="periodId_{{ $enrollId }}" value="{{ $period?->id }}">
                        <input type="hidden" name="alloc_arrears" id="allocArrears_{{ $enrollId }}" value="0">

                        <div class="modal-header border-0 pb-0">
                          <div>
                            <div class="small text-muted">Input Pembayaran</div>
                            <h5 class="modal-title fw-semibold" id="{{ $modalPayId }}Label">{{ $student->name }}</h5>
                          </div>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body pt-3">
                          @if(!$periodIsOpen)
                            <div class="alert alert-warning rounded-3">
                              <i class="mdi mdi-alert-circle-outline me-1"></i> Periode tidak OPEN.
                            </div>
                          @else
                            {{-- RINGKASAN TAGIHAN --}}
                            @php
                              $totalDue = (int) $r->sisa + (int) $arrearTotal;
                            @endphp

                            <div class="soft card border-0 mb-3">
                              <div class="card-body p-3">
                                <div class="d-flex flex-wrap justify-content-between gap-3">
                                  <div class="d-flex align-items-start gap-3">
                                    <div class="avatar avatar-sm bg-info-subtle text-info">
                                      <i class="mdi mdi-cash"></i>
                                    </div>
                                    <div>
                                      <div class="small text-muted">Nominal Mingguan</div>
                                      <div class="fw-semibold">Rp {{ number_format($nominal, 0, ',', '.') }}</div>

                                      @if($arrearTotal > 0)
                                        <div class="small text-muted mt-2">
                                          Tunggakan lama:
                                          <strong>Rp {{ number_format($arrearTotal, 0, ',', '.') }}</strong>
                                          ({{ $arrearCount }} minggu)
                                        </div>
                                      @endif
                                    </div>
                                  </div>

                                  <div class="text-end">
                                    <div class="small text-muted">Sisa minggu ini</div>
                                    <div class="fw-semibold {{ $r->sisa > 0 ? 'text-warning' : 'text-success' }}">
                                      Rp {{ number_format($r->sisa, 0, ',', '.') }}
                                    </div>
                                  </div>
                                </div>

                                <div class="mt-3 pt-2 border-top small d-flex justify-content-between flex-wrap gap-2">
                                  <span class="text-muted">
                                    Total kewajiban (minggu ini + tunggakan)
                                  </span>
                                  <span class="fw-semibold" style="color: red;">
                                    Rp {{ number_format($totalDue, 0, ',', '.') }}
                                  </span>
                                </div>
                              </div>
                            </div>

                            {{-- PERIODE PEMBAYARAN (TUNGGAKAN) --}}
                            @if($lateList->isNotEmpty() && isset($availablePeriods) && $availablePeriods->isNotEmpty())
                              <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                  <label class="form-label mb-0">Periode Pembayaran</label>
                                  <span class="small text-muted">
                                    Pilih minggu yang ingin dibayar / dilunasi.
                                  </span>
                                </div>

                                <div class="list-group small">
                                  @foreach($availablePeriods as $p)
                                    @php
                                      $lateItem = $lateList->firstWhere('period_id', $p->id);
                                      $lateRemaining = $lateItem ? (int) ($lateItem->sisa ?? 0) : 0;
                                    @endphp

                                    <label class="list-group-item d-flex justify-content-between align-items-start gap-2">
                                      <span class="d-flex align-items-start gap-2">
                                        <input
                                          type="radio"
                                          name="target_period_id"
                                          value="{{ $p->id }}"
                                          data-status="{{ $p->status }}"
                                          data-start="{{ $p->date_start->toDateString() }}"
                                          data-end="{{ $p->date_end->toDateString() }}"
                                          {{ $loop->first ? 'checked' : '' }}
                                          class="mt-1"
                                        >
                                        <span>
                                          <div>
                                            Periode #{{ $p->week_no }}
                                            <span class="text-muted">
                                              ({{ $p->date_start->format('d M') }}–{{ $p->date_end->format('d M Y') }})
                                            </span>
                                          </div>

                                          @if($lateRemaining > 0)
                                            <div class="text-muted small mt-1">
                                              Sisa periode ini:
                                              <strong>Rp {{ number_format($lateRemaining, 0, ',', '.') }}</strong>
                                            </div>
                                          @endif
                                        </span>
                                      </span>

                                      <span class="badge {{ $p->status === 'open'
                                          ? 'bg-success-subtle text-success border border-success-subtle'
                                          : 'bg-secondary-subtle text-secondary border border-secondary-subtle'
                                        }}">
                                        {{ strtoupper($p->status) }}
                                      </span>
                                    </label>
                                  @endforeach
                                </div>

                                {{-- BLOK: AUTO SPLIT TUNGGAKAN --}}
                                <div class="rounded-3 bg-body-tertiary p-3 mt-3">
                                  <div class="form-check mb-1">
                                    <label class="form-check-label small" for="autoSplit_{{ $enrollId }}">
                                      <input
                                        class="form-check-input"
                                        type="checkbox"
                                        value="1"
                                        id="autoSplit_{{ $enrollId }}"
                                        name="auto_split"
                                      >
                                      Otomatis alokasikan berurutan ke tunggakan (terlama → terbaru) jika jumlah melebihi satu periode
                                    </label>
                                  </div>
                                  <div class="form-text mb-0">
                                    Jika tidak dicentang, pembayaran hanya masuk ke periode yang dipilih di atas.
                                  </div>
                                </div>
                              </div>
                            @else
                              {{-- fallback: tetap kirim target_period_id minggu ini --}}
                              @if($period)
                                <input type="hidden" name="target_period_id" value="{{ $period->id }}">
                              @endif
                            @endif

                            <hr class="my-3">

                            {{-- FORM JUMLAH & TANGGAL --}}
                            <div class="row g-3">
                              <div class="col-md-6">
                                <label class="form-label">Jumlah Bayar <span class="text-danger">*</span></label>
                                <div class="input-group input-group-curved">
                                  <span class="input-group-text">Rp</span>
                                  <input
                                    type="number"
                                    class="form-control"
                                    name="amount"
                                    min="1"
                                    required
                                    placeholder="contoh: 5000"
                                    inputmode="numeric"
                                  >
                                </div>
                                <div class="form-text">Boleh cicilan. Status dihitung otomatis.</div>
                              </div>

                              <div class="col-md-6">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input
                                  type="date"
                                  class="form-control"
                                  name="date"
                                  value="{{ now()->toDateString() }}"
                                  @if($period)
                                    min="{{ \Illuminate\Support\Carbon::parse($period->date_start)->toDateString() }}"
                                    max="{{ \Illuminate\Support\Carbon::parse($period->date_end)->toDateString() }}"
                                  @endif
                                >
                              </div>

                              <div class="col-12">
                                <label class="form-label">Catatan (opsional)</label>
                                <input
                                  type="text"
                                  class="form-control"
                                  name="note"
                                  maxlength="255"
                                  placeholder="Contoh: tunai / transfer / keterangan lain"
                                >
                              </div>
                            </div>
                          @endif
                        </div>

                        <div class="modal-footer border-0 pt-0">
                          <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                          <button type="submit" class="btn btn-primary rounded-pill" {{ !$periodIsOpen ? 'disabled' : '' }}>
                            <i class="mdi mdi-content-save-outline me-1"></i> Simpan
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>

                 {{-- MODAL: RIWAYAT --}}

                  <div class="modal fade" id="{{ $modalHisId }}" tabindex="-1" aria-labelledby="{{ $modalHisId }}Label" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                      <div class="modal-content rounded-4 glass-modal">
                        <div class="modal-header border-0">
                          <div>
                            <div class="small text-muted">Riwayat Pembayaran (minggu ini)</div>
                            <h5 class="modal-title fw-semibold" id="{{ $modalHisId }}Label">{{ $student->name }}</h5>
                          </div>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>

                        <div class="modal-body pt-0">
                          @if($history->isEmpty())
                            <div class="py-4 text-center text-muted">
                              <div class="mb-2">
                                <i class="mdi mdi-receipt-text-outline fs-1"></i>
                              </div>
                              <div class="fw-semibold mb-1">Belum ada pembayaran</div>
                              <div class="small">Gunakan tombol <em>Bayar</em> untuk menambah transaksi.</div>
                            </div>
                          @else
                            <div class="table-responsive">
                              <table class="table table-sm align-middle mb-0">
                                <thead class="small text-muted bg-body-tertiary">
                                  <tr>
                                    <th style="width:140px">Jumlah</th>
                                    <th style="width:150px">Tanggal</th>
                                    <th>Catatan & Alokasi</th>
                                    <th style="width:180px">Penerima</th>
                                    <th class="text-end" style="width:200px">Aksi</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  @foreach($history as $pay)
                                    @php
                                      $allocs = isset($allocationsByPayment)
                                        ? ($allocationsByPayment[$pay->id] ?? collect())
                                        : collect();

                                      $allocatedSum   = (int) $allocs->sum('allocated_amount');
                                      $forCurrentWeek = max(0, (int) $pay->amount - $allocatedSum);
                                    @endphp
                                    <tr>
                                      <td class="fw-semibold">
                                        Rp {{ number_format($pay->amount, 0, ',', '.') }}
                                      </td>
                                      <td>
                                        {{ \Illuminate\Support\Carbon::parse($pay->date)->translatedFormat('d M Y') }}
                                      </td>
                                      <td>
                                        <div>{{ $pay->note ?? '—' }}</div>

                                        {{-- Breakdown alokasi pembayaran ini --}}
                                        <div class="small text-muted mt-1">
                                          <i class="mdi mdi-link-variant me-1"></i>
                                          Dipakai untuk:
                                          <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle">
                                            Minggu ini: Rp {{ number_format($forCurrentWeek, 0, ',', '.') }}
                                          </span>

                                          @foreach($allocs as $a)
                                            <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle mt-1">
                                              Minggu #{{ $a->week_no }}
                                              ({{ \Illuminate\Support\Carbon::parse($a->date_start)->format('d M') }}–{{ \Illuminate\Support\Carbon::parse($a->date_end)->format('d M Y') }})
                                              · Rp {{ number_format($a->allocated_amount, 0, ',', '.') }}
                                            </span>
                                          @endforeach
                                        </div>
                                      </td>
                                      <td class="small">
                                        {{ $pay->receiver?->name ?? '—' }}
                                      </td>
                                      <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                          <button
                                            class="btn btn-outline-dark btn-sm rounded-pill"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#editRow_{{ $pay->id ?? $pay->payment_id ?? $pay->ID }}"
                                            {{ !$periodIsOpen ? 'disabled' : '' }}
                                          >
                                            <i class="mdi mdi-pencil-outline me-1"></i>Edit
                                          </button>
                                          <button
                                            class="btn btn-outline-danger btn-sm rounded-pill"
                                            data-bs-toggle="modal"
                                            data-bs-target="#confirmDelete"
                                            data-pay-id="{{ $pay->id ?? $pay->payment_id ?? $pay->ID }}"
                                            data-pay-amount="{{ $pay->amount }}"
                                            data-pay-date="{{ \Illuminate\Support\Carbon::parse($pay->date)->toDateString() }}"
                                            {{ !$periodIsOpen ? 'disabled' : '' }}
                                          >
                                            <i class="mdi mdi-trash-can-outline me-1"></i>Hapus
                                          </button>
                                        </div>
                                      </td>
                                    </tr>

                                    {{-- Inline edit row --}}
                                    <tr id="editRow_{{ $pay->id ?? $pay->payment_id ?? $pay->ID }}" class="collapse">
                                      <td colspan="5" class="bg-body">
                                        <form
                                          method="POST"
                                          action="{{ route('cash.update', $pay->id) }}"
                                          class="border rounded-3 p-3"
                                        >
                                          @csrf
                                          @method('PUT')

                                          <div class="row g-3">
                                            <div class="col-md-4">
                                              <label class="form-label">Jumlah (Rp)</label>
                                              <input
                                                type="number"
                                                name="amount"
                                                class="form-control"
                                                min="1"
                                                value="{{ $pay->amount }}"
                                                required
                                                {{ !$periodIsOpen ? 'disabled' : '' }}
                                              >
                                            </div>
                                            <div class="col-md-4">
                                              <label class="form-label">Tanggal</label>
                                              <input
                                                type="date"
                                                name="date"
                                                class="form-control"
                                                value="{{ \Illuminate\Support\Carbon::parse($pay->date)->toDateString() }}"
                                                {{ !$periodIsOpen ? 'disabled' : '' }}
                                              >
                                            </div>
                                            <div class="col-md-4">
                                              <label class="form-label">Catatan</label>
                                              <input
                                                type="text"
                                                name="note"
                                                class="form-control"
                                                maxlength="255"
                                                value="{{ $pay->note }}"
                                                {{ !$periodIsOpen ? 'disabled' : '' }}
                                              >
                                            </div>
                                            <div class="col-12 text-end">
                                              <button
                                                type="submit"
                                                class="btn btn-sm btn-primary rounded-pill"
                                                {{ !$periodIsOpen ? 'disabled' : '' }}
                                              >
                                                <i class="mdi mdi-content-save-outline me-1"></i> Simpan Perubahan
                                              </button>
                                            </div>
                                          </div>
                                        </form>
                                      </td>
                                    </tr>
                                  @endforeach
                                </tbody>
                              </table>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-3">
                              <div class="small text-muted">
                                Total minggu ini:
                                <strong>Rp {{ number_format($history->sum('amount'), 0, ',', '.') }}</strong>
                              </div>
                              @php
                                $tot = (int) $history->sum('amount');
                                $stt = $tot >= $nominal && $nominal > 0 ? 'LUNAS' : 'BELUM';
                              @endphp
                              <span class="badge {{ $stt === 'LUNAS' ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill px-3 py-2">
                                <i class="mdi {{ $stt === 'LUNAS' ? 'mdi-check-all' : 'mdi-timer-sand' }} me-1"></i>{{ $stt }}
                              </span>
                            </div>
                          @endif
                        </div>

                        <div class="modal-footer border-0">
                          <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Tutup</button>
                        </div>
                      </div>
                    </div>
                  </div>

            {{-- MODAL: ALASAN --}}
                <div class="modal fade" id="{{ $modalReasonId }}" tabindex="-1" aria-labelledby="{{ $modalReasonId }}Label" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered modal-compact">
                    <form
                      class="modal-content glass-modal modal-content-compact"
                      method="POST"
                      action="{{ route('cash.unpaidReason.store') }}"
                      id="reasonForm_{{ $enrollId }}"
                    >
                      @csrf
                      <input type="hidden" name="enrollment_id" value="{{ $enrollId }}">

                      <div class="modal-header border-0 pb-1">
                        <div>
                          <h6 class="modal-title fw-semibold mb-0" id="{{ $modalReasonId }}Label">
                            Alasan Belum Lunas
                          </h6>
                          <div class="small text-muted mt-1 d-flex align-items-center gap-2">
                            <i class="mdi mdi-account-outline"></i>
                            <span class="text-truncate" style="max-width: 260px;">
                              {{ $student->name }}
                            </span>
                          </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                      </div>

                      <div class="modal-body pt-2">
                        @if(isset($period))
                          <div class="reason-headerchips mb-2 d-flex flex-wrap gap-2">
                            <span class="chip">
                              <i class="mdi mdi-calendar-blank-outline me-1"></i>
                              Minggu #{{ $period->week_no }}
                            </span>
                            <span class="chip {{ $period->status === 'open' ? 'chip-green' : 'chip-gray' }}">
                              <i class="mdi {{ $period->status === 'open' ? 'mdi-lock-open-variant-outline' : 'mdi-lock-outline' }} me-1"></i>
                              {{ strtoupper($period->status) }}
                            </span>
                            @php $sisaRp = (int) ($r->sisa ?? 0); @endphp
                            <span class="chip chip-amber">
                              <i class="mdi mdi-cash-remove me-1"></i>
                              Sisa: Rp {{ number_format($sisaRp, 0, ',', '.') }}
                            </span>
                          </div>
                        @endif

                        <label class="form-label small mb-1">
                          Alasan <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                          <textarea
                            name="reason"
                            class="form-control form-control-compact reason-textarea"
                            rows="4"
                            maxlength="255"
                            required
                            id="reasonTextarea_{{ $enrollId }}"
                            placeholder="Contoh: belum membawa uang, orang tua belum transfer, izin sakit, dll."
                          >{{ old('reason', $r->reason ?? null) }}</textarea>

                          <div class="small text-muted mt-1 d-flex justify-content-between">
                            <span>Wajib diisi untuk status BELUM</span>
                            <span id="reasonCounter_{{ $enrollId }}">0/255</span>
                          </div>
                        </div>

                        <div class="mt-3">
                          <div class="small text-muted mb-1">Alasan cepat</div>
                          <div class="d-flex flex-wrap gap-2">
                            @php
                              $presets = [
                                'Belum membawa uang',
                                'Orang tua belum transfer',
                                'Izin sakit',
                                'Lupa membawa dompet',
                                'Akan bayar besok',
                                'Kendala keluarga',
                              ];
                            @endphp
                            @foreach($presets as $preset)
                              <button
                                type="button"
                                class="btn btn-light btn-sm rounded-pill reason-chip"
                                data-insert="{{ $preset }}"
                              >
                                <i class="mdi mdi-plus-circle-outline me-1"></i>{{ $preset }}
                              </button>
                            @endforeach
                          </div>
                        </div>
                      </div>

                      <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill">
                          <i class="mdi mdi-content-save-outline me-1"></i> Simpan
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
                @endpush

              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

</div>


{{-- ====== MODAL KONFIRMASI HAPUS (GLOBAL) ====== --}}
<div class="modal fade" id="confirmDelete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-compact">
    <form id="confirmDeleteForm" method="POST" class="modal-content glass-modal modal-content-compact">
      @csrf @method('DELETE')

      <div class="modal-header border-0 pb-1">
        <div>
          <h6 class="modal-title fw-semibold mb-0">Hapus Pembayaran</h6>
          <div class="small text-muted mt-1">Tindakan ini akan dicatat pada riwayat aktivitas</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body pt-2">
        <div class="summary-compact mb-3">
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="mdi mdi-receipt-outline fs-5 text-muted"></i>
              <div class="small">
                <div class="text-muted">Jumlah</div>
                <div class="fw-semibold" id="delAmount">Rp 0</div>
              </div>
            </div>
            <div class="vr d-none d-sm-block"></div>
            <div class="d-flex align-items-center gap-2">
              <i class="mdi mdi-calendar-blank-outline fs-5 text-muted"></i>
              <div class="small text-end">
                <div class="text-muted">Tanggal</div>
                <div class="fw-semibold" id="delDate">—</div>
              </div>
            </div>
          </div>
        </div>

        <label class="form-label small mb-1">Alasan penghapusan <span class="text-danger">*</span></label>
        <textarea
          name="reason"
          id="delReason"
          class="form-control form-control-compact"
          rows="3"
          required
          maxlength="255"
        ></textarea>
        <div class="d-flex justify-content-between align-items-center mt-1">
          <span class="small text-muted">Maks. 255 karakter</span>
          <span class="small text-muted" id="reasonCounter">0/255</span>
        </div>
      </div>

      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger rounded-pill">
          <i class="mdi mdi-trash-can-outline me-1"></i> Hapus
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ====== KUMPULKAN SEMUA MODAL DI SINI ====== --}}
@stack('modals')



{{-- Styles khusus halaman --}}
<style>
  :root{
    --modal-surface: #ffffff;
    --modal-border: #e9eef5;
    --modal-shadow: 0 20px 55px rgba(10, 21, 39, .18);
    --modal-backdrop: rgba(9, 12, 20, .55);
  }

  .avatar{ width:44px; height:44px; border-radius:12px; display:inline-grid; place-items:center; font-size:20px; }
  .avatar-sm{ width:36px; height:36px; border-radius:10px; font-size:18px; }

  .glass{ backdrop-filter: saturate(180%) blur(8px); background: rgba(255,255,255,.8); }
  .soft .avatar{ width:34px; height:34px; font-size:16px; border-radius:10px; }

  .status-badge{ border-radius:999px; padding:.4rem .65rem; font-weight:600; }
  .status-green{ background:#eaf7ef; color:#157347; border:1px solid #cfe9d8; }
  .status-amber{ background:#fff6e6; color:#8a5a00; border:1px solid #ffe0b3; }

  .progress-bar{ transition: width .35s ease; }
  @media (prefers-reduced-motion: reduce){ .progress-bar{ transition: none; } }

  .kpi-grid{ display:grid; gap:.6rem; }
  .kpi-grid-2x2{ grid-template-columns: repeat(2, minmax(140px, 1fr)); max-width: 520px; }
  .kpi-chip{
    border:1px solid var(--bs-border-color);
    background:var(--bs-body-bg);
    border-radius:14px;
    padding:.6rem .8rem;
    display:flex; flex-direction:column; align-items:flex-start; min-height:56px;
  }
  .kpi-label{ font-size:.72rem; color:var(--bs-secondary-color); line-height:1.1; }
  .kpi-value{ font-weight:700; font-size: clamp(.9rem, 1.4vw, 1.05rem); line-height:1.2; margin-top:.2rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .kpi-green{ background:var(--bs-success-bg-subtle); border-color:rgba(25,135,84,.25); }
  .kpi-amber{ background:var(--bs-warning-bg-subtle); border-color:rgba(255,193,7,.35); }
  .kpi-blue{  background:var(--bs-primary-bg-subtle); border-color:rgba(13,110,253,.25); }

  .action-cell{ min-width: 240px; }
  .btn-action{ min-width: 96px; padding-left: .9rem; padding-right: .9rem; }
  @media (max-width: 576px){
    td.pe-4 { padding-right: 1rem !important; }
    .action-cell{ width:100%; }
  }

  .modal-backdrop.show{ background: var(--modal-backdrop); opacity: 1; backdrop-filter: blur(2px); }
  .glass-modal{ background: var(--modal-surface); border: 1px solid var(--modal-border); box-shadow: var(--modal-shadow); }
  .modal .modal-content{ border-radius: 18px; }
  .modal .modal-header, .modal .modal-footer{ padding: 1rem 1.25rem; border-color: #f1f5fb !important; }
  .modal .modal-body{ padding: 1rem 1.25rem; }
  .modal .modal-title{ font-weight: 700; letter-spacing: .2px; }

  .modal .modal-dialog{ margin: var(--bs-modal-margin, 1rem) auto; }
  .modal.fade .modal-dialog{ margin-left: auto; margin-right: auto; }
  .modal-dialog{ max-width: 100%; }
  @media (min-width: 576px){
    .modal-dialog.modal-md{ max-width: 560px; }
    .modal-dialog.modal-lg{ max-width: 900px; }
  }

  /* Compact modal – dibuat lebih proporsional */
  .modal-dialog.modal-compact{
    max-width: 600px;
    width: min(600px, calc(100% - 2rem));
  }
  .modal-content-compact{ border-radius:16px; }
  .modal-content-compact .modal-header{ padding: .85rem 1rem; }
  .modal-content-compact .modal-body{ padding: .75rem 1rem 1rem; }
  .modal-content-compact .modal-footer{ padding: .75rem 1rem 1rem; }

  .form-control-compact{ padding:.5rem .75rem; font-size:.925rem; }

  .summary-compact{ border:1px dashed var(--modal-border); background: var(--bs-body-bg); border-radius:12px; padding:.6rem .8rem; }
  .summary-compact .vr{ height: 28px; opacity: .2; }

  /* ===== Modal Alasan ===== */
  .reason-headerchips { display:flex; flex-wrap:wrap; gap:.4rem; }
  .chip {
    display:inline-flex; align-items:center; gap:.25rem;
    font-size:.78rem; padding:.25rem .6rem; border-radius:999px;
    border:1px solid var(--modal-border); background:var(--bs-body-bg); color:var(--bs-body-color);
  }
  .chip-green { background: var(--bs-success-bg-subtle); border-color: rgba(25,135,84,.25); color: var(--bs-success-text-emphasis, #157347); }
  .chip-amber { background: var(--bs-warning-bg-subtle); border-color: rgba(255,193,7,.35); color: #8a5a00; }
  .chip-gray  { background: var(--bs-secondary-bg); color: var(--bs-secondary-color); }

  .reason-textarea { min-height:108px; width:100%; resize:vertical; }

  .reason-chip { padding:.35rem .8rem; border:1px solid var(--modal-border); background:#fff; }
  .reason-chip:hover, .reason-chip:focus { background: var(--bs-primary-bg-subtle); border-color: rgba(13,110,253,.25); }

  .btn{ line-height: 1.15; }
</style>

{{-- UX helpers --}}
<script>
  // Fokus otomatis ke jumlah saat modal bayar dibuka
  document.addEventListener('shown.bs.modal', e => {
    const amountInput = e.target.querySelector('input[name="amount"]');
    if(amountInput){ amountInput.focus(); amountInput.select?.(); }
  });

  document.addEventListener('DOMContentLoaded', () => {
    // Konfirmasi hapus: isi dinamis
    const modalEl = document.getElementById('confirmDelete');
    if (modalEl) {
      modalEl.addEventListener('show.bs.modal', (event) => {
        const btn = event.relatedTarget;
        const payId = btn?.getAttribute('data-pay-id');
        const amount = parseInt(btn?.getAttribute('data-pay-amount') || '0', 10);
        const dateStr = btn?.getAttribute('data-pay-date') || '';
        const formatRp = (n) => 'Rp ' + (n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        modalEl.querySelector('#delAmount').textContent = formatRp(amount);
        modalEl.querySelector('#delDate').textContent = dateStr;
        const reasonInput = modalEl.querySelector('#delReason'); if (reasonInput) reasonInput.value = '';

        const form = document.getElementById('confirmDeleteForm');
        if (form && payId) form.action = `/kas/payments/${payId}`;
      });
    }

    // Header dinamis
    const table = document.getElementById('cash-table');
    const progressBar = document.getElementById('header-progress-bar');
    const progressText = document.getElementById('header-progress-text');
    const kpiSiswa = document.getElementById('kpi-siswa');
    const kpiLunas = document.getElementById('kpi-lunas');
    const kpiBelum = document.getElementById('kpi-belum');
    const kpiTerkumpul = document.getElementById('kpi-terkumpul');

    function recomputeHeaderFromTable() {
      if (!table) return;
      const rows = Array.from(table.querySelectorAll('tbody > tr')).filter(tr => tr.querySelector('td'));
      const totalRows = rows.length;

      let lunas = 0, belum = 0, terkumpul = 0;
      rows.forEach(tr => {
        const statusBadge = tr.querySelector('.status-badge');
        if (statusBadge && statusBadge.textContent.trim().toUpperCase().includes('LUNAS')) lunas++; else belum++;

        const paidCell = tr.querySelector('td:nth-child(2) .fw-semibold');
        if (paidCell) {
          const val = (paidCell.textContent.replace(/[^\d]/g,'') || '0') * 1;
          terkumpul += val;
        }
      });

      if (kpiSiswa) kpiSiswa.textContent = totalRows;
      if (kpiLunas) kpiLunas.textContent = lunas;
      if (kpiBelum) kpiBelum.textContent = belum;
      if (kpiTerkumpul) kpiTerkumpul.textContent = 'Rp ' + (terkumpul.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));

      const pct = totalRows > 0 ? Math.round(100 * (lunas / totalRows)) : 0;
      if (progressBar) progressBar.style.width = pct + '%';
      if (progressText) progressText.textContent = pct + '%';
    }
    document.addEventListener('payments:updated', recomputeHeaderFromTable);

    // Modal Alasan: counter, autosize, preset insert
    document.querySelectorAll('[id^="reasonModal_"]').forEach((modalEl) => {
      const enrollId = modalEl.id.split('_')[1];
      const ta = modalEl.querySelector('#reasonTextarea_' + enrollId);
      const counter = modalEl.querySelector('#reasonCounter_' + enrollId);

      const updateUI = () => {
        if (!ta || !counter) return;
        const len = ta.value.length || 0;
        counter.textContent = `${len}/255`;
        ta.style.height = 'auto';
        ta.style.height = Math.min(320, ta.scrollHeight) + 'px';
      };

      modalEl.addEventListener('shown.bs.modal', () => { if (ta) { ta.focus(); ta.select?.(); updateUI(); } });
      ta?.addEventListener('input', updateUI); updateUI();

      modalEl.querySelectorAll('.reason-chip[data-insert]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const text = btn.getAttribute('data-insert') || '';
          if (!ta) return;
          const joiner = ta.value && !ta.value.endsWith(' ') ? ' ' : '';
          const candidate = (ta.value + joiner + text).trim();
          ta.value = candidate.slice(0, 255);
          ta.dispatchEvent(new Event('input'));
          ta.focus();
        });
      });
    });
  });


 document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[id^="payModal_"]').forEach((modal) => {
    const id = modal.id.split('_')[1];
    const radios = modal.querySelectorAll('input[name="target_period_id"]');
    if (!radios.length) return; // kalau tidak ada pilihan periode, tidak perlu apa-apa

    const arrearsInput = modal.querySelector('#allocArrears_' + id);

    const apply = (radio) => {
      if (!radio || !arrearsInput) return;
      const status = radio.dataset.status || 'open';
      // Kalau target periode CLOSED -> ini mode bayar tunggakan
      arrearsInput.value = (status === 'closed') ? 1 : 0;
    };

    radios.forEach(r => r.addEventListener('change', (e) => apply(e.target)));

    modal.addEventListener('shown.bs.modal', () => {
      const checked = Array.from(radios).find(r => r.checked) || radios[0];
      apply(checked);
    });
  });
});

</script>

@endsection
