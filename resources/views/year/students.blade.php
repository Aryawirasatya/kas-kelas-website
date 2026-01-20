@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:1200px;">
    @php
        $isDraft = $year->status === 'draft';
    @endphp

    {{-- ====== Wizard Mini (Konsisten & Proporsional) ====== --}}
    <nav class="mb-4">
        <ul class="nav nav-pills flex-wrap gap-2 year-wizard">
            @php
                $onSetting  = url()->current() === route('year.setting', $year);
                $onStudents = url()->current() === route('year.students', $year);
            @endphp
            <li class="nav-item">
                <a
                    class="nav-link rounded-pill d-flex align-items-center gap-2 {{ $onSetting ? 'active' : 'wizard-muted' }}"
                    href="{{ route('year.setting', $year) }}"
                >
                    <span class="wizard-step">1</span>
                    <span>Nominal</span>
                </a>
            </li>
            <li class="nav-item">
                <a
                    class="nav-link rounded-pill d-flex align-items-center gap-2 {{ $onStudents ? 'active' : 'wizard-muted' }}"
                    href="{{ route('year.students', $year) }}"
                >
                    <span class="wizard-step">2</span>
                    <span>Siswa & Bendahara</span>
                </a>
            </li>
        </ul>
    </nav>

    {{-- ====== Flash & Error ====== --}}
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

    {{-- ====== Header Ringkas Tahun Ajaran ====== --}}
   <div class="rounded-4 shadow-sm p-3 p-md-4 bg-white mb-4">
    <div class="row g-3 align-items-center">
        {{-- Kiri: info kelas --}}
        <div class="col-md">
            <div class="d-flex align-items-center gap-3">
                <div class="status-dot {{ $year->status }}"></div>
                <div>
                    <div class="fw-semibold">{{ $year->class_label }} ({{ $year->level }})</div>
                    <div class="text-muted small">
                        Tahun: {{ $year->academic_year }}
                        @if($year->homeroom_name)
                            • Wali: {{ $year->homeroom_name }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Kanan: status + tombol (jika draft) --}}
        <div class="col-md-auto">
            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-end gap-2 text-end">
                <span class="badge rounded-pill {{ $isDraft ? 'bg-warning text-dark' : 'bg-success' }}">
                    {{ ucfirst($year->status) }}
                </span>

                @if($isDraft)
                    <form method="POST" action="{{ route('year.activate', $year) }}" class="m-0">
                        @csrf
                        <button class="btn btn-success rounded-pill">
                            Aktifkan Tahun Ajaran
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    </div>


    <div class="row g-4 align-items-start">
        {{-- ========================== KIRI ========================== --}}
        <div class="col-lg-8">

            {{-- ====== Kartu Import & Tambah Manual (auto layout) ====== --}}
            <div class="row g-4">
                @if($isDraft)
                    <div class="col-md-6">
                        <div class="card-soft h-100">
                            <div class="card-soft-body">
                                <h6 class="mb-3">Import Siswa (CSV/EXCELL)</h6>
                                <form method="POST" action="{{ route('year.students.import', $year) }}" enctype="multipart/form-data" class="small">
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <a href="{{ route('year.students.template', $year) }}"
                                            class="btn btn-sm btn-outline-primary rounded-pill">
                                            Download Template Excel
                                        </a>

                                        <a href="{{ route('year.students.template.csv', $year) }}"
                                            class="btn btn-sm btn-outline-secondary rounded-pill">
                                            Download Template CSV
                                        </a>
                                        </div>
                                    @csrf
                                    <div class="mb-3">
                                      <input type="file" name="csv" class="form-control" accept=".xlsx,.xls,.csv" required>
                                      <div class="form-text">
                                        Header wajib: <code>name,email,nis,nisn,gender,password,active</code> (aktif: 1/0). Boleh CSV/Excel.
                                      </div>
                                    </div>
                                    <button class="btn btn-secondary rounded-pill">Import</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Dinamis: kalau import nggak ada (Active), ini melebar col-12 --}}
                <div class="{{ $isDraft ? 'col-md-6' : 'col-12' }}">
                    <div class="card-soft h-100">
                        <div class="card-soft-body">
                            <h6 class="mb-3">Tambah Siswa Manual</h6>
                            <form method="POST" action="{{ route('year.students.manual', $year) }}" class="small">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Nama</label>
                                        <input name="name" class="form-control rounded-pill" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control rounded-pill" required>
                                    </div>

                                    {{-- NISN (WAJIB 10 DIGIT) --}}
                                    <div class="col-md-6">
                                        <label class="form-label">NISN</label>
                                        <input
                                            name="nisn"
                                            class="form-control rounded-pill"
                                            placeholder="10 digit NISN"
                                            inputmode="numeric"
                                            pattern="[0-9]{10}"
                                            minlength="10"
                                            maxlength="10"
                                            required
                                        >
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">NIS (opsional)</label>
                                        <input name="nis" class="form-control rounded-pill" placeholder="Nomor Induk Sekolah">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Gender</label>
                                        <select name="gender" class="form-select rounded-pill">
                                            <option value="">(Tidak diisi)</option>
                                            <option value="L">L</option>
                                            <option value="P">P</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Password</label>
                                        <input
                                            type="password"
                                            name="password"
                                            class="form-control rounded-pill"
                                            placeholder="Minimal 6 karakter"
                                            required
                                        >
                                    </div>
                                </div>
                                <div class="mt-2 d-grid d-md-inline-block">
                                    <button class="btn btn-outline-primary rounded-pill">Tambah</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            {{-- >>> Perubahan: Kartu "Daftar Siswa" DIPINDAH ke baris terpisah full-width di bawah (lihat col-12 di akhir) <<< --}}
        </div>

        {{-- ========================== KANAN ========================== --}}
        <div class="col-lg-4">
            <div class="card-soft {{ $isDraft ? 'position-sticky' : '' }}" style="{{ $isDraft ? 'top:84px' : '' }}">
                <div class="card-soft-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Bendahara Kelas</h6>
                        <span class="badge rounded-pill bg-light text-muted border">1–2 orang</span>
                    </div>

                    <form method="POST" action="{{ route('year.treasurers', $year) }}" id="treasurerForm" class="small">
                        @csrf

                        {{-- Hidden nilai final --}}
                        <input type="hidden" name="treasurer_1_id" id="treasurer_1_id" value="">
                        <input type="hidden" name="treasurer_2_id" id="treasurer_2_id" value="">

                        <div class="picker-slot">
                            <div>
                                <div class="text-muted small">Bendahara 1</div>
                                <div id="slot1Label" class="fw-semibold">— belum dipilih —</div>
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary rounded-pill"
                                data-bs-toggle="modal"
                                data-bs-target="#treasurerPickerModal"
                                data-slot="1"
                            >Pilih / Ubah</button>
                        </div>

                        <div class="picker-slot">
                            <div>
                                <div class="text-muted small">Bendahara 2</div>
                                <div id="slot2Label" class="fw-semibold">— kosong —</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary rounded-pill"
                                    data-bs-toggle="modal"
                                    data-bs-target="#treasurerPickerModal"
                                    data-slot="2"
                                >Pilih / Ubah</button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-dark rounded-pill"
                                    id="clearSlot2Btn"
                                >Kosongkan</button>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-outline-primary rounded-pill">
                                Simpan Bendahara
                            </button>
                        </div>
                    </form>


                </div>
            </div>
        </div>

        {{-- ========================== BARIS PENUH: DAFTAR SISWA ========================== --}}
        <div class="col-12">
            <div class="card-soft mt-0">
                <div class="card-soft-body">
                    <div class="row g-2 align-items-center mb-3">
                        <div class="col">
                            <h6 class="mb-0">Daftar Siswa</h6>
                        </div>
                        <div class="col-12 col-md d-flex">
                            @if($isDraft)
                                {{-- Draft: bulk action --}}
                                <form
                                    id="bulkForm"
                                    method="POST"
                                    action="{{ route('year.students.bulk', $year) }}"
                                    class="d-flex align-items-center gap-2 w-100 flex-wrap"
                                >
                                    @csrf
                                    <div id="bulkHiddenInputs"></div>
                                    <select name="action" class="form-select form-select-sm rounded-pill" required>
                                        <option value="" selected disabled>Aksi massal…</option>
                                        <option value="activate">Aktifkan</option>
                                        <option value="deactivate">Nonaktifkan</option>
                                        <option value="remove">Hapus dari tahun</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-dark rounded-pill">Jalankan</button>
                                </form>
                            @else
                                {{-- Active: Search + Filter --}}
                            <form method="GET" class="d-flex align-items-center gap-2 w-100 flex-wrap" id="studentSearchForm">
                                <div class="input-group input-group-sm flex-grow-1 shadow-none search-soft">
                                    <button
                                        type="button"
                                        id="studentSearchBtn"
                                        class="input-group-text rounded-start-pill btn p-0 px-3 border-0 bg-transparent text-muted"
                                        title="Cari (Enter)"
                                        aria-label="Cari"
                                    >
                                        <i class="mdi mdi-magnify"></i>
                                    </button>
                                    <input
                                        type="search"
                                        id="mainSearch"
                                        name="q"
                                        value="{{ request('q') }}"
                                        class="form-control border-0 rounded-0"
                                        placeholder="Cari nama / email / NIS / NISN"
                                        autocomplete="off"
                                    >
                                    <span class="input-group-text border-0 bg-transparent px-2">
                                        <span class="vr vr-soft"></span>
                                    </span>
                                    <div class="form-floating-select">
                                        <select
                                            name="only"
                                            class="form-select form-select-sm border-0 rounded-end-pill px-3 py-2"
                                            onchange="this.form.submit()"
                                            aria-label="Filter status siswa"
                                        >
                                            <option value=""  {{ request('only')==='' ? 'selected' : '' }}>Semua status</option>
                                            <option value="active"    {{ request('only')==='active' ? 'selected' : '' }}>Aktif</option>
                                            <option value="inactive"  {{ request('only')==='inactive' ? 'selected' : '' }}>Nonaktif</option>
                                            <option value="treasurer" {{ request('only')==='treasurer' ? 'selected' : '' }}>Bendahara</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Tombol Terapkan (opsional, sebagai fallback). Disembunyikan di mobile. --}}
                                <button class="btn btn-sm btn-soft-primary rounded-pill d-none d-md-inline-flex">
                                    Terapkan
                                </button>
                            </form>

                            @endif
                        </div>
                    </div>

                    {{-- ====== Tabel Siswa (FULL WIDTH) ====== --}}
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 w-100">
                            <thead class="text-muted bg-light">
                                <tr>
                                    @if($isDraft)
                                        <th style="width:26px;">
                                            <input type="checkbox" id="checkAll" aria-label="Pilih semua">
                                        </th>
                                    @endif
                                    <th style="width:40px;">#</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>NIS</th>
                                    <th>NISN</th>
                                    <th>Gender</th>
                                    <th>Aktif</th>
                                    <th>Bendahara</th>
                                    <th class="text-end" style="width:{{ $isDraft ? '220px' : '70px' }};">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($enrolls as $i => $en)
                                    <tr
                                        data-enroll-id="{{ $en->id }}"
                                        data-name="{{ e($en->user->name) }}"
                                        data-email="{{ e($en->user->email) }}"
                                        data-nis="{{ e($en->nis) }}"
                                        data-nisn="{{ e($en->user->nisn) }}"
                                        data-gender="{{ e($en->user->gender) }}"
                                        data-fulltext="{{ strtolower(
                                            trim(($en->user->name ?? '').' '.
                                                ($en->user->email ?? '').' '.
                                                ($en->nis ?? '').' '.
                                                ($en->user->nisn ?? '').' '.
                                                ($en->user->gender ?? ''))
                                        ) }}"
                                    >
                                        @if($isDraft)
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    class="row-check"
                                                    value="{{ $en->id }}"
                                                    aria-label="Pilih {{ $en->user->name }}"
                                                >
                                            </td>
                                        @endif

                                        <td>{{ ($enrolls->currentPage()-1)*$enrolls->perPage() + $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $en->user->name }}</td>
                                        <td class="text-muted small">{{ $en->user->email }}</td>
                                        <td>{{ $en->nis ?? '—' }}</td>
                                        <td>{{ $en->user->nisn ?? '—' }}</td>
                                        <td>{{ $en->user->gender ?? '—' }}</td>
                                        <td>
                                            @if($en->is_active)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($en->is_treasurer)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Bendahara</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-none d-sm-inline-flex flex-wrap gap-2 justify-content-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary rounded-pill btn-edit"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editStudentModal"
                                                >Edit</button>

                                                <form
                                                    method="POST"
                                                    action="{{ route('year.enrollment.toggle', [$year,$en]) }}"
                                                    class="d-inline"
                                                >
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-secondary rounded-pill">
                                                        {{ $en->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>

                                                <form
                                                    method="POST"
                                                    action="{{ route('year.enrollment.destroy', [$year,$en]) }}"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Keluarkan dari tahun ini?')"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger rounded-pill">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>

                                            {{-- Dropdown versi mobile --}}
                                            <div class="dropdown d-sm-none">
                                                <button
                                                    class="btn btn-sm btn-light border rounded-pill dropdown-toggle"
                                                    type="button"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false"
                                                >
                                                    Aksi
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item btn-edit"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editStudentModal"
                                                        >Edit</button>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('year.enrollment.toggle', [$year,$en]) }}">
                                                            @csrf
                                                            <button class="dropdown-item">
                                                                {{ $en->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('year.enrollment.destroy', [$year,$en]) }}"
                                                            onsubmit="return confirm('Keluarkan dari tahun ini?')"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="dropdown-item text-danger">Hapus</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isDraft ? 11 : 10 }}" class="text-center py-5">
                                            <div class="text-muted mb-2">Belum ada siswa di tahun ini.</div>
                                            <a
                                                href="#"
                                                onclick="document.querySelector('input[name=name]')?.focus()"
                                                class="btn btn-sm btn-outline-primary rounded-pill"
                                            >Tambah Siswa Sekarang</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $enrolls->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
        {{-- ========================== /BARIS PENUH ========================== --}}
    </div>
    </div>

 {{-- ========================== Modal: Edit Siswa (dengan password editable) ========================== --}}
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" id="editForm">
            @csrf
            @method('PATCH')

            <div class="modal-header">
                <h6 class="modal-title">Edit Siswa</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3 small">
                    {{-- Nama --}}
                    <div class="col-12">
                        <label for="ed_name" class="form-label mb-1">Nama</label>
                        <input id="ed_name" type="text" name="name" class="form-control rounded-pill" required
                               autocomplete="name" placeholder="Nama lengkap siswa">
                        @error('name')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    {{-- Email --}}
                    <div class="col-12">
                        <label for="ed_email" class="form-label mb-1">Email</label>
                        <input id="ed_email" type="email" name="email" class="form-control rounded-pill" required
                               autocomplete="email" inputmode="email" placeholder="email@sekolah.id">
                        @error('email')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    {{-- NISN + NIS --}}
                    <div class="col-md-6">
                        <label for="ed_nisn" class="form-label mb-1">NISN</label>
                        <input
                            id="ed_nisn"
                            type="text"
                            name="nisn"
                            class="form-control rounded-pill"
                            placeholder="10 digit NISN"
                            inputmode="numeric"
                            pattern="[0-9]{10}"
                            minlength="10"
                            maxlength="10"
                            required
                        >
                        @error('nisn')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="ed_nis" class="form-label mb-1">NIS (opsional)</label>
                        <input id="ed_nis" type="text" name="nis" class="form-control rounded-pill"
                               inputmode="numeric" pattern="[0-9]*" maxlength="12" placeholder="Nomor Induk Sekolah">
                        @error('nis')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="ed_gender" class="form-label mb-1">Gender</label>
                        <select id="ed_gender" name="gender" class="form-select rounded-pill">
                            <option value="">(Tidak diisi)</option>
                            <option value="L">L</option>
                            <option value="P">P</option>
                        </select>
                        @error('gender')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    {{-- Password (opsional) --}}
                    <div class="col-12">
                        <label for="editStudentPassword" class="form-label mb-1">
                            Password <small class="text-muted">(kosongkan jika tidak ingin mengubah)</small>
                        </label>
                        <div class="input-group input-group-merge">
                            <input
                                type="password"
                                name="password"
                                id="editStudentPassword"
                                class="form-control rounded-start-pill"
                                placeholder="Minimal 6 karakter"
                                minlength="6"
                                autocomplete="new-password"
                                aria-describedby="ed_pwd_help"
                            >
                            <button class="btn btn-outline-primary rounded-end-pill" type="button" id="toggleEditPwd">
                                Tampilkan
                            </button>
                        </div>
                        <div id="ed_pwd_help" class="form-text">
                            Isi hanya jika ingin mereset password siswa.
                        </div>
                        @error('password')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light rounded-pill" data-bs-dismiss="modal" type="button">Batal</button>
                <button class="btn btn-primary rounded-pill" id="editFormSubmitBtn" type="submit">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>



