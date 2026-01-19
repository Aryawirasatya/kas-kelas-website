@extends('layouts.guest')

@section('title', 'Reset Password - Kas Kelas')

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
                            Reset Password
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="alert alert-light border small">
                        Masukkan email akun kamu. Nanti sistem akan mengirim link reset password ke email tersebut.
                    </div>

                    {{-- Status sukses --}}
                    @if (session('status'))
                        <div class="alert alert-success small py-2 mb-3" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Error --}}
                    @if ($errors->has('email'))
                        <div class="alert alert-danger small py-2 mb-3" role="alert">
                            {{ $errors->first('email') }}
                        </div>
                    @endif

                    {{-- Form --}}
                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

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

                        {{-- Button --}}
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-dark">
                                Kirim Link Reset Password
                            </button>
                        </div>

                        {{-- Back to login --}}
                        <div class="text-center">
                            <a href="{{ route('login') }}" class="small text-decoration-none">
                                ← Kembali ke halaman login
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
