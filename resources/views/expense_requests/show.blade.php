@php
    $user        = auth()->user();
    $isGuru      = $user?->hasRole('guru');
    $isBendahara = $user?->hasRole('bendahara');
@endphp

@extends('layouts.app')

@section('title', 'Detail Pengajuan Pengeluaran')

@section('content')
<div class="content-wrapper">

    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-md-8">
            <h3 class="font-weight-bold mb-1">
                @if($isGuru)
                    Review Pengajuan Pengeluaran
                @else
                    Detail Pengajuan Pengeluaran
                @endif
            </h3>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">
                Pengajuan oleh {{ $expenseRequest->requester->name ?? '-' }}
                pada {{ $expenseRequest->request_date?->format('d M Y') ?? '-' }}.
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('expense-requests.index') }}" class="btn btn-light">
                <i class="mdi mdi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle-outline me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle-outline me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Info utama --}}
        <div class="col-lg-8 grid-margin">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="card-title mb-1">
                                {{ $expenseRequest->category->name ?? 'Pengeluaran' }}
                            </h4>
                            <p class="mb-0 text-muted" style="font-size: 0.9rem;">
                                Diajukan oleh <strong>{{ $expenseRequest->requester->name ?? '-' }}</strong>
                                pada {{ $expenseRequest->request_date?->format('d M Y') ?? '-' }}
                            </p>
                        </div>
                        <div>
                            @php
                                $statusMap = [
                                    'pending'  => ['bg-warning text-dark', 'Pending'],
                                    'approved' => ['bg-success', 'Disetujui'],
                                    'rejected' => ['bg-danger', 'Ditolak'],
                                ];
                                [$badgeClass, $label] = $statusMap[$expenseRequest->status] ?? ['bg-secondary', ucfirst($expenseRequest->status)];
                            @endphp
                            <span class="badge {{ $badgeClass }} px-3 py-2">
                                {{ $label }}
                            </span>
                        </div>
                    </div>

                    <div class="row small mb-3">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted d-block">Nominal Pengeluaran</span>
                                <span class="fw-bold">
                                    Rp {{ number_format($expenseRequest->amount, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted d-block">Kategori</span>
                                <span>{{ $expenseRequest->category->name ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            @if($expenseRequest->status === 'approved')
                                <div class="mb-2">
                                    <span class="text-muted d-block">Disetujui oleh</span>
                                    <span>{{ $expenseRequest->approver->name ?? '-' }}</span>
                                </div>
                                <div class="mb-2">
                                    <span class="text-muted d-block">Tanggal Persetujuan</span>
                                    <span>{{ $expenseRequest->approved_at?->format('d M Y H:i') ?? '-' }}</span>
                                </div>
                            @elseif($expenseRequest->status === 'rejected')
                                <div class="mb-2">
                                    <span class="text-muted d-block">Ditolak oleh</span>
                                    <span>{{ $expenseRequest->approver->name ?? '-' }}</span>
                                </div>
                                <div class="mb-2">
                                    <span class="text-muted d-block">Tanggal Penolakan</span>
                                    <span>{{ $expenseRequest->approved_at?->format('d M Y H:i') ?? '-' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-2">Deskripsi / Keperluan</h6>
                    <p class="mb-0">
                        {{ $expenseRequest->description ?? 'Tidak ada deskripsi tambahan.' }}
                    </p>

                    @if($expenseRequest->status === 'rejected' && $expenseRequest->reject_reason)
                        <hr>
                        <h6 class="fw-bold mb-2 text-danger">Alasan Penolakan</h6>
                        <p class="mb-0">
                            {{ $expenseRequest->reject_reason }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panel lampiran + ACC guru --}}
        <div class="col-lg-4 grid-margin">
            {{-- Lampiran --}}
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Lampiran Nota / Bukti</h6>

                    @php
                        $attachment = $expenseRequest->attachments->first();
                    @endphp

                    @if($attachment)
                        @php
                            $isImage = str_starts_with($attachment->mime ?? '', 'image/');
                        @endphp

                        @if($isImage)
                            <div class="mb-3">
                                <img src="{{ asset('storage/'.$attachment->file_path) }}"
                                     alt="Nota Pengeluaran"
                                     class="img-fluid rounded border">
                            </div>
                        @else
                            <p class="small mb-2">
                                Terdapat file nota/bukti dalam bentuk dokumen.
                            </p>
                        @endif

                        <a href="{{ asset('storage/'.$attachment->file_path) }}"
                           target="_blank"
                           class="btn btn-sm btn-outline-primary">
                            <i class="mdi mdi-file-download-outline me-1"></i>
                            Download / Buka Lampiran
                        </a>
                    @else
                        <p class="text-muted small mb-0">
                            Belum ada lampiran nota yang diunggah.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Panel ACC Guru --}}
            @if($isGuru)
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Panel Persetujuan Guru</h6>

                        <div class="small mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Saldo kas saat ini</span>
                                <span class="fw-bold">
                                    Rp {{ number_format($currentBalance, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Nominal pengajuan</span>
                                <span class="fw-bold text-primary">
                                    - Rp {{ number_format($expenseRequest->amount, 0, ',', '.') }}
                                </span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Saldo jika disetujui</span>
                                @php
                                    $after = $currentBalance - $expenseRequest->amount;
                                @endphp
                                <span class="fw-bold {{ $after < 0 ? 'text-danger' : '' }}">
                                    Rp {{ number_format($after, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        @if($expenseRequest->status === 'pending')
                            <div class="d-grid gap-2">
                                {{-- Tombol ACC --}}
                                <form action="{{ route('expense-requests.approve', $expenseRequest) }}"
                                      method="POST"
                                      onsubmit="return confirm('Setujui pengajuan ini dan catat sebagai pengeluaran kas?');">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="mdi mdi-check-circle-outline me-1"></i>
                                        Setujui & Catat Pengeluaran
                                    </button>
                                </form>

                                {{-- Tombol Tolak (pakai modal) --}}
                                <button type="button"
                                        class="btn btn-outline-danger btn-sm w-100"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalReject">
                                    <i class="mdi mdi-close-circle-outline me-1"></i>
                                    Tolak Pengajuan
                                </button>
                            </div>
                        @else
                            <p class="small text-muted mb-0">
                                Pengajuan ini sudah diproses ({{ $label }}), perubahan tidak dapat dilakukan di sini.
                            </p>
                        @endif

                    </div>
                </div>
            @elseif($isBendahara)
                {{-- Info kecil untuk bendahara --}}
                <div class="card shadow-sm border-0">
                    <div class="card-body small text-muted">
                        <h6 class="fw-bold mb-2">Status Pengajuan Anda</h6>
                        <p class="mb-1">
                            Pengajuan ini sedang/belum diproses oleh guru. Jika status:
                        </p>
                        <ul class="mb-0 ps-3">
                            <li><span class="badge bg-warning text-dark">Pending</span> &mdash; menunggu ACC guru.</li>
                            <li><span class="badge bg-success">Disetujui</span> &mdash; sudah dicatat sebagai pengeluaran kas.</li>
                            <li><span class="badge bg-danger">Ditolak</span> &mdash; cek alasan penolakan di bagian atas.</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- Modal Tolak --}}
@if($isGuru && $expenseRequest->status === 'pending')
<div class="modal fade" id="modalReject" tabindex="-1" aria-labelledby="modalRejectLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('expense-requests.reject', $expenseRequest) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="modalRejectLabel">Tolak Pengajuan Pengeluaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    Berikan alasan penolakan secara singkat agar bendahara dan siswa dapat memahami keputusan ini.
                </p>
                <div class="mb-3">
                    <label for="reject_reason" class="form-label">
                        Alasan Penolakan <span class="text-danger">*</span>
                    </label>
                    <textarea name="reject_reason" id="reject_reason" rows="3"
                              class="form-control @error('reject_reason') is-invalid @enderror"
                              required>{{ old('reject_reason') }}</textarea>
                    @error('reject_reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">
                    <i class="mdi mdi-close-circle-outline me-1"></i> Tolak Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
