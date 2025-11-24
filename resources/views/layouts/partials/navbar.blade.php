{{-- partial:partials/_navbar.html --}}
@php
  $u = auth()->user();
@endphp

<nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
  <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
    <div class="me-3">
      <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
        <span class="icon-menu"></span>
      </button>
    </div>
    <div>
      <a class="navbar-brand brand-logo" href="{{ route('dashboard') }}">
        <img src="/assets/images/poris-1.png" alt="logo" />
      </a>
      <a class="navbar-brand brand-logo-mini" href="{{ route('dashboard') }}">
        <img src="/assets/images/poris-1.png" alt="logo" />
      </a>
    </div>
  </div>

  <div class="navbar-menu-wrapper d-flex align-items-top">
    <ul class="navbar-nav">
      <li class="nav-item fw-semibold d-none d-lg-block ms-0">
        @auth
          <h4 class="welcome-text">
            {{-- bisa diganti kalimat sapaan kalau mau --}}
            <span class="text-black fw-bold">{{ $u->name }}</span>
          </h4>
          {{-- contoh sub-text, boleh diaktifkan kalau mau --}}
          {{-- <h4 class="welcome-sub-text">Siap cek uang kas?</h4> --}}
        @endauth
      </li>
    </ul>

    <ul class="navbar-nav ms-auto">
      <li class="nav-item dropdown d-none d-lg-block user-dropdown">
        <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary"
               style="width:42px; height:42px;">
            <i class="mdi mdi-account-circle fs-5"></i>
          </div>
        </a>

        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
          <div class="dropdown-header text-center">
            <p class="mb-1 mt-3 fw-semibold">{{ optional($u)->name }}</p>
            <p class="fw-light text-muted mb-0">{{ optional($u)->email }}</p>
          </div>

          {{-- contoh menu profile kalau mau ditambah nanti --}}
          {{-- <a class="dropdown-item">
            <i class="dropdown-item-icon mdi mdi-account-outline text-primary me-2"></i> My Profile
          </a> --}}

          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item d-flex align-items-center">
              <i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>
              <span>Sign Out</span>
            </button>
          </form>
        </div>
      </li>
    </ul>

    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center"
            type="button" data-bs-toggle="offcanvas">
      <span class="mdi mdi-menu"></span>
    </button>
  </div>
</nav>
