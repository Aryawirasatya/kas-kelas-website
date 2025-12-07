<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Login - Kas Kelas')</title>

    {{-- Favicon --}}
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" />

    {{-- Skydash core CSS (samakan dengan layouts.app kamu) --}}
    <link rel="stylesheet" href="{{ asset('assets/vendors/mdi/css/materialdesignicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/css/vendor.bundle.base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    {{-- Tambahan kecil untuk auth page --}}
    <style>
        body {
            background: #f5f7ff;
        }

        .auth-wrapper {
            min-height: 100vh;
        }

        .auth-card {
            border-radius: 1rem;
        }

        .brand-logo img {
            height: 40px;
        }

        .form-floating > label {
            opacity: .7;
        }
    </style>
</head>
<body>
<div class="container-scroller">
    <div class="container-fluid auth-wrapper d-flex align-items-center justify-content-center">
        @yield('content')
    </div>
</div>

{{-- Skydash core JS --}}
<script src="{{ asset('assets/vendors/js/vendor.bundle.base.js') }}"></script>
<script src="{{ asset('assets/js/off-canvas.js') }}"></script>
<script src="{{ asset('assets/js/hoverable-collapse.js') }}"></script>
<script src="{{ asset('assets/js/misc.js') }}"></script>
</body>
</html>
