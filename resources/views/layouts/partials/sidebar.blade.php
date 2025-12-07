@php
  /** @var \App\Models\User|null $u */
  $u = auth()->user();

  // ===== Flags route aktif =====
  $isDashboard     = request()->routeIs('dashboard');
  $isYear          = request()->routeIs('year.*');
  $isPeriod        = request()->routeIs('period.index') || request()->routeIs('period.*');

  // Guru – ACC pakai expense-requests.*
  $isApproval      = request()->routeIs('expense-requests.*');

  // Bendahara
  $isKasInput      = request()->routeIs('cash.*');
  $isKasUnpaid     = request()->routeIs('cash.unpaid*'); // nanti diarahkan ke halaman belum lunas
  $kasOpen         = $isKasInput || $isPeriod || $isKasUnpaid;

  // Pengeluaran kas (bendahara & guru)
  $isSpendReq      = request()->routeIs('expense-requests.*');
  $spendOpen       = $isSpendReq;

  // Siswa
  $isRiwayatSaya   = request()->routeIs('student.payments.*');
  $isTransparan    = request()->routeIs('transparansi.*');

  // Laporan (guru, bendahara, siswa)
  $isReportIndex   = request()->routeIs('reports.index');
  $isReportExport  = request()->routeIs('reports.export.*')
                    || request()->routeIs('reports.export.personal.*');
  $reportsOpen     = $isReportIndex || $isReportExport;

  // Akun
  $isProfile       = request()->routeIs('profile.edit');
@endphp

