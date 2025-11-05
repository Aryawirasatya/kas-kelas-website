<!doctype html>
<html lang="id">
  @include('layouts.partials.header') {{-- <head> + CSS yang benar --}}

  <body class="with-welcome-text bg-light">
    <div class="container-scroller">
      @include('layouts.partials.navbar')   {{-- NAVBAR fixed-top --}}

      <div class="container-fluid page-body-wrapper">
        @include('layouts.partials.sidebar') {{-- SIDEBAR --}}
        <div class="main-panel d-flex flex-column">
          <div class="content-wrapper flex-grow-1">
            @yield('content')
          </div>

          @include('layouts.partials.footer') {{-- FOOTER --}}
        </div>
      </div>
    </div>

    @stack('scripts') {{-- jika ada script tambahan per-halaman --}}
  </body>
</html>