{{-- ========================== Modal: Picker Bendahara ========================== --}}
<div class="modal fade" id="treasurerPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Pilih Siswa</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                 <div id="pickerList" class="list-group">
                    @foreach($pickerEnrolls as $en)
                      @php
                        $nm   = $en->user->name ?? '';
                        $em   = $en->user->email ?? '';
                        $nis  = $en->nis ?? '';
                        $nisn = $en->user->nisn ?? '';
                        $g    = $en->user->gender ?? '';
                        $fulltext = strtolower(trim("$nm $em $nis $nisn $g"));
                      @endphp

                      <label
                        class="list-group-item list-group-item-action d-flex align-items-center justify-content-between picker-item"
                        data-id="{{ $en->id }}"
                        data-name="{{ $nm }}"
                        data-fulltext="{{ $fulltext }}"
                      >
                        <div class="me-3">
                          <div class="fw-semibold">{{ $nm }}</div>
                          <div class="text-muted small">
                            {{ $em }}
                            @if($nis)  · NIS: {{ $nis }} @endif
                            @if($nisn) · NISN: {{ $nisn }} @endif
                            @if($g)    · {{ $g }} @endif
                          </div>
                        </div>

                        <input
                          type="radio"
                          name="pickerRadio"
                          class="form-check-input mt-0"
                          aria-label="pilih {{ $nm }}"
                        >
                      </label>
                    @endforeach
                  </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary rounded-pill" id="pickerChooseBtn" disabled>Pilih</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  // ===== Helper =====
  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  // ===================== Modal EDIT siswa (populate + password opsional) =====================
  const editForm        = $('#editForm');
  const editPwd         = editForm?.querySelector('#editStudentPassword');
  const toggleEditPwd   = editForm?.querySelector('#toggleEditPwd');
  const editSubmitBtn   = editForm?.querySelector('#editFormSubmitBtn');

  $$('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
      const tr     = btn.closest('tr'); if (!tr || !editForm) return;
      const id     = tr.dataset.enrollId;
      const name   = tr.dataset.name   || '';
      const email  = tr.dataset.email  || '';
      const nis    = tr.dataset.nis    || '';
      const nisn   = tr.dataset.nisn   || '';
      const gender = tr.dataset.gender || '';

      // set action (replace placeholder with id)
      editForm.action = "{{ route('year.enrollment.update', [$year, '___ID___']) }}".replace('___ID___', id);

      // populate
      editForm.querySelector('[name=name]').value   = name;
      editForm.querySelector('[name=email]').value  = email;
      editForm.querySelector('[name=nis]').value    = nis;
      editForm.querySelector('[name=nisn]').value   = nisn;
      editForm.querySelector('[name=gender]').value = gender;

      // reset state password
      if (editPwd)  { editPwd.value = ''; editPwd.type = 'password'; }
      if (toggleEditPwd) toggleEditPwd.textContent = 'Tampilkan';
      if (editSubmitBtn) { editSubmitBtn.disabled = false; editSubmitBtn.textContent = 'Simpan Perubahan'; }
    });
  });

  // toggle show/hide password
  toggleEditPwd?.addEventListener('click', () => {
    if (!editPwd) return;
    const isPwd = editPwd.type === 'password';
    editPwd.type = isPwd ? 'text' : 'password';
    toggleEditPwd.textContent = isPwd ? 'Sembunyikan' : 'Tampilkan';
    editPwd.focus();
  });

  // prevent double submit pada edit form
  editForm?.addEventListener('submit', () => {
    if (editSubmitBtn) {
      editSubmitBtn.disabled = true;
      editSubmitBtn.textContent = 'Memproses…';
    }
  });

  // ===================== Bulk (draft) =====================
  const bulkHiddenCt = $('#bulkHiddenInputs');
  const checkAll     = $('#checkAll');
  const rowChecks    = $$('.row-check');
  function rebuildBulkIds() {
    if (!bulkHiddenCt) return;
    bulkHiddenCt.innerHTML = '';
    rowChecks.filter(r => r.checked).forEach(r => {
      const i = document.createElement('input');
      i.type = 'hidden'; i.name = 'ids[]'; i.value = r.value;
      bulkHiddenCt.appendChild(i);
    });
  }
  if (checkAll) {
    checkAll.addEventListener('change', () => {
      rowChecks.forEach(r => r.checked = checkAll.checked);
      rebuildBulkIds();
    });
    rowChecks.forEach(r => r.addEventListener('change', rebuildBulkIds));
  }

  // ===================== Picker Bendahara =====================
  const slot1Label      = $('#slot1Label');
  const slot2Label      = $('#slot2Label');
  const inp1            = $('#treasurer_1_id');
  const inp2            = $('#treasurer_2_id');
  const clear2Btn       = $('#clearSlot2Btn');

  const modalEl         = $('#treasurerPickerModal');
  const pickerList      = $('#pickerList');
  const pickerChooseBtn = $('#pickerChooseBtn');

  let activeSlot = 1; // 1 atau 2

  function mapPickerNames() {
    const map = {};
    $$('.picker-item', pickerList).forEach(item => map[item.dataset.id] = item.dataset.name);
    return map;
  }
  function refreshSlotLabels() {
    const m = mapPickerNames();
    if (slot1Label) slot1Label.textContent = inp1?.value ? (m[inp1.value] || ('ID ' + inp1.value)) : '— belum dipilih —';
    if (slot2Label) slot2Label.textContent = inp2?.value ? (m[inp2.value] || ('ID ' + inp2.value)) : '— kosong —';
  }
  function resetPickerUi(disableThisId) {
    if (pickerChooseBtn) pickerChooseBtn.disabled = true;
    $$('.picker-item', pickerList).forEach(item => {
      const id    = item.dataset.id;
      const radio = item.querySelector('input[type=radio]');
      if (radio) { radio.checked = false; radio.disabled = false; }
      item.classList.remove('disabled');
      item.style.display = '';
      if (disableThisId && id === disableThisId) {
        if (radio) radio.disabled = true;
        item.classList.add('disabled');
      }
    });
  }
  function hideModalSafe() {
    try {
      const B = window.bootstrap;
      if (B && typeof B.Modal?.getInstance === 'function') {
        const inst = B.Modal.getInstance(modalEl) || B.Modal.getOrCreateInstance(modalEl);
        inst?.hide?.(); return;
      }
    } catch (_) {}
    modalEl?.querySelector('[data-bs-dismiss="modal"], .btn-close')?.click();
  }

  $$('.btn[data-bs-target="#treasurerPickerModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
      activeSlot = parseInt(btn.dataset.slot || '1', 10);
      const otherId = (activeSlot === 1) ? (inp2?.value || null) : (inp1?.value || null);
      resetPickerUi(otherId);
    });
  });

  // Klik item list → pilih radio
  pickerList?.addEventListener('click', (e) => {
    const item  = e.target.closest('.picker-item');
    if (!item) return;
    const radio = item.querySelector('input[type=radio]');
    if (radio && !radio.disabled) {
      radio.checked = true;
      if (pickerChooseBtn) pickerChooseBtn.disabled = false;
    }
  });

  // Konfirmasi pilih bendahara
  pickerChooseBtn?.addEventListener('click', () => {
    const checked = pickerList?.querySelector('input[type=radio]:checked');
    if (!checked) return;
    const item     = checked.closest('.picker-item');
    const chosenId = item?.dataset.id;
    if (!chosenId) return;

    if (activeSlot === 1) {
      if (inp2?.value && inp2.value === chosenId) { alert('Bendahara 1 dan Bendahara 2 tidak boleh sama.'); return; }
      if (inp1) inp1.value = chosenId;
    } else {
      if (inp1?.value && inp1.value === chosenId) { alert('Bendahara 1 dan Bendahara 2 tidak boleh sama.'); return; }
      if (inp2) inp2.value = chosenId;
    }
    refreshSlotLabels();
    hideModalSafe();
  });

  // Tombol "Kosongkan" Bendahara 2
  clear2Btn?.addEventListener('click', () => {
    if (inp2) inp2.value = '';
    refreshSlotLabels();
  });

  // Inisialisasi label slot dari server (bendahara tersimpan)
  (function initTreasurersFromServer() {
    @php
      $treasurers = ($pickerEnrolls ?? collect())->where('is_treasurer', true)->values();
      $t1 = $treasurers[0]->id ?? null;
      $t2 = $treasurers[1]->id ?? null;
    @endphp
    @if(isset($t1)) if (inp1) inp1.value = '{{ $t1 }}'; @endif
    @if(isset($t2)) if (inp2) inp2.value = '{{ $t2 }}'; @endif
    refreshSlotLabels();
  })();

  // ===================== Search tabel utama (client-side) =====================
  const mainSearch       = $('#mainSearch');
  const studentSearchBtn = $('#studentSearchBtn');
  const tbody            = document.querySelector('table tbody');
  function filterTable() {
    const q = (mainSearch?.value || '').trim().toLowerCase();
    $$('#tbody tr[data-fulltext], tr[data-fulltext]', tbody).forEach(tr => {
      const ft = (tr.dataset.fulltext || '').toLowerCase();
      tr.style.display = ft.includes(q) ? '' : 'none';
    });
  }
  mainSearch?.addEventListener('input', filterTable);
  mainSearch?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); filterTable(); } });
  studentSearchBtn?.addEventListener('click', filterTable);
});
</script>
@endpush