<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">

    {{-- Dashboard (semua role) --}}
    <li class="nav-item">
      <a class="nav-link {{ $isDashboard ? 'active' : '' }}"
         href="{{ route('dashboard') }}"
         aria-current="{{ $isDashboard ? 'page' : 'false' }}">
        <i class="mdi mdi-view-dashboard-outline menu-icon"></i>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>

    <li class="nav-item nav-category">Menu Utama</li>

    {{-- =========================
         GURU / WALI KELAS
       ========================= --}}
    @if($u && $u->hasRole('guru'))
      <li class="nav-item">
        <a class="nav-link {{ $isYear ? 'active' : '' }}"
           href="{{ route('year.index') }}"
           aria-current="{{ $isYear ? 'page' : 'false' }}">
          <i class="mdi mdi-office-building-outline menu-icon"></i>
          <span class="menu-title">Tahun Ajaran & Kelas</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ $isPeriod ? 'active' : '' }}"
           href="{{ route('period.index') }}"
           aria-current="{{ $isPeriod ? 'page' : 'false' }}">
          <i class="mdi mdi-calendar-week-outline menu-icon"></i>
          <span class="menu-title">Periode / Minggu Kas</span>
        </a>
      </li>

      {{-- ACC Pengeluaran (Guru pakai halaman pengajuan yg sama) --}}
      @php $approvalOpen = $isApproval; @endphp
      <li class="nav-item">
        <a class="nav-link {{ $approvalOpen ? '' : 'collapsed' }}"
           data-bs-toggle="collapse"
           href="#approval-spend"
           aria-expanded="{{ $approvalOpen ? 'true' : 'false' }}"
           aria-controls="approval-spend">
          <i class="mdi mdi-check-decagram-outline menu-icon"></i>
          <span class="menu-title">ACC Pengeluaran</span>
          <i class="menu-arrow"></i>
        </a>
        <div class="collapse {{ $approvalOpen ? 'show' : '' }}" id="approval-spend">
          <ul class="nav flex-column sub-menu">
            {{-- Permintaan Pending --}}
            <li class="nav-item">
              <a class="nav-link
                  {{ request()->routeIs('expense-requests.index') && request('status') === 'pending' ? 'active' : '' }}"
                 href="{{ route('expense-requests.index', ['status' => 'pending']) }}">
                Permintaan Pending
              </a>
            </li>

            {{-- Riwayat ACC / Tolak --}}
            <li class="nav-item">
              <a class="nav-link
                  {{ request()->routeIs('expense-requests.index') && in_array(request('status'), ['approved', 'rejected']) ? 'active' : '' }}"
                 href="{{ route('expense-requests.index', ['status' => 'approved']) }}">
                Riwayat ACC / Tolak
              </a>
            </li>
          </ul>
        </div>
      </li>
    @endif

    {{-- =========================
         BENDAHARA
       ========================= --}}
    @if($u && $u->hasRole('bendahara'))
      {{-- Kas mingguan --}}
      <li class="nav-item">
        <a class="nav-link {{ $kasOpen ? '' : 'collapsed' }}"
           data-bs-toggle="collapse"
           href="#kas"
           aria-expanded="{{ $kasOpen ? 'true' : 'false' }}"
           aria-controls="kas">
          <i class="mdi mdi-cash-multiple menu-icon"></i>
          <span class="menu-title">Kas Mingguan</span>
          <i class="menu-arrow"></i>
        </a>
        <div class="collapse {{ $kasOpen ? 'show' : '' }}" id="kas">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link {{ $isKasInput ? 'active' : '' }}"
                 href="{{ route('cash.index') }}">
                Input Pembayaran
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link {{ $isPeriod ? 'active' : '' }}"
                 href="{{ route('period.index') }}">
                Periode / Minggu Aktif
              </a>
            </li>
          </ul>
        </div>
      </li>

      {{-- Pengeluaran kas (pengajuan bendahara) --}}
      <li class="nav-item">
        <a class="nav-link {{ $spendOpen ? '' : 'collapsed' }}"
           data-bs-toggle="collapse"
           href="#spend"
           aria-expanded="{{ $spendOpen ? 'true' : 'false' }}"
           aria-controls="spend">
          <i class="mdi mdi-arrow-top-right-bold-box-outline menu-icon"></i>
          <span class="menu-title">Pengeluaran Kas</span>
          <i class="menu-arrow"></i>
        </a>
        <div class="collapse {{ $spendOpen ? 'show' : '' }}" id="spend">
          <ul class="nav flex-column sub-menu">

            {{-- Halaman ajukan pengeluaran --}}
            <li class="nav-item">
              <a class="nav-link {{ request()->routeIs('expense-requests.create') ? 'active' : '' }}"
                 href="{{ route('expense-requests.create') }}">
                Ajukan Pengeluaran
              </a>
            </li>

            {{-- Halaman daftar/status pengajuan --}}
            <li class="nav-item">
              <a class="nav-link {{ request()->routeIs('expense-requests.index') ? 'active' : '' }}"
                 href="{{ route('expense-requests.index') }}">
                Status Pengajuan
              </a>
            </li>
          </ul>
        </div>
      </li>
    @endif

    {{-- =========================
         SISWA
         (menu khusus siswa kalau nanti ada, misal riwayat sendiri)
       ========================= --}}
    {{-- contoh: nanti bisa ditambah menu lain untuk siswa di sini --}}

    {{-- =========================
         KATEGORI (Guru & Bendahara)
       ========================= --}}
    @if($u && ($u->hasRole('guru') || $u->hasRole('bendahara')))
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"
           href="{{ route('categories.index') }}">
          <i class="mdi mdi-shape-outline menu-icon"></i>
          <span class="menu-title">Kategori Pengeluaran</span>
        </a>
      </li>
    @endif

    {{-- =========================
         LAPORAN (Guru, Bendahara, Siswa)
       ========================= --}}
    @if($u && ($u->hasRole('guru') || $u->hasRole('bendahara') || $u->hasRole('siswa')))
      <li class="nav-item nav-category">Laporan</li>

      <li class="nav-item">
        <a class="nav-link {{ $isReportIndex ? 'active' : '' }}"
           href="{{ route('reports.index') }}"
           aria-current="{{ $isReportIndex ? 'page' : 'false' }}">
          <i class="mdi mdi-file-chart-outline menu-icon"></i>
          <span class="menu-title">Laporan Keuangan</span>
        </a>
      </li>
    @endif

    {{-- =========================
         RIWAYAT AKTIVITAS (hanya Guru & Bendahara)
       ========================= --}}
    @if($u && ($u->hasRole('guru') || $u->hasRole('bendahara')))
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"
           href="{{ route('activity-logs.index') }}">
           <i class="mdi mdi-history menu-icon"></i>
            <span class="menu-title">Riwayat Aktivitas</span>
        </a>
      </li>
    @endif

    {{-- =========================
         AKUN (Semua)
       ========================= --}}
    <li class="nav-item nav-category">Akun</li>
    <li class="nav-item">
      <a class="nav-link {{ $isProfile ? 'active' : '' }}"
         href="{{ route('profile.edit') }}"
         aria-current="{{ $isProfile ? 'page' : 'false' }}">
        <i class="mdi mdi-account-circle-outline menu-icon"></i>
        <span class="menu-title">Profil Saya</span>
      </a>
    </li>

  </ul>
</nav>
