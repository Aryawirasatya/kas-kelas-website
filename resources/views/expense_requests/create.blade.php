@php
    $user        = auth()->user();
    $isGuru      = $user?->hasRole('guru');
    $isBendahara = $user?->hasRole('bendahara');
@endphp

@extends('layouts.app')

@section('title', 'Tambah Pengajuan Pengeluaran')

@section('content')
<div class="content-wrapper">

    <div class="row mb-3">
        <div class="col-md-8">
            <h3 class="font-weight-bold mb-0">
                @if($isBendahara)
                    Tambah Pengajuan Pengeluaran
                @else
                    Form Pengajuan Pengeluaran
                @endif
            </h3>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">
                Kas kelas {{ $classYear->class_label }} ({{ $classYear->academic_year }}).
                @if($isBendahara)
                    Isi data pengajuan dengan jelas agar guru mudah memproses.
                @elseif($isGuru)
                    Halaman ini biasanya digunakan bendahara untuk mengajukan pengeluaran.
                @endif
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('expense-requests.index') }}" class="btn btn-light">
                <i class="mdi mdi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Panel info --}}
        <div class="col-lg-4 grid-margin">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        @if($isBendahara)
                            Panduan Pengajuan
                        @else
                            Informasi Pengajuan
                        @endif
                    </h5>

                    @if($isBendahara)
                        <ol class="small text-muted ps-3 mb-3">
                            <li>Pilih <strong>tanggal</strong> pengajuan sesuai rencana pengeluaran.</li>
                            <li>Pilih <strong>kategori pengeluaran</strong> yang paling sesuai.</li>
                            <li>Isi <strong>nominal</strong> sesuai total pengeluaran (tanpa titik).</li>
                            <li>Tambahkan <strong>deskripsi</strong> singkat namun jelas.</li>
                            <li>Upload <strong>nota/bukti</strong> (jika sudah ada).</li>
                        </ol>
                        <p class="small mb-1">
                            Status awal pengajuan adalah
                            <span class="badge bg-warning text-dark">Pending</span>
                            dan akan diproses oleh guru.
                        </p>
                    @else
                        <p class="small text-muted mb-2">
                            Form ini digunakan bendahara untuk mengajukan pengeluaran kas kelas yang nantinya akan guru ACC atau tolak.
                        </p>
                        <p class="small mb-1">
                            Status awal tetap
                            <span class="badge bg-warning text-dark">Pending</span>
                            sampai guru melakukan persetujuan.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Form --}}
        <div class="col-lg-8 grid-margin">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('expense-requests.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- Tanggal --}}
                        <div class="mb-3">
                            <label for="request_date" class="form-label">
                                Tanggal Pengajuan <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   id="request_date"
                                   name="request_date"
                                   class="form-control @error('request_date') is-invalid @enderror"
                                   value="{{ old('request_date', now()->toDateString()) }}">
                            @error('request_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Kategori --}}
                        <div class="mb-3">
                            <label for="category_id" class="form-label">
                                Kategori Pengeluaran <span class="text-danger">*</span>
                            </label>
                            <select id="category_id"
                                    name="category_id"
                                    class="form-select @error('category_id') is-invalid @enderror">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nominal --}}
                        <div class="mb-3">
                            <label for="amount" class="form-label">
                                Nominal Pengeluaran (Rp) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number"
                                       id="amount"
                                       name="amount"
                                       class="form-control @error('amount') is-invalid @enderror"
                                       placeholder="contoh: 50000"
                                       min="1"
                                       value="{{ old('amount') }}">
                            </div>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Masukkan tanpa titik atau koma. Contoh: <code>50000</code>
                            </small>
                        </div>

                        {{-- Deskripsi --}}
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                Deskripsi / Keperluan
                            </label>
                            <textarea id="description"
                                      name="description"
                                      rows="3"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Contoh: Pembelian spidol dan penghapus papan tulis.">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Jelaskan secara singkat namun jelas agar guru mudah menilai.
                            </small>
                        </div>

                        {{-- Nota --}}
                        <div class="mb-3">
                            <label for="nota" class="form-label">
                                Upload Nota / Bukti (opsional)
                            </label>
                            <input type="file"
                                   id="nota"
                                   name="nota"
                                   class="form-control @error('nota') is-invalid @enderror"
                                   accept=".jpg,.jpeg,.png,.pdf">
                            @error('nota')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Format: JPG, JPEG, PNG, atau PDF. Maksimal 2MB.
                            </small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('expense-requests.index') }}" class="btn btn-light">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-1"></i>
                                Simpan Pengajuan
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
