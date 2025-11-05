@php
    $u = auth()->user();

    // Flag aktif per bagian (atur sesuai nama route kamu)
    $isDashboard   = request()->routeIs('dashboard');
    $isYear        = request()->routeIs('year.*');

    // GURU
    $isApproval    = request()->routeIs('approval.*');

    // BENDAHARA
    $isKasInput    = request()->routeIs('cash.*');
    $isKasPeriod   = request()->routeIs('periods.*');
    $isKasUnpaid   = request()->routeIs('kas.unpaid*');
    $kasOpen       = $isKasInput || $isKasPeriod || $isKasUnpaid;

    $isSpendReq    = request()->routeIs('expense.request.*');
    $spendOpen     = $isSpendReq; // tambah pola lain kalau perlu

    // SISWA
    $isRiwayatSaya = request()->routeIs('student.payments.*');
    $isTransparan  = request()->routeIs('transparansi.*');

    // LAPORAN (guru/bendahara)
    $isReportIndex = request()->routeIs('report.index');
    $isReportExport= request()->routeIs('report.export.*');
    $reportsOpen   = $isReportIndex || $isReportExport;

    // AKUN
    $isProfile     = request()->routeIs('profile.edit');
@endphp

<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">

        {{-- DASHBOARD - semua role --}}
        <li class="nav-item">
            <a class="nav-link {{ $isDashboard ? 'active' : '' }}" href="{{ route('dashboard') }}" aria-current="{{ $isDashboard ? 'page' : 'false' }}">
                <i class="mdi mdi-view-dashboard-outline menu-icon"></i>
                <span class="menu-title">Dashboard</span>
            </a>
        </li>

        {{-- SECTION LABEL --}}
        <li class="nav-item nav-category">Menu Utama</li>

        {{-- =========================
             GURU / WALI KELAS ONLY
           ========================= --}}
        @if($u && $u->hasRole('guru'))
            {{-- Tahun Ajaran / Kelas --}}
            <li class="nav-item">
                <a class="nav-link {{ $isYear ? 'active' : '' }}" href="{{ route('year.index') }}" aria-current="{{ $isYear ? 'page' : 'false' }}">
                    <i class="mdi mdi-office-building-outline menu-icon"></i>
                    <span class="menu-title">Tahun Ajaran & Kelas</span>
                </a>
            </li>

            {{-- ACC Pengeluaran (Guru) --}}
            @php $approvalOpen = $isApproval; @endphp
            <li class="nav-item">
                <a class="nav-link {{ $approvalOpen ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#approval-spend" aria-expanded="{{ $approvalOpen ? 'true' : 'false' }}" aria-controls="approval-spend">
                    <i class="mdi mdi-check-decagram-outline menu-icon"></i>
                    <span class="menu-title">ACC Pengeluaran</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse {{ $approvalOpen ? 'show' : '' }}" id="approval-spend">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            {{-- TODO: ganti "#" ke route('approval.index') --}}
                            <a class="nav-link {{ request()->routeIs('approval.index') ? 'active' : '' }}" href="#">
                                Permintaan Pending
                            </a>
                        </li>
                        <li class="nav-item">
                            {{-- TODO: ganti "#" ke route('approval.history') --}}
                            <a class="nav-link {{ request()->routeIs('approval.history') ? 'active' : '' }}" href="#">
                                Riwayat ACC / Tolak
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        @endif

        {{-- =========================
             BENDAHARA ONLY
           ========================= --}}
        @if($u && $u->hasRole('bendahara'))
            {{-- Kas Mingguan --}}
            <li class="nav-item">
                <a class="nav-link {{ $kasOpen ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#kas" aria-expanded="{{ $kasOpen ? 'true' : 'false' }}" aria-controls="kas">
                    <i class="mdi mdi-cash-multiple menu-icon"></i>
                    <span class="menu-title">Kas Mingguan</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse {{ $kasOpen ? 'show' : '' }}" id="kas">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            {{-- TODO: ganti "#" ke route('cash.index') --}}
                            <a class="nav-link {{ $isKasInput ? 'active' : '' }}" href="#">Input Pembayaran</a>
                        </li>
                        <li class="nav-item">
                            {{-- TODO: ganti "#" ke route('periods.index') --}}
                            <a class="nav-link {{ $isKasPeriod ? 'active' : '' }}" href="#">Periode / Minggu Aktif</a>
                        </li>
                        <li class="nav-item">
                            {{-- TODO: ganti "#" ke route('kas.unpaid') --}}
                            <a class="nav-link {{ $isKasUnpaid ? 'active' : '' }}" href="#">Belum Lunas &amp; Alasan</a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Pengeluaran Kas (Request) --}}
            <li class="nav-item">
                <a class="nav-link {{ $spendOpen ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#spend" aria-expanded="{{ $spendOpen ? 'true' : 'false' }}" aria-controls="spend">
                    <i class="mdi mdi-arrow-top-right-bold-box-outline menu-icon"></i>
                    <span class="menu-title">Pengeluaran Kas</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse {{ $spendOpen ? 'show' : '' }}" id="spend">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            {{-- TODO: ganti "#" ke route('expense.request.index') --}}
                            <a class="nav-link {{ request()->routeIs('expense.request.index') ? 'active' : '' }}" href="#">
                                Ajukan Pengeluaran
                            </a>
                        </li>
                        <li class="nav-item">
                            {{-- TODO: ganti "#" ke route('expense.request.history') --}}
                            <a class="nav-link {{ request()->routeIs('expense.request.history') ? 'active' : '' }}" href="#">
                                Status Pengajuan
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Kategori Pengeluaran --}}
            <li class="nav-item">
                {{-- TODO: ganti "#" ke route('categories.index') --}}
                <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="#">
                    <i class="mdi mdi-shape-outline menu-icon"></i>
                    <span class="menu-title">Kategori Pengeluaran</span>
                </a>
            </li>
        @endif

        {{-- =========================
             SISWA ONLY
           ========================= --}}
        @if($u && $u->hasRole('siswa'))
            <li class="nav-item">
                {{-- TODO: ganti "#" ke route('student.payments.index') --}}
                <a class="nav-link {{ $isRiwayatSaya ? 'active' : '' }}" href="#">
                    <i class="mdi mdi-book-check-outline menu-icon"></i>
                    <span class="menu-title">Riwayat Pembayaran Saya</span>
                </a>
            </li>

            <li class="nav-item">
                {{-- TODO: ganti "#" ke route('transparansi.index') --}}
                <a class="nav-link {{ $isTransparan ? 'active' : '' }}" href="#">
                    <i class="mdi mdi-eye-outline menu-icon"></i>
                    <span class="menu-title">Transparansi Kas Kelas</span>
                </a>
            </li>
        @endif

        {{-- =========================
             LAPORAN (Guru & Bendahara)
           ========================= --}}
        @if($u && ($u->hasRole('guru') || $u->hasRole('bendahara')))
            <li class="nav-item nav-category">Laporan</li>

            <li class="nav-item">
                <a class="nav-link {{ $reportsOpen ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#reports" aria-expanded="{{ $reportsOpen ? 'true' : 'false' }}" aria-controls="reports">
                    <i class="mdi mdi-file-chart-outline menu-icon"></i>
                    <span class="menu-title">Laporan Keuangan</span>
                    <i class="menu-arrow"></i>
                </a>
                <div class="collapse {{ $reportsOpen ? 'show' : '' }}" id="reports">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            {{-- TODO: ganti "#" ke route('report.index') --}}
                            <a class="nav-link {{ $isReportIndex ? 'active' : '' }}" href="#">
                                Ringkasan Kas
                            </a>
                        </li>
                        <li class="nav-item">
                            {{-- TODO: ganti "#" ke route('report.export.pdf') atau export lain --}}
                            <a class="nav-link {{ $isReportExport ? 'active' : '' }}" href="#">
                                Export PDF / Excel
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        @endif

        {{-- =========================
             PENGATURAN AKUN (Semua)
           ========================= --}}
        <li class="nav-item nav-category">Akun</li>

        <li class="nav-item">
            <a class="nav-link {{ $isProfile ? 'active' : '' }}" href="{{ route('profile.edit') }}" aria-current="{{ $isProfile ? 'page' : 'false' }}">
                <i class="mdi mdi-account-circle-outline menu-icon"></i>
                <span class="menu-title">Profil Saya</span>
            </a>
        </li>

        {{-- Logout bisa di navbar/topbar --}}
    </ul>
</nav>
