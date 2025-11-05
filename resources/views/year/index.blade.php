@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 980px;">

  @if(session('success'))
    <div class="alert alert-success rounded-4">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger rounded-4">
      <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  {{-- Tahun aktif --}}
  <div class="p-4 rounded-4 shadow-sm bg-white mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h5 class="mb-0">Tahun Ajaran Aktif</h5>
      @if($activeYear)
        <form method="POST" action="{{ route('year.close', $activeYear) }}" onsubmit="return confirm('Tutup tahun ajaran ini?')">
          @csrf
          <button class="btn btn-light rounded-pill">Tutup Tahun</button>
        </form>
      @endif
    </div>

    @if($activeYear)
      <div class="row g-3">
        <div class="col-md-3">
          <div class="text-muted small">Kelas</div>
          <div>{{ $activeYear->class_label }} ({{ $activeYear->level }})</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Tahun</div>
          <div>{{ $activeYear->academic_year }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Wali</div>
          <div>{{ $activeYear->homeroom_name ?? '-' }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Nominal/Minggu</div>
          <div>Rp {{ number_format($activeYear->setting?->kas_nominal ?? 0,0,',','.') }}</div>
        </div>
      </div>
      <div class="mt-3">
        <a class="btn btn-outline-primary btn-sm rounded-pill" href="{{ route('year.setting', $activeYear) }}">Kelola Year Aktif</a>
      </div>
    @else
      <div class="text-muted">Belum ada tahun aktif.</div>
    @endif
  </div>

  {{-- Buat draft --}}
  <div class="p-4 rounded-4 shadow-sm bg-white mb-4">
    <h5 class="mb-3">Buat Tahun Ajaran Baru (Draft)</h5>
    <form method="POST" action="{{ route('year.store') }}" class="small">
      @csrf
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Nama Kelas</label>
          <input name="class_label" class="form-control rounded-pill" placeholder="RPL 2" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Tingkat</label>
          <select name="level" class="form-select rounded-pill" required>
            <option value="X">X</option>
            <option value="XI">XI</option>
            <option value="XII">XII</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Tahun Ajaran</label>
          @php
            $startYear = now()->year; $opsi = [];
            for ($i=0; $i<5; $i++){ $y1 = $startYear + $i; $y2 = $y1 + 1; $opsi[] = "$y1/$y2"; }
          @endphp
          <select name="academic_year" class="form-select rounded-pill" required>
            @foreach($opsi as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
          </select>
          <div class="form-text">Format YYYY/YYYY+1, unik.</div>
        </div>
      </div>
      <div class="mt-3 d-grid">
        <button class="btn btn-primary rounded-pill">Buat Draft & Lanjut</button>
      </div>
    </form>
  </div>

  {{-- Riwayat --}}
  <div class="p-4 rounded-4 shadow-sm bg-white">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h6 class="mb-0">Riwayat Tahun Ajaran</h6>
      <span class="text-muted small">Active / Draft / Archived</span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead class="text-muted">
          <tr>
            <th>Kelas</th><th>Tahun</th><th>Wali</th><th>Status</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($years as $y)
            <tr>
              <td>{{ $y->class_label }} ({{ $y->level }})</td>
              <td>{{ $y->academic_year }}</td>
              <td>{{ $y->homeroom_name ?? '-' }}</td>
              <td>
                @if($y->status==='active')
                  <span class="badge bg-success rounded-pill">Active</span>
                @elseif($y->status==='draft')
                  <span class="badge bg-warning text-dark rounded-pill">Draft</span>
                @else
                  <span class="badge bg-secondary rounded-pill">Archived</span>
                @endif
              </td>
              <td>
                @if($y->status==='draft')
                  <a class="btn btn-sm btn-outline-primary rounded-pill" href="{{ route('year.setting', $y) }}">Atur Nominal</a>
                  <a class="btn btn-sm btn-outline-dark rounded-pill" href="{{ route('year.students', $y) }}">Siswa & Bendahara</a>
                  <form class="d-inline" method="POST" action="{{ route('year.periods.generate', $y) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary rounded-pill">Generate Periode</button>
                  </form>
                  <form class="d-inline" method="POST" action="{{ route('year.activate', $y) }}" onsubmit="return confirm('Aktifkan?')">
                    @csrf
                    <button class="btn btn-sm btn-success rounded-pill">Aktifkan</button>
                  </form>
                @elseif($y->status==='active')
                  <form class="d-inline" method="POST" action="{{ route('year.close', $y) }}" onsubmit="return confirm('Tutup tahun ini?')">
                    @csrf
                    <button class="btn btn-sm btn-light rounded-pill">Tutup</button>
                  </form>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-muted">Belum ada data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
