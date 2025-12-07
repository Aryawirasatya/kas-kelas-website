@extends('layouts.app')

@section('title', 'Edit Kategori Pengeluaran')

@section('content')
<div class="content-wrapper">
    <div class="row justify-content-center">
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card shadow-sm border-0">

                {{-- Header card --}}
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Edit Kategori Pengeluaran</h4>
                        <small class="d-block mt-1 text-white-50">
                            Perbarui nama atau deskripsi kategori tanpa mengubah jenis pengeluaran.
                        </small>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        {{-- Info type (read only) --}}
                        <span class="badge bg-light text-dark">
                            Jenis: {{ strtoupper($category->type) }}
                        </span>

                        <a href="{{ route('categories.index') }}" class="btn btn-sm btn-light">
                            <i class="mdi mdi-arrow-left"></i>
                            Kembali
                        </a>
                    </div>
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

                    <form action="{{ route('categories.update', $category->id) }}" method="POST">
                        @csrf
                        @method('PUT')

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
                                value="{{ old('name', $category->name) }}"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="form-text text-muted">
                                    Ubah jika nama lama kurang jelas / membingungkan.
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
                            >{{ old('description', $category->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="form-text text-muted">
                                    Boleh dikosongkan, tapi deskripsi membantu saat laporan & audit kas.
                                </small>
                            @enderror
                        </div>

                        {{-- Info type (fix) --}}
                        <div class="alert alert-secondary d-flex align-items-center mb-4">
                            <i class="mdi mdi-lock-outline me-2 fs-4"></i>
                            <div>
                                <div class="fw-semibold mb-1">Jenis kategori terkunci</div>
                                <small>
                                    Kategori ini digunakan sebagai
                                    <span class="badge bg-dark text-uppercase">PENGELUARAN ({{ $category->type }})</span>
                                    dan tidak dapat diubah dari halaman ini.
                                </small>
                            </div>
                        </div>

                        {{-- Tombol aksi --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('categories.index') }}" class="btn btn-light">
                                Batal
                            </a>

                            <div class="d-flex gap-2">
                                {{-- (opsional) tombol hapus bisa ditaruh di sini kalau mau --}}
                                {{-- 
                                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" 
                                      onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="mdi mdi-delete"></i> Hapus
                                    </button>
                                </form>
                                --}}

                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-content-save"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
