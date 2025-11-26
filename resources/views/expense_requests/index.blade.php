@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $requests */

    $user        = auth()->user();
    $isGuru      = $user?->hasRole('guru');
    $isBendahara = $user?->hasRole('bendahara');

    $statusFilter = request('status');
@endphp

@extends('layouts.app')

@section('title', 'Pengajuan Pengeluaran')

@section('content')
<div class="content-wrapper">

    {{-- Page Header --}}
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h3 class="fw-bold mb-1">
                @if($isGuru)
                    Review Pengajuan Pengeluaran
                @else
                    Pengajuan Pengeluaran
                @endif
            </h3>

            <div class="d-flex flex-wrap align-items-center text-muted small">
                <i class="mdi mdi-cash-multiple me-1"></i>
                <span>
                    Kas kelas {{ $classYear->class_label }} ({{ $classYear->academic_year }})
                    @if($isGuru)
                        &mdash; Mode: <strong>Guru / ACC</strong>
                    @elseif($isBendahara)
                        &mdash; Mode: <strong>Bendahara / Pengajuan</strong>
                    @endif
                </span>
            </div>
        </div>

        {{-- CTA hanya bendahara --}}
        @if($isBendahara)
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <a href="{{ route('expense-requests.create') }}" class="btn btn-primary shadow-sm">
                    <i class="mdi mdi-plus-circle-outline me-1"></i> Tambah Pengajuan
                </a>
            </div>
        @endif
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="mdi mdi-check-circle-outline me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="mdi mdi-alert-circle-outline me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row gy-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">
                        @if($isGuru)
                            Filter Status Pengajuan
                        @else
                            Status Pengajuan
                        @endif
                    </label>
                    <select name="status" class="form-select form-select-sm shadow-sm">
                        <option value="">Semua</option>
                        <option value="pending"  {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                @if($isGuru)
                    <div class="col-md-5 small text-muted d-none d-md-block">
                        <p class="mb-1">
                            <strong>Tips:</strong> gunakan filter
                            <span class="badge bg-warning text-dark">Pending</span>
                            untuk fokus pada pengajuan yang menunggu ACC.
                        </p>
                    </div>
                @endif

                <div class="col-md-2">
                    <button class="btn btn-outline-primary btn-sm shadow-sm w-100">
                        <i class="mdi mdi-filter-variant me-1"></i> Terapkan
                    </button>
                </div>

                @if($statusFilter !== null && $statusFilter !== '')
                    <div class="col-md-2">
                        <a href="{{ route('expense-requests.index') }}" class="btn btn-light btn-sm shadow-sm w-100">
                            Reset
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            @if($requests->count() === 0)
                <div class="p-4 text-center text-muted">
                    <i class="mdi mdi-file-search-outline fs-2 d-block mb-2"></i>
                    @if($isGuru)
                        Belum ada pengajuan dengan filter saat ini.
                    @else
                        Belum ada pengajuan pengeluaran.
                    @endif
                    <br>
                    @if($isBendahara)
                        <span class="small">Klik tombol <b>Tambah Pengajuan</b> untuk membuat pengajuan baru.</span>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered mb-0">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 5%">#</th>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Deskripsi</th>
                            <th class="text-end">Nominal</th>
                            <th>Status</th>
                            <th>
                                @if($isGuru)
                                    Pengaju (Bendahara)
                                @else
                                    Pengaju
                                @endif
                            </th>
                            <th class="text-end" style="width: 120px">Aksi</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($requests as $index => $item)
                            <tr>
                                <td class="fw-bold">{{ $requests->firstItem() + $index }}</td>

                                <td>{{ $item->request_date?->format('d M Y') }}</td>

                                <td>
                                    <span class="badge bg-light text-dark border rounded-pill px-3">
                                        {{ $item->category->name }}
                                    </span>
                                </td>

                                <td>
                                    <span class="d-inline-block text-truncate" style="max-width:230px;">
                                        {{ $item->description }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <span class="fw-semibold">
                                        Rp {{ number_format($item->amount, 0, ',', '.') }}
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $statusMap = [
                                            'pending'  => ['bg-warning text-dark', 'Pending'],
                                            'approved' => ['bg-success', 'Disetujui'],
                                            'rejected' => ['bg-danger', 'Ditolak'],
                                        ];
                                        [$class, $label] = $statusMap[$item->status] ?? ['bg-secondary', ucfirst($item->status)];
                                    @endphp

                                    <span class="badge {{ $class }} rounded-pill px-3">
                                        {{ $label }}
                                    </span>
                                </td>

                                <td>{{ $item->requester->name }}</td>

                                <td class="text-end">
                                    @if($isGuru)
                                        {{-- GURU: tombol ACC / Detail --}}
                                        @if($item->status === 'pending')
                                            <a href="{{ route('expense-requests.show', $item) }}"
                                               class="btn btn-sm btn-success rounded-pill"
                                               title="Review & ACC pengajuan ini">
                                                <i class="mdi mdi-check-circle-outline me-1"></i>
                                                ACC
                                            </a>
                                        @else
                                            <a href="{{ route('expense-requests.show', $item) }}"
                                               class="btn btn-sm btn-outline-primary rounded-pill"
                                               title="Lihat detail pengajuan">
                                                <i class="mdi mdi-eye-outline me-1"></i>
                                                Detail
                                            </a>
                                        @endif
                                    @else
                                        {{-- BENDAHARA: tombol Detail + (opsional) Hapus --}}
                                        <a href="{{ route('expense-requests.show', $item) }}"
                                           class="btn btn-sm btn-outline-primary rounded-pill"
                                           title="Lihat detail pengajuan">
                                            <i class="mdi mdi-eye-outline"></i>
                                        </a>

                                        @if($item->status === 'pending'
                                            && $isBendahara
                                            && auth()->id() === $item->requested_by
                                        )
                                            <form action="{{ route('expense-requests.destroy', $item) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Batalkan pengajuan ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger rounded-pill"
                                                        title="Batalkan pengajuan">
                                                    <i class="mdi mdi-trash-can-outline"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>

                    </table>
                </div>

                <div class="p-3 d-flex justify-content-end">
                    {{ $requests->links() }}
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
