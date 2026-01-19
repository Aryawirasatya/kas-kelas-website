@extends('layouts.guest')

@section('title', 'Login Kas Kelas')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5">

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">

                    {{-- Header --}}
                    <div class="text-center mb-4">
                        <div class="fw-bold fs-4">Kas Kelas</div>
                        <div class="text-muted small mt-1">
                            Masuk untuk melanjutkan
                        </div>
                    </div>

                    {{-- Status (contoh: reset password link sent) --}}
                    @if (session('status'))
                        <div class="alert alert-success small py-2 mb-3" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Error global --}}
                    @if ($errors->has('email') || $errors->has('password'))
                        <div class="alert alert-danger small py-2 mb-3" role="alert">
                            {{ $errors->first('email') ?: $errors->first('password') }}
                        </div>
                    @endif

                    {{-- FORM LOGIN --}}
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold">Email</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="nama@gmail.com"
                            >
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="mb-2">
                            <label for="password" class="form-label small fw-semibold">Password</label>

                            <div class="input-group">
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                >

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    id="togglePassword"
                                    aria-label="Toggle password"
                                >
                                    <span id="toggleIcon">👁</span>
                                </button>
                            </div>

                            @error('password')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Remember + Forgot --}}
                        <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="remember_me"
                                    name="remember"
                                    <label class="form-check-label small" for="remember_me">
                                        Ingat saya
                                    </label>
                            </div>

                            @if (Route::has('password.request'))
                                <a class="small text-decoration-none" href="{{ route('password.request') }}">
                                    Lupa password?
                                </a>
                            @endif
                        </div>

                        {{-- Button Login --}}
                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark">
                                Masuk
                            </button>
                        </div>

                        {{-- Divider --}}
                        <div class="d-flex align-items-center gap-3 my-4">
                            <div class="flex-grow-1 border-top"></div>
                            <div class="text-muted small">atau</div>
                            <div class="flex-grow-1 border-top"></div>
                        </div>

                        {{-- Google Login --}}
                        <div class="d-grid mb-2">
                            <a href="{{ route('google.redirect') }}" class="btn btn-outline-dark">
                                Login dengan Google
                            </a>
                        </div>

                        {{-- Footer info --}}
                        <div class="text-center text-muted small mt-3">
                            Akun dibuat oleh guru/wali kelas. <br>
                            Pastikan email kamu sudah terdaftar.
                        </div>
                    </form>

                </div>
            </div>

            {{-- small footer --}}
            <div class="text-center text-muted small mt-3">
                © {{ date('Y') }} Kas Kelas
            </div>

        </div>
    </div>
</div>

{{-- Toggle show/hide password --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('togglePassword');
        const input = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');

        if (toggleBtn && input) {
            toggleBtn.addEventListener('click', function () {
                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');

                if (icon) {
                    icon.textContent = isPassword ? '🙈' : '👁';
                }
            });
        }
    });
</script>
@endsection
