@php
    /** @var \App\Models\User $user */
    $user = auth()->user();

    $roles = (method_exists($user, 'getRoleNames'))
        ? $user->getRoleNames()
        : collect();

    $primaryRole = $roles->first();
@endphp

@extends('layouts.app')

@section('title', 'Profil Akun')

@section('content')
<div class="content-wrapper">

    {{-- Header halaman --}}
    <div class="page-header d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="page-title mb-1">
                <i class="mdi mdi-account-circle-outline me-2 text-primary"></i>
                Profil Akun
            </h3>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                Kelola informasi akun dan keamanan login untuk aplikasi kas kelas.
            </p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Profil</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        {{-- Panel ringkasan user (kiri) --}}
        <div class="col-lg-4 grid-margin">
            <div class="card shadow-sm border-0 h-100">
                {{-- Header profil dengan background halus --}}
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white"
                             style="width: 64px; height: 64px;">
                            <i class="mdi mdi-account fs-2"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-1">
                                {{ $user?->name ?? 'Pengguna' }}
                            </h5>
                            <p class="mb-1 text-muted" style="font-size: 0.9rem;">
                                {{ $user?->email }}
                            </p>
                            <span class="badge bg-light text-muted" style="font-size: 0.7rem;">
                                ID Akun: #{{ $user?->id }}
                            </span>
                        </div>
                    </div>

                    {{-- Role / Akses --}}
                    <div class="mb-3">
                        <span class="text-muted d-block mb-1" style="font-size: 0.8rem;">
                            <i class="mdi mdi-shield-account-outline me-1"></i>
                            Role / Akses
                        </span>
                        <div>
                            @if($roles->count())
                                @foreach($roles as $role)
                                    <span class="badge bg-primary-subtle text-primary me-1 mb-1"
                                          style="font-size: 0.75rem;">
                                        <i class="mdi mdi-check-circle-outline me-1"></i>
                                        {{ ucfirst($role) }}
                                    </span>
                                @endforeach
                            @else
                                <span class="badge bg-light text-muted" style="font-size: 0.75rem;">
                                    Tidak ada role
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Info singkat akun --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted" style="font-size: 0.8rem;">
                                <i class="mdi mdi-calendar-account me-1"></i>
                                Bergabung sejak
                            </span>
                            <span style="font-size: 0.9rem;">
                                {{ optional($user?->created_at)->format('d M Y') ?? '-' }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted" style="font-size: 0.8rem;">
                                <i class="mdi mdi-shield-check-outline me-1"></i>
                                Role utama
                            </span>
                            <span style="font-size: 0.9rem;">
                                {{ $primaryRole ? ucfirst($primaryRole) : '-' }}
                            </span>
                        </div>

                    </div>

                    <hr>

                    {{-- Tips singkat terkait fitur --}}
                    <div class="mb-2">
                        <p class="text-muted mb-2" style="font-size: 0.8rem;">
                            <i class="mdi mdi-information-outline me-1"></i>
                            Tips penggunaan:
                        </p>
                        <ul class="list-unstyled mb-0" style="font-size: 0.8rem;">
                            <li class="d-flex mb-1">
                                <i class="mdi mdi-checkbox-blank-circle-outline me-2 mt-1 text-primary" style="font-size: 0.6rem;"></i>
                                Pastikan nama dan email sesuai untuk laporan kas dan export data.
                            </li>
                            <li class="d-flex">
                                <i class="mdi mdi-checkbox-blank-circle-outline me-2 mt-1 text-primary" style="font-size: 0.6rem;"></i>
                                Ganti password secara berkala untuk menjaga keamanan kelas.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel form (kanan) --}}
        <div class="col-lg-8 grid-margin">
            <div class="card shadow-sm border-0">
                <div class="card-body p-3 p-md-4">
                    {{-- Tab navigasi untuk form --}}
                    <ul class="nav nav-pills mb-3" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link active d-flex align-items-center"
                                id="tab-profile-info"
                                data-bs-toggle="pill"
                                data-bs-target="#pane-profile-info"
                                type="button"
                                role="tab"
                                aria-controls="pane-profile-info"
                                aria-selected="true"
                            >
                                <i class="mdi mdi-account-edit-outline me-2"></i>
                                Informasi Profil
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link d-flex align-items-center"
                                id="tab-security"
                                data-bs-toggle="pill"
                                data-bs-target="#pane-security"
                                type="button"
                                role="tab"
                                aria-controls="pane-security"
                                aria-selected="false"
                            >
                                <i class="mdi mdi-lock-reset me-2"></i>
                                Keamanan & Password
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="profileTabsContent">
                        {{-- TAB 1: Informasi Profil --}}
                        <div class="tab-pane fade show active" id="pane-profile-info" role="tabpanel" aria-labelledby="tab-profile-info">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary"
                                     style="width: 40px; height: 40px;">
                                    <i class="mdi mdi-account-edit fs-5"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-0">Informasi Profil</h5>
                                    <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                                        Ubah nama dan email yang digunakan pada laporan dan login.
                                    </p>
                                </div>
                            </div>

                            @if (session('status') === 'profile-updated')
                                <div class="alert alert-success py-2" style="font-size: 0.85rem;">
                                    <i class="mdi mdi-check-circle-outline me-1"></i>
                                    Profil berhasil diperbarui.
                                </div>
                            @endif

                            <form method="POST" action="{{ route('profile.update') }}" class="mt-3">
                                @csrf
                                @method('patch')

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">
                                            <i class="mdi mdi-account-outline me-1"></i>
                                            Nama Lengkap
                                        </label>
                                        <input
                                            type="text"
                                            name="name"
                                            id="name"
                                            value="{{ old('name', $user?->name) }}"
                                            class="form-control @error('name') is-invalid @enderror"
                                            autocomplete="name"
                                            required
                                        >
                                        @error('name')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">
                                            <i class="mdi mdi-email-outline me-1"></i>
                                            Alamat Email
                                        </label>
                                        <input
                                            type="email"
                                            name="email"
                                            id="email"
                                            value="{{ old('email', $user?->email) }}"
                                            class="form-control @error('email') is-invalid @enderror"
                                            autocomplete="email"
                                            required
                                        >
                                        @error('email')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <button type="submit" class="btn btn-primary btn-sm px-3">
                                        <i class="mdi mdi-content-save-outline me-1"></i>
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- TAB 2: Keamanan & Password --}}
                        <div class="tab-pane fade" id="pane-security" role="tabpanel" aria-labelledby="tab-security">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning-subtle text-warning"
                                     style="width: 40px; height: 40px;">
                                    <i class="mdi mdi-lock-outline fs-5"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-0">Keamanan & Password</h5>
                                    <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                                        Atur ulang password secara berkala untuk menjaga keamanan kas kelas.
                                    </p>
                                </div>
                            </div>

                            @if (session('status') === 'password-updated')
                                <div class="alert alert-success py-2" style="font-size: 0.85rem;">
                                    <i class="mdi mdi-check-circle-outline me-1"></i>
                                    Password berhasil diperbarui.
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-7">
                                    <form method="POST" action="{{ route('password.update') }}" class="mt-1">
                                        @csrf
                                        @method('put')

                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">
                                                <i class="mdi mdi-lock-check-outline me-1"></i>
                                                Password Saat Ini
                                            </label>
                                            <input
                                                type="password"
                                                name="current_password"
                                                id="current_password"
                                                class="form-control @error('current_password') is-invalid @enderror"
                                                autocomplete="current-password"
                                                required
                                            >
                                            @error('current_password')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">
                                                <i class="mdi mdi-lock-outline me-1"></i>
                                                Password Baru
                                            </label>
                                            <input
                                                type="password"
                                                name="password"
                                                id="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                autocomplete="new-password"
                                                required
                                            >
                                            @error('password')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="password_confirmation" class="form-label">
                                                <i class="mdi mdi-lock-reset me-1"></i>
                                                Konfirmasi Password Baru
                                            </label>
                                            <input
                                                type="password"
                                                name="password_confirmation"
                                                id="password_confirmation"
                                                class="form-control"
                                                autocomplete="new-password"
                                                required
                                            >
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-warning text-white btn-sm px-3">
                                                <i class="mdi mdi-shield-lock-outline me-1"></i>
                                                Update Password
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                {{-- Tips keamanan (kanan) --}}
                                <div class="col-md-5 mt-4 mt-md-0">
                                    <div class="border rounded-3 p-3 bg-light">
                                        <p class="text-muted mb-2" style="font-size: 0.8rem;">
                                            <i class="mdi mdi-shield-key-outline me-1"></i>
                                            Rekomendasi password:
                                        </p>
                                        <ul class="list-unstyled mb-0" style="font-size: 0.8rem;">
                                            <li class="d-flex mb-1">
                                                <i class="mdi mdi-checkbox-marked-circle-outline me-2 text-success"></i>
                                                Minimal 8 karakter.
                                            </li>
                                            <li class="d-flex mb-1">
                                                <i class="mdi mdi-checkbox-marked-circle-outline me-2 text-success"></i>
                                                Kombinasi huruf besar, kecil, dan angka.
                                            </li>
                                            <li class="d-flex mb-1">
                                                <i class="mdi mdi-checkbox-marked-circle-outline me-2 text-success"></i>
                                                Hindari menggunakan nama kelas atau tahun ajaran.
                                            </li>
                                            <li class="d-flex">
                                                <i class="mdi mdi-checkbox-marked-circle-outline me-2 text-success"></i>
                                                Jangan bagikan password ke siswa lain / teman sekelas.
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div> {{-- /row --}}
                        </div>
                    </div> {{-- /tab-content --}}
                </div>
            </div>
        </div> {{-- /col-lg-8 --}}
    </div> {{-- /row --}}
</div>
@endsection
