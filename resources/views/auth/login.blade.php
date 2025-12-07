@extends('layouts.guest')

@section('title', 'Login Kas Kelas')

@section('content')
<div class="row w-100 mx-0">
    <div class="col-lg-4 col-md-6 mx-auto">
        <div class="card shadow-sm border-0 auth-card">
            <div class="card-body p-4 p-md-5">

                {{-- Brand / Judul --}}
                <div class="text-center mb-4">
                    <div class="brand-logo mb-2">
                        {{-- pakai logo sendiri di sini kalau ada --}}
                        {{-- <img src="{{ asset('assets/images/logo.svg') }}" alt="logo"> --}}
                        <span class="fw-bold fs-4 text-primary">Kas Kelas</span>
                    </div>
                    <h4 class="mb-1">Login</h4>
                    <p class="text-muted mb-0 small">
                        Masuk untuk mengelola kas kelas sebagai Guru, Bendahara, atau Siswa.
                    </p>
                </div>

                {{-- Session status (misal: "Password reset email sent") --}}
                @if (session('status'))
                    <div class="alert alert-success py-2 small" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- Error umum (misal: email/password salah) --}}
                @if ($errors->has('email') || $errors->has('password'))
                    <div class="alert alert-danger py-2 small" role="alert">
                        {{ $errors->first('email') ?: $errors->first('password') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-3">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="nama@contoh.com"
                        >
                        @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
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
                            <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
                                <i class="mdi mdi-eye-outline"></i>
                            </span>
                            @error('password')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    {{-- Remember Me + Lupa password --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
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

                    {{-- Tombol Login --}}
                    <div class="d-grid mb-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-login me-1"></i> Masuk
                        </button>
                    </div>

                    {{-- Info kecil di bawah --}}
                    <p class="text-center text-muted small mb-0">
                        Hak akses diatur oleh Wali Kelas / Guru.  
                        Siswa menggunakan akun yang telah didaftarkan.
                    </p>
                </form>

            </div>
        </div>
    </div>
</div>

{{-- Toggle show/hide password --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('togglePassword');
        const input  = document.getElementById('password');

        if (toggle && input) {
            toggle.addEventListener('click', function () {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);

                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('mdi-eye-outline');
                    icon.classList.toggle('mdi-eye-off-outline');
                }
            });
        }
    });
</script>
@endsection
