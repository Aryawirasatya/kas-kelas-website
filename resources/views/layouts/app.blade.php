<!doctype html>
<html lang="id">
  @include('layouts.partials.header') {{-- <head> + CSS --}}

  <body class="with-welcome-text">
    <div class="container-scroller">
      @include('layouts.partials.navbar')  {{-- NAVBAR fixed-top --}}

      <div class="container-fluid page-body-wrapper">
        @include('layouts.partials.sidebar')  {{-- SIDEBAR, tanpa wrapper ekstra --}}
        <div class="main-panel d-flex flex-column">
          <div class="content-wrapper flex-grow-1">
            @yield('content')
          </div>
          @include('layouts.partials.footer') {{-- footer di DALAM main-panel --}}
        </div>
      </div>
    </div>

  </body>
</html>
