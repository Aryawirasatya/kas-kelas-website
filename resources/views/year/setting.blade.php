@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1100px;">

  {{-- ===== Wizard Mini (Langkah 1–2) ===== --}}
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
          <span>Nominal Kas</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link rounded-pill d-flex align-items-center gap-2 {{ $onStudents ? 'active' : 'wizard-muted' }}"
           href="{{ route('year.students', $year) }}">
          <span class="wizard-step">2</span>
          <span>Siswa &amp; Bendahara</span>
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
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- ===== Header Tahun Ajaran ===== --}}
  <div class="rounded-4 shadow-sm p-3 p-md-4 bg-white mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div class="d-flex align-items-center gap-3">
        <div class="status-dot {{ $year->status }}"></div>
        <div>
          @if($year->school_name)
            <div class="text-muted small mb-1">
              {{ $year->school_name }}
            </div>
          @endif

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
          {{ $year->status === 'active'
              ? 'bg-success'
              : ($year->status === 'draft'
                  ? 'bg-warning text-dark'
                  : 'bg-secondary') }}">
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

  {{-- ===== Kartu Form Nominal ===== --}}
  <div class="card-soft mb-4">
    <div class="card-soft-body">
      <h5 class="mb-2">Atur Nominal Kas Mingguan</h5>

      @if($year->status === 'draft')
        <p class="small text-muted mb-3">
          Langkah 2 — Tentukan nominal kas per minggu sebelum tahun ajaran ini diaktifkan.
        </p>
      @else
        <p class="small text-muted mb-3">
          Tahun ajaran ini sudah <strong>ACTIVE</strong>. Kamu masih bisa mengubah nominal jika diperlukan.
        </p>
      @endif

      <form method="POST"
            action="{{ route('year.setting', $year) }}"
            class="small">
        @csrf
        @method('PUT')

        {{-- Nominal / minggu --}}
        <div class="mb-3">
          <label class="form-label">
            Nominal Kas per Minggu (Rp) <span class="text-danger">*</span>
          </label>

          <div class="input-group">
            <span class="input-group-text rounded-start-pill">Rp</span>

            {{-- INPUT TAMPILAN (yang terlihat user, format 5.000) --}}
            <input
              type="text"
              id="kas_nominal_display"
              class="form-control rounded-end-pill @error('kas_nominal') is-invalid @enderror"
              value="{{ old('kas_nominal', $year->setting?->kas_nominal ?? '') }}"
              inputmode="numeric"
              autocomplete="off"
              required
            >

            {{-- INPUT ASLI (yang dikirim ke backend, angka murni 5000) --}}
            <input
              type="hidden"
              name="kas_nominal"
              id="kas_nominal"
              value="{{ old('kas_nominal', $year->setting?->kas_nominal ?? '') }}"
            >

            @error('kas_nominal')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-text">
            Minimal sekitar Rp 1.000. Contoh: <code>5.000</code> untuk kas 5.000 per minggu.
          </div>
        </div>

        {{-- Hari bayar (opsional, kalau pakai pay_day_hint di class_settings) --}}
        <div class="mb-3">
          <label class="form-label">
           rekomandasi mulai Hari Bayar (opsional)
          </label>
          @php
            $sel = old('pay_day_hint', $year->setting?->pay_day_hint);
          @endphp
          <select name="pay_day_hint" class="form-select rounded-pill @error('pay_day_hint') is-invalid @enderror">
            <option value="">(Tidak ditentukan)</option>
            @foreach(['Sen' => 'Senin', 'Sel' => 'Selasa', 'Rab' => 'Rabu', 'Kam' => 'Kamis', 'Jum' => 'Jumat'] as $k => $label)
              <option value="{{ $k }}" @selected($sel === $k)>{{ $label }}</option>
            @endforeach
          </select>
          @error('pay_day_hint')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
          <div class="form-text">
            Hanya sebagai pengingat di tampilan, tidak memengaruhi perhitungan.
          </div>
        </div>

        <div class="row g-2 mt-3">
          <div class="col-12 col-md">
            <button class="btn btn-primary rounded-pill w-100">
              <i class="mdi mdi-content-save-outline me-1"></i>
              Simpan
            </button>
          </div>

          @if($year->status === 'draft')
            <div class="col-12 col-md">
              <a class="btn btn-outline-dark rounded-pill w-100"
                 href="{{ route('year.students', $year) }}">
                Lanjut: Siswa &amp; Bendahara
              </a>
            </div>
          @endif
        </div>

        <div class="small text-muted mt-3">
          <ul class="mb-0 ps-3">
            <li>Nominal ini dipakai untuk menentukan status <strong>LUNAS / BELUM</strong> tiap minggu.</li>
            <li>Perubahan nominal setelah tahun aktif bisa memengaruhi perhitungan ke depan (kebijakan bisa kamu atur di controller).</li>
          </ul>
        </div>
      </form>
    </div>
  </div>

  {{-- ===== Ringkasan Siswa & Bendahara (info singkat) ===== --}}
  <div class="card mb-3">
    <div class="card-header fw-semibold">
      <i class="mdi mdi-account-group-outline me-1"></i>
      Ringkasan Siswa &amp; Bendahara
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="border rounded-3 p-3 h-100">
            <div class="mini-label">Total Siswa di Tahun Ini</div>
            <div class="fw-semibold fs-5">{{ $totalSiswa }}</div>
            <div class="small text-muted">Termasuk aktif &amp; nonaktif.</div>
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
                Belum ada bendahara yang diset.
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