{{-- ====== Style kecil (palet & konsistensi) ====== --}}
<style>
    :root{
        --soft-bg: #f7f8fa;
        --soft-border: #e9ecf1;
        --soft-text: #6b7280;
        --soft-primary: #2563eb;
    }

    .alert-soft { border: 1px solid var(--soft-border); }

    .card-soft { background:#fff; border:1px solid var(--soft-border); border-radius:1rem; }
    .card-soft-body { padding:1.25rem; }

    .picker-slot{
        border:1px solid var(--soft-border);
        border-radius:1rem;
        padding:0.9rem 1rem;
        margin-bottom:0.75rem;
        display:flex; align-items:center; justify-content:space-between;
        gap:0.75rem;
    }

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

    /* Subtle badges */
    .bg-success-subtle{ background:#eaf7ee; }
    .bg-secondary-subtle{ background:#f2f4f7; }
    .bg-primary-subtle{ background:#e6eeff; }
    .border-success-subtle{ border-color:#c7ecd3 !important; }
    .border-secondary-subtle{ border-color:#e6e9ef !important; }
    .border-primary-subtle{ border-color:#ccdcff !important; }

    /* Focus */
    .form-control:focus, .form-select:focus, .btn:focus{
        box-shadow:0 0 0 .2rem rgba(37,99,235,.15);
        border-color:var(--soft-primary);
    }

    /* Rapihin input-group untuk tombol show/hide password */
.input-group.input-group-merge .form-control.rounded-start-pill {
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
}
.input-group.input-group-merge .btn.rounded-end-pill {
  border-top-left-radius: 0 !important;
  border-bottom-left-radius: 0 !important;
}

/* ====== Search + Filter Soft UI ====== */
.search-soft{
    background:#fff;
    border:1px solid var(--soft-border, #e8edf4);
    border-radius:999px;
    padding:2px;
}
.search-soft:focus-within{
    box-shadow:0 0 0 .2rem rgba(37,99,235,.12);
    border-color:var(--soft-primary, #2563eb);
}
.search-soft .form-control{
    border-radius:0;
}
.search-soft .form-control:focus{ box-shadow:none; }
.search-soft .input-group-text{
    color:#6b7280;
}
.search-soft .form-select{
    background-color:transparent;
    background-image:none;
}
.form-floating-select{
    min-width: 190px;
}
.vr-soft{
    display:inline-block; width:1px; height:20px; background:#e5e9f0;
}
@media (max-width: 576px){
    .form-floating-select{ min-width: 160px; }
}

/* Konsistensi jarak label */
.form-label.mb-1 { margin-bottom: .35rem !important; }

/* Buat form kecil tetap nyaman di modal */
#editStudentModal .modal-body .form-control,
#editStudentModal .modal-body .form-select {
  min-height: 40px;
}

/* ====== Custom Pagination Soft UI ====== */
.pagination {
    gap: .4rem;
}

.page-item .page-link {
    border-radius: 50rem !important;
    padding: .45rem .85rem;
    border: 1px solid #e5e9f0;
    color: #374151;
    font-size: .85rem;
    background: #ffffff;
    transition: all .15s ease;
}

.page-item .page-link:hover {
    background: rgba(37,99,235,.08);
    color: #2563eb;
    border-color: rgba(37,99,235,.25);
}

.page-item.active .page-link {
    background: #2563eb;
    border-color: #2563eb;
    color: white;
    font-weight: 600;
    box-shadow: 0 4px 10px rgba(37,99,235,.25);
}

.page-item.disabled .page-link {
    opacity: .4;
    background: #f3f4f6;
    border-color: #e5e7eb;
}

.page-link:focus {
    box-shadow: 0 0 0 .2rem rgba(37,99,235,.20) !important;
}

    /* Sticky hanya saat draft supaya tidak bikin gap jika isinya pendek */
    @media (max-width: 576px){
        .card-soft-body{ padding:1rem; }
    }

    .picker-item.disabled { opacity:.55; pointer-events:none; }
</style>
@endsection
