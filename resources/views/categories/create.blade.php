@extends('layouts.app')

@section('title', 'Tambah Kategori Pengeluaran')

@section('content')
<div class="content-wrapper">
    <div class="row justify-content-center">
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card shadow-sm border-0">

                {{-- Header card --}}
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <div>
                        <h4 class="mb-0">Tambah Kategori Pengeluaran</h4>
                        <small class="d-block mt-1 text-white-50">
                            Kategori ini dipakai saat bendahara mengajukan pengeluaran kas.
                        </small>
                    </div>

                    <a href="{{ route('categories.index') }}" class="btn btn-sm btn-light">
                        <i class="mdi mdi-arrow-left"></i>
                        Kembali
                    </a>
                </div>

                <div class="card-body">

                    {{-- Error validasi --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <div class="d-flex">
                                <i class="mdi mdi-alert-circle-outline me-2 fs-4"></i>
                                <div>
                                    <strong>Terjadi kesalahan input.</strong>
                                    <ul class="mb-0 mt-1 ps-3">
                                        @foreach ($errors->all() as $e)
                                            <li>{{ $e }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('categories.store') }}" method="POST">
                        @csrf

                        {{-- Nama --}}
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">
                                Nama Kategori
                                <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Contoh: ATK, Konsumsi Rapat, Transportasi"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="form-text text-muted">
                                    Buat nama yang singkat dan mudah dipahami bendahara dan siswa.
                                </small>
                            @enderror
                        </div>

                        {{-- Deskripsi --}}
                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">
                                Deskripsi (opsional)
                            </label>
                            <textarea
                                class="form-control @error('description') is-invalid @enderror"
                                id="description"
                                name="description"
                                rows="3"
                                placeholder="Contoh: Kebutuhan alat tulis kelas selama satu semester"
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="form-text text-muted">
                                    Tambahkan penjelasan singkat supaya guru & bendahara paham konteks penggunaannya.
                                </small>
                            @enderror
                        </div>

                        {{-- Info type (fix expense) --}}
                        <div class="alert alert-info d-flex align-items-center mb-4">
                            <i class="mdi mdi-information-outline me-2 fs-4"></i>
                            <div>
                                <div class="fw-semibold mb-1">Catatan</div>
                                <small>
                                    Semua kategori di sini otomatis dianggap sebagai
                                    <span class="badge bg-dark">PENGELUARAN (expense)</span>.
                                </small>
                            </div>
                        </div>

                        {{-- Tombol aksi --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('categories.index') }}" class="btn btn-light">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save"></i>
                                Simpan Kategori
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
