@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 960px;">

  {{-- ===== Wizard Mini (konsisten) ===== --}}
  <nav class="mb-4">
    <ul class="nav nav-pills flex-wrap gap-2 year-wizard">
      @php
        $onSetting  = url()->current() === route('year.setting', $year);
        $onStudents = url()->current() === route('year.students', $year);
      @endphp
      <li class="nav-item">
        <a class="nav-link rounded-pill d-flex align-items-center gap-2 {{ $onSetting ? 'active' : 'wizard-muted' }}"
           href="{{ route('year.setting', $year) }}">
          <span class="wizard-step">1</span>
          <span>Nominal</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link rounded-pill d-flex align-items-center gap-2 {{ $onStudents ? 'active' : 'wizard-muted' }}"
           href="{{ route('year.students', $year) }}">
          <span class="wizard-step">2</span>
          <span>Siswa & Bendahara</span>
        </a>
      </li>
    </ul>
  </nav>

  {{-- ===== Flash & Errors ===== --}}
  @if(session('success'))
    <div class="alert alert-success rounded-4 shadow-sm alert-soft mb-3">
      {{ session('success') }}
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger rounded-4 shadow-sm alert-soft mb-3">
      <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  {{-- ===== Header Tahun Ajaran ===== --}}
  <div class="rounded-4 shadow-sm p-3 p-md-4 bg-white mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="d-flex align-items-center gap-3">
        <div class="status-dot {{ $year->status }}"></div>
        <div>
          <div class="fw-semibold">{{ $year->class_label }} ({{ $year->level }})</div>
          <div class="text-muted small">
            Tahun: {{ $year->academic_year }}
            @if($year->homeroom_name) • Wali: {{ $year->homeroom_name }} @endif
          </div>
        </div>
      </div>
      <div class="text-end">
        <span class="badge rounded-pill {{ $year->status === 'active' ? 'bg-success' : ($year->status==='draft' ? 'bg-warning text-dark' : 'bg-secondary') }}">
          {{ ucfirst($year->status) }}
        </span>
      </div>
    </div>
  </div>

  {{-- ===== Kartu Form Nominal ===== --}}
  <div class="card-soft">
    <div class="card-soft-body">
      <h5 class="mb-3">Atur Nominal Kas</h5>

      <form method="POST" action="{{ route('year.setting', $year) }}" class="small">
        @csrf @method('PUT')

        <div class="mb-3">
          <label class="form-label">Nominal / Minggu</label>
          <div class="input-group">
            <span class="input-group-text rounded-start-pill">Rp</span>
            <input type="number"
                   name="kas_nominal"
                   class="form-control rounded-end-pill"
                   value="{{ old('kas_nominal', $year->setting?->kas_nominal ?? '') }}"
                   min="1000" step="100" required>
          </div>
          <div class="form-text">Minimal Rp 1.000 (bisa diubah kembali meskipun tahun sudah aktif).</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Hari Bayar (opsional)</label>
          <select name="pay_day_hint" class="form-select rounded-pill">
            @php $sel = old('pay_day_hint',$year->setting?->pay_day_hint); @endphp
            <option value="">(Tidak ditentukan)</option>
            @foreach(['Sen','Sel','Rab','Kam','Jum'] as $d)
              <option value="{{ $d }}" @selected($sel===$d)>{{ $d }}</option>
            @endforeach
          </select>
        </div>

        <div class="row g-2">
          <div class="col-12 col-md">
            <button class="btn btn-primary rounded-pill w-100">Simpan</button>
          </div>
          @if($year->status==='draft')
            <div class="col-12 col-md">
              <a class="btn btn-outline-dark rounded-pill w-100"
                 href="{{ route('year.students', $year) }}">
                Lanjut: Siswa & Bendahara
              </a>
            </div>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- ===== Panel Bantuan (opsional) ===== --}}
  <div class="small text-muted mt-3">
    <ul class="mb-0 ps-3">
      <li>Nominal berlaku untuk perhitungan otomatis status bayar mingguan.</li>
      <li>Perubahan nominal setelah aktif akan memengaruhi minggu berjalan & selanjutnya (kebijakan detail di controller).</li>
    </ul>
  </div>
</div>

{{-- ===== Style kecil konsisten dgn halaman Siswa & Bendahara ===== --}}
<style>
  :root{
    --soft-bg: #f7f8fa;
    --soft-border: #e9ecf1;
    --soft-primary: #2563eb;
  }
  .alert-soft { border:1px solid var(--soft-border); }

  .card-soft{ background:#fff; border:1px solid var(--soft-border); border-radius:1rem; }
  .card-soft-body{ padding:1.25rem; }

  /* Wizard */
  .year-wizard .nav-link{
    background:#fff; border:1px solid var(--soft-border); color:#111827;
    padding:.45rem .9rem; transition:all .15s ease;
  }
  .year-wizard .nav-link:hover{ border-color:#d7dbe2; background:#fbfbfd; }
  .year-wizard .nav-link.active{
    background:var(--soft-primary); color:#fff; border-color:var(--soft-primary);
    box-shadow:0 4px 16px rgba(37,99,235,.15);
  }
  .year-wizard .wizard-muted{ color:#1f2937; }
  .wizard-step{
    display:inline-grid; place-items:center; width:1.5rem; height:1.5rem; border-radius:999px;
    font-size:.8rem; font-weight:600; background:rgba(37,99,235,.08); color:var(--soft-primary);
  }
  .year-wizard .nav-link.active .wizard-step{ background:rgba(255,255,255,.25); color:#fff; }

  /* Status dot */
  .status-dot{ width:10px; height:10px; border-radius:50%; }
  .status-dot.draft{ background:#f59e0b; }
  .status-dot.active{ background:#22c55e; }
  .status-dot.archived{ background:#9ca3af; }

  /* Focus ring halus */
  .form-control:focus, .form-select:focus, .btn:focus{
    box-shadow:0 0 0 .2rem rgba(37,99,235,.15);
    border-color:var(--soft-primary);
  }

  @media (max-width:576px){
    .card-soft-body{ padding:1rem; }
  }
</style>
@endsection
