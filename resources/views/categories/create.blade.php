@extends('layouts.app')

@section('content')
<div class="page-header">
    <h3 class="page-title">Tambah Kategori Pengeluaran</h3>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('categories.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label">Nama Kategori</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Deskripsi (opsional)</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>

            <input type="hidden" name="type" value="expense">

            <div class="d-flex justify-content-between">
                <a href="{{ route('categories.index') }}" class="btn btn-light">Kembali</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
