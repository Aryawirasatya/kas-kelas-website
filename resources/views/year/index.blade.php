@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 980px;">

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
          <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  @endif

  {{-- ========== KARTU: Tahun Ajaran Aktif ========== --}}
  <div class="p-4 rounded-4 shadow-sm bg-white mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div>
        <h5 class="mb-0 fw-semibold">Tahun Ajaran Aktif</h5>
        <div class="text-muted small">
          Satu akun wali kelas hanya mengelola satu kelas per tahun ajaran.
        </div>
      </div>

      @if($activeYear)
        <form method="POST" action="{{ route('year.close', $activeYear) }}"
              onsubmit="return confirm('Tutup tahun ajaran ini? Semua periode akan di-closed.');">
          @csrf
          <button class="btn btn-light rounded-pill">
            <i class="mdi mdi-archive-outline me-1"></i>Tutup & Arsipkan
          </button>
        </form>
      @endif
    </div>

    @if($activeYear)
      <div class="row g-3 small">
        <div class="col-md-3">
          <div class="text-muted">Sekolah</div>
          <div class="fw-semibold">
            {{ $activeYear->school_name ?: '— (belum diisi)' }}
          </div>
        </div>
        <div class="col-md-3">
          <div class="text-muted">Kelas</div>
          <div class="fw-semibold">
            {{ $activeYear->class_label }}
            @if($activeYear->level)
              <span class="text-muted">· {{ $activeYear->level }}</span>
            @endif
          </div>
        </div>
        <div class="col-md-3">
          <div class="text-muted">Tahun Ajaran</div>
          <div class="fw-semibold">
            {{ $activeYear->academic_year }}
          </div>
        </div>
        <div class="col-md-3">
          <div class="text-muted">Nominal / Minggu</div>
          <div class="fw-semibold">
            Rp {{ number_format($activeYear->setting?->kas_nominal ?? 0, 0, ',', '.') }}
          </div>
        </div>
      </div>

      <div class="mt-3 d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary btn-sm rounded-pill"
           href="{{ route('year.setting', $activeYear) }}">
          <i class="mdi mdi-cog-outline me-1"></i>Kelola Pengaturan
        </a>
        <a class="btn btn-outline-dark btn-sm rounded-pill"
           href="{{ route('year.students', $activeYear) }}">
          <i class="mdi mdi-account-group-outline me-1"></i>Siswa &amp; Bendahara
        </a>
      </div>
    @else
      <div class="text-muted small">
        Kamu belum memiliki tahun ajaran aktif. Buat draft baru di bawah, lalu isi siswa & bendahara dan aktifkan.
      </div>
    @endif
  </div>

  {{-- ========== KARTU: Buat Tahun Ajaran Baru ========== --}}
  <div class="p-4 rounded-4 shadow-sm bg-white mb-4">
    <h5 class="mb-3 fw-semibold">Buat Tahun Ajaran Baru (Draft)</h5>
    <p class="small text-muted mb-3">
      Aplikasi ini didesain untuk <strong>1 wali kelas = 1 kelas</strong> per tahun ajaran.
      Kamu bebas mengisi jenjang (SD/SMP/SMA/SMK) sesuai kebutuhan.
    </p>

    <form method="POST" action="{{ route('year.store') }}" class="small">
      @csrf
      <div class="row g-3">
        {{-- Sekolah --}}
        <div class="col-md-6">
          <label class="form-label">Nama Sekolah <span class="text-muted">(opsional)</span></label>
          <input
            name="school_name"
            class="form-control rounded-pill"
            placeholder="contoh: SMKN 1 Cianjur / SDN 2 Sukamaju"
            value="{{ old('school_name') }}"
          >
        </div>

        {{-- Nama Kelas --}}
        <div class="col-md-6">
          <label class="form-label">Nama Kelas <span class="text-danger">*</span></label>
          <input
            name="class_label"
            class="form-control rounded-pill"
            placeholder="contoh: 4B, 8C, RPL 2"
            required
            value="{{ old('class_label') }}"
          >
        </div>

        {{-- Level / Jenjang --}}
        <div class="col-md-6">
          <label class="form-label">Tingkat / Level <span class="text-danger">*</span></label>
          <input
            name="level"
            class="form-control rounded-pill"
            placeholder="contoh: 4 SD, 8 SMP, X SMK, XII SMA"
            required
            value="{{ old('level') }}"
          >
          <div class="form-text">
            Bebas diisi sesuai jenjang. Contoh: <code>4 SD</code>, <code>8 SMP</code>, <code>X SMK</code>.
          </div>
        </div>

        {{-- Tahun Ajaran --}}
        <div class="col-md-6">
          <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
          @php
            $current = now()->year;
            $options = [];
            // contoh: 2024/2025, 2025/2026, 2026/2027, ...
            for ($i = -1; $i <= 3; $i++) {
                $y1 = $current + $i;
                $y2 = $y1 + 1;
                $options[] = "$y1/$y2";
            }
          @endphp
          <input
            name="academic_year"
            list="academicYearOptions"
            class="form-control rounded-pill"
            placeholder="contoh: 2025/2026"
            required
            value="{{ old('academic_year', $options[1] ?? '') }}"
          >
          <datalist id="academicYearOptions">
            @foreach($options as $opt)
              <option value="{{ $opt }}"></option>
            @endforeach
          </datalist>
          <div class="form-text">
            Format: <code>YYYY/YYYY+1</code>. Contoh: <code>2025/2026</code>.
            Satu wali kelas tidak boleh punya tahun ajaran yang sama dua kali.
          </div>
        </div>
      </div>

      <div class="mt-3 d-grid d-md-flex gap-2">
        <button class="btn btn-primary rounded-pill">
          <i class="mdi mdi-playlist-plus me-1"></i>Buat Draft &amp; Lanjut Atur Nominal
        </button>
      </div>
    </form>
  </div>

  {{-- ========== KARTU: Riwayat Tahun Ajaran ========== --}}
  <div class="p-4 rounded-4 shadow-sm bg-white">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <h6 class="mb-0 fw-semibold">Riwayat Tahun Ajaran Kamu</h6>
      <span class="text-muted small">Status: Draft / Active / Archived</span>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="text-muted">
          <tr>
            <th>Sekolah</th>
            <th>Kelas</th>
            <th>Tahun</th>
            <th>Status</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
         <tbody>
          @forelse($years as $y)
            <tr>
              {{-- Nama sekolah --}}
              <td class="small">
                {{ $y->school_name ?: '—' }}
              </td>

              {{-- Kelas & level --}}
              <td>
                <div class="fw-semibold">{{ $y->class_label }}</div>
                @if($y->level)
                  <div class="text-muted small">{{ $y->level }}</div>
                @endif
              </td>

              {{-- Tahun ajaran --}}
              <td>{{ $y->academic_year }}</td>

              {{-- Status --}}
              <td>
                @if($y->status === 'active')
                  <span class="badge bg-success rounded-pill">Active</span>
                @elseif($y->status === 'draft')
                  <span class="badge bg-warning text-dark rounded-pill">Draft</span>
                @else
                  <span class="badge bg-secondary rounded-pill">Archived</span>
                @endif
              </td>

              {{-- Aksi --}}
              <td class="text-end">

                {{-- ====== DRAFT ====== --}}
                @if($y->status === 'draft')

                  <a class="btn btn-sm btn-outline-primary rounded-pill"
                    href="{{ route('year.setting', $y) }}">
                    Nominal
                  </a>

                  <a class="btn btn-sm btn-outline-dark rounded-pill"
                    href="{{ route('year.students', $y) }}">
                    Siswa & Bendahara
                  </a>

                  <form class="d-inline"
                        method="POST"
                        action="{{ route('year.activate', $y) }}"
                        onsubmit="return confirm('Aktifkan tahun ajaran ini? Tahun aktif lain akan diarsipkan.');">
                    @csrf
                    <button class="btn btn-sm btn-success rounded-pill">
                      Aktifkan
                    </button>
                  </form>

                {{-- ====== ACTIVE ====== --}}
                @elseif($y->status === 'active')

                  {{-- Tombol ringkasan --}}
                  <a class="btn btn-sm btn-outline-primary rounded-pill"
                    href="{{ route('year.summary', $y) }}">
                    Ringkasan
                  </a>

                  <form class="d-inline"
                        method="POST"
                        action="{{ route('year.close', $y) }}"
                        onsubmit="return confirm('Tutup & arsipkan tahun ini? Semua periode akan di-closed.');">
                    @csrf
                    <button class="btn btn-sm btn-light rounded-pill">
                      Tutup
                    </button>
                  </form>

                {{-- ====== ARCHIVED ====== --}}
                @else

                  {{-- Archived hanya bisa dilihat ringkasannya --}}
                  <a class="btn btn-sm btn-outline-secondary rounded-pill"
                    href="{{ route('year.summary', $y) }}">
                    Ringkasan
                  </a>

                @endif
              </td>
            </tr>

          @empty
            <tr>
              <td colspan="5" class="text-muted text-center py-4">
                Belum ada tahun ajaran yang kamu buat.
              </td>
            </tr>
          @endforelse
          </tbody>

      </table>
    </div>
  </div>

</div>

<style>
  .alert { font-size: .9rem; }

  .form-control:focus,
  .form-select:focus,
  .btn:focus {
    box-shadow: 0 0 0 .16rem rgba(37,99,235,.15);
    border-color: #2563eb;
  }
</style>
@endsection
