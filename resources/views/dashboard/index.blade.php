@extends('layouts.app')

@section('content')
@php($u = auth()->user())
<div class="container mt-4">
    <h3>Selamat datang, {{ $u->name }} 👋</h3>
    <p>Email: <strong>{{ $u->email }}</strong></p>

    {{-- Role dari kolom enum di tabel users (jika ada) --}}
    <p>Role (enum): 
        <span class="badge bg-primary">{{ $u->role ?? '-' }}</span>
    </p>

    {{-- Role dari Spatie (lebih akurat; bisa lebih dari satu) --}}
    <p>Role (Spatie): 
        <span class="badge bg-success">
            {{ $u->getRoleNames()->implode(', ') ?: '-' }}
        </span>
    </p>

    <hr>

    <h5>Informasi Akun</h5>
    <ul>
        <li>ID: {{ $u->id }}</li>
        <li>Jenis Kelamin: {{ $u->gender ?? '-' }}</li>
        <li>Status Aktif: 
            @if($u->active ?? false)
                <span class="text-success">Aktif</span>
            @else
                <span class="text-danger">Nonaktif</span>
            @endif
        </li>
    </ul>

    <hr>
    <p class="text-muted">
        Terakhir login: {{ now()->format('d M Y, H:i') }} WIB
    </p>
</div>
@endsection
