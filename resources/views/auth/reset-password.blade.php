@extends('layouts.guest')

@section('title', 'Buat Password Baru - Kas Kelas')

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
                            Buat Password Baru
                        </div>
                    </div>

                    {{-- Error global --}}
                    @if ($errors->any())
                        <div class="alert alert-danger small py-2 mb-3" role="alert">
                            Ada error, cek input kamu ya.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.store') }}">
                        @csrf

                        {{-- Token wajib --}}
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold">Email</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email', $request->email) }}"
                                class="form-control @error('email') is-invalid @enderror"
                                required
                                autofocus
                                autocomplete="username"
                                readonly
                            >
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Password baru --}}
                        <div class="mb-3">
                            <label for="password" class="form-label small fw-semibold">Password Baru</label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                                autocomplete="new-password"
                                placeholder="Minimal 8 karakter"
                            >
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Konfirmasi password --}}
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label small fw-semibold">Konfirmasi Password</label>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                class="form-control"
                                required
                                autocomplete="new-password"
                                placeholder="Ulangi password"
                            >
                        </div>

                        {{-- Button --}}
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-dark">
                                Simpan Password Baru
                            </button>
                        </div>

                        {{-- Back to login --}}
                        <div class="text-center">
                            <a href="{{ route('login') }}" class="small text-decoration-none">
                                ← Kembali ke Login
                            </a>
                        </div>
                    </form>

                </div>
            </div>

            <div class="text-center text-muted small mt-3">
                © {{ date('Y') }} Kas Kelas
            </div>

        </div>
    </div>
</div>
@endsection
