@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1100px;">
   @php
      use Illuminate\Support\Carbon;
      Carbon::setLocale('id');
      date_default_timezone_set('Asia/Jakarta');

      $dayMap = [
        'Sen' => 'Senin',
        'Sel' => 'Selasa',
        'Rab' => 'Rabu',
        'Kam' => 'Kamis',
        'Jum' => 'Jumat',
      ];

      $payDayCode = $activeYear?->setting?->pay_day_hint;
      $payDayText = $payDayCode ? ($dayMap[$payDayCode] ?? $payDayCode) : null;
    @endphp


  {{-- Flash message --}}
  @if(session('success'))
    <div class="alert alert-success rounded-4 shadow-sm mb-3">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger rounded-4 shadow-sm mb-3">
      <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  {{-- Header Tahun Aktif --}}
  <div class="rounded-4 shadow-sm p-3 p-md-4 bg-white mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="d-flex align-items-center gap-3">
        <div class="status-dot {{ $activeYear?->status ?? 'draft' }}"></div>
        <div>
          <div class="fw-semibold">
            @if($activeYear)
              {{ $activeYear->class_label }} ({{ $activeYear->level }})
            @else
              — Tidak ada tahun aktif —
            @endif
          </div>
          <div class="text-muted small">
            Tahun:
            {{ $activeYear?->academic_year ?? '—' }}
            @if($activeYear?->homeroom_name)
              • Wali: {{ $activeYear->homeroom_name }}
            @endif
          </div>
        </div>
      </div>

      <div class="text-end">
        @if($activeYear)
          <span class="badge rounded-pill {{ $activeYear->status==='active' ? 'bg-success' : ($activeYear->status==='draft' ? 'bg-warning text-dark' : 'bg-secondary') }}">
            {{ ucfirst($activeYear->status) }}
          </span>
        @else
          <span class="badge rounded-pill bg-secondary">—</span>
        @endif
      </div>
    </div>
  </div>

  @if(!$activeYear)
    <div class="card-soft">
      <div class="card-soft-body">
        <div class="text-muted">
          Belum ada <strong>tahun ajaran aktif</strong>. Silakan aktifkan dulu dari menu Tahun Ajaran.
        </div>
      </div>
    </div>
  @else
    {{-- Aksi Cepat (Guru & Bendahara) --}}
    @hasanyrole('guru|bendahara')
      <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">

        {{-- Jika belum ada periode OPEN, boleh buat minggu baru --}}
        @if(!$currentOpen)
          <form method="POST" action="{{ route('period.openToday') }}" class="js-oneclick">
            @csrf
            <button class="btn btn-primary rounded-pill">
              Buat Periode  
            </button>
          </form>

          @if($payDayText)
            <span class="badge bg-light text-dark border rounded-pill">
              Rekomendasi mulai periode: <strong>{{ $payDayText }}</strong>
            </span>
          @endif

          @if($periods->isNotEmpty())
            <span class="text-muted small">
              Tidak ada periode <strong>OPEN</strong>.  
            </span>
          @else
            <span class="text-muted small">
              Belum ada periode kas. Klik <strong>Buat Periode  </strong> 
            </span>
          @endif
        @endif


        {{-- Jika ada periode OPEN, boleh tutup --}}
        @if($currentOpen)
          <form method="POST"
                action="{{ route('period.close', $currentOpen) }}"
                class="js-oneclick"
                onsubmit="return confirm('Tutup periode berjalan (#{{ $currentOpen->week_no }})?')">
            @csrf
            <button class="btn btn-outline-dark rounded-pill">
              Tutup Periode Berjalan (#{{ $currentOpen->week_no }})
            </button>
          </form>

          <span class="badge bg-info text-dark rounded-pill ms-1">
            Periode OPEN: #{{ $currentOpen->week_no }}
            ({{ Carbon::parse($currentOpen->date_start)->locale('id')->isoFormat('D MMM') }}
             – {{ Carbon::parse($currentOpen->date_end)->locale('id')->isoFormat('D MMM Y') }})
          </span>
        @endif

      </div>
    @endhasanyrole

    {{-- Tabel Periode --}}
    <div class="card-soft">
      <div class="card-soft-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="mb-0">Daftar Periode Kas Mingguan</h6>
          <span class="text-muted small">{{ $periods->count() }} minggu</span>
        </div>

        @if($periods->isEmpty())
          @hasanyrole('guru|bendahara')
            <div class="text-muted">
              Belum ada periode. Klik <strong>Buat Periode Minggu Baru</strong> di atas untuk memulai.
            </div>
          @else
            <div class="text-muted">Belum ada periode.</div>
          @endhasanyrole
        @else
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead class="text-muted bg-light">
                <tr>
                  <th style="width:60px;">#</th>
                  <th>Rentang</th>
                  <th style="width:120px;">Status</th>
                  <th class="text-end" style="width:220px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach($periods as $p)
                  @php
                    $isOpen = $currentOpen && $currentOpen->id === $p->id;
                  @endphp
                  <tr class="{{ $isOpen ? 'table-primary' : '' }}">
                    <td class="fw-semibold">
                      #{{ $p->week_no }}
                      @if($isOpen)
                        <span class="badge bg-primary-subtle text-primary border-primary-subtle ms-1">berjalan</span>
                      @endif
                    </td>
                    <td>
                      {{ Carbon::parse($p->date_start)->locale('id')->isoFormat('ddd, D MMM Y') }}
                      &ndash;
                      {{ Carbon::parse($p->date_end)->locale('id')->isoFormat('ddd, D MMM Y') }}
                    </td>
                    <td>
                      @if($p->status === 'open')
                        <span class="badge bg-success rounded-pill">OPEN</span>
                      @else
                        <span class="badge bg-secondary rounded-pill">CLOSED</span>
                      @endif
                    </td>
                    <td class="text-end">
                      @hasanyrole('guru|bendahara')
                        @if($p->status === 'open')
                          {{-- Tutup periode ini --}}
                          <form method="POST"
                                action="{{ route('period.close', $p) }}"
                                class="d-inline js-oneclick"
                                onsubmit="return confirm('Tutup periode #{{ $p->week_no }}?')">
                            @csrf
                            <button class="btn btn-sm btn-outline-dark rounded-pill" title="Tutup periode ini">
                              Tutup
                            </button>
                          </form>
                        @else
                          {{-- Buka periode ini jika tidak ada OPEN lain --}}
                          @if(!$currentOpen)
                            <form method="POST"
                                  action="{{ route('period.open', $p) }}"
                                  class="d-inline js-oneclick"
                                  onsubmit="return confirm('Buka periode #{{ $p->week_no }} ini sebagai minggu aktif?')">
                              @csrf
                              <button class="btn btn-sm btn-outline-primary rounded-pill" title="Buka periode ini">
                                Buka
                              </button>
                            </form>
                          @else
                            <button class="btn btn-sm btn-light rounded-pill" disabled title="Masih ada periode OPEN">
                              Buka
                            </button>
                          @endif
                        @endif
                      @else
                        <span class="text-muted small">—</span>
                      @endhasanyrole
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

{{-- Styles kecil biar konsisten --}}
<style>
  :root{
    --soft-border:#e9ecf1;
    --soft-primary:#2563eb;
  }
  .card-soft{ background:#fff; border:1px solid var(--soft-border); border-radius:1rem; }
  .card-soft-body{ padding:1.25rem; }
  .status-dot{ width:10px; height:10px; border-radius:50%; }
  .status-dot.draft{ background:#f59e0b; }
  .status-dot.active{ background:#22c55e; }
  .status-dot.archived{ background:#9ca3af; }
  .bg-primary-subtle{ background:#e6eeff!important; }
  .border-primary-subtle{ border:1px solid #ccdcff!important; }
  .table-primary td{ background: rgba(37,99,235,.06)!important; }
</style>

{{-- Anti double-submit untuk form aksi cepat --}}
<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form.js-oneclick').forEach(f => {
      f.addEventListener('submit', () => {
        const btn = f.querySelector('button[type="submit"], button:not([type])');
        if (btn) {
          btn.disabled = true;
          const txt = btn.textContent;
          btn.dataset._txt = txt;
          btn.textContent = 'Memproses…';
        }
      });
    });
  });
</script>
@endsection