</div>

{{-- ===== Style kecil (konsisten dgn halaman siswa) ===== --}}
<style>
  :root{
    --soft-bg: #f7f8fa;
    --soft-border: #e9ecf1;
    --soft-primary: #2563eb;
  }

  .alert-soft {
    border: 1px solid var(--soft-border);
  }

  .card-soft{
    background:#fff;
    border:1px solid var(--soft-border);
    border-radius:1rem;
  }
  .card-soft-body{
    padding:1.25rem;
  }

  /* Wizard */
  .year-wizard .nav-link{
    background:#fff;
    border:1px solid var(--soft-border);
    color:#111827;
    padding:.45rem .9rem;
    transition:all .15s ease;
  }
  .year-wizard .nav-link:hover{
    border-color:#d7dbe2;
    background:#fbfbfd;
  }
  .year-wizard .nav-link.active{
    background:var(--soft-primary);
    color:#fff;
    border-color:var(--soft-primary);
    box-shadow:0 4px 16px rgba(37,99,235,.15);
  }
  .year-wizard .wizard-muted{
    color:#1f2937;
  }
  .wizard-step{
    display:inline-grid;
    place-items:center;
    width:1.5rem;
    height:1.5rem;
    border-radius:999px;
    font-size:.8rem;
    font-weight:600;
    background:rgba(37,99,235,.08);
    color:var(--soft-primary);
  }
  .year-wizard .nav-link.active .wizard-step{
    background:rgba(255,255,255,.25);
    color:#fff;
  }

  /* Status dot */
  .status-dot{
    width:10px;
    height:10px;
    border-radius:50%;
  }
  .status-dot.draft{ background:#f59e0b; }
  .status-dot.active{ background:#22c55e; }
  .status-dot.archived{ background:#9ca3af; }

  /* Mini text */
  .mini-label {
    font-size: .8rem;
    color: #6b7280;
  }

  @media (max-width:576px){
    .card-soft-body{ padding:1rem; }
  }
</style>

{{-- ===== JS Format Rupiah (5000 => 5.000) ===== --}}
<script>
  function onlyDigits(str) {
    return (str || '').toString().replace(/\D/g, '');
  }

  function formatRupiah(angka) {
    angka = onlyDigits(angka);
    if (!angka) return '';
    return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  document.addEventListener('DOMContentLoaded', function () {
    const display = document.getElementById('kas_nominal_display');
    const hidden  = document.getElementById('kas_nominal');

    // format awal dari DB/old()
    display.value = formatRupiah(display.value);
    hidden.value  = onlyDigits(hidden.value || display.value);

    display.addEventListener('input', function () {
      const raw = onlyDigits(display.value);

      // simpan angka asli buat backend
      hidden.value = raw;

      // tampilkan yang sudah diformat
      display.value = formatRupiah(raw);
    });
  });
</script>
@endsection
