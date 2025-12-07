{{-- resources/views/activity_logs/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Riwayat Aktivitas')

@section('content')
<div class="content-wrapper">
    {{-- HEADER --}}
    <div class="page-header d-flex justify-content-between align-items-center">
        <h3 class="page-title mb-0 d-flex align-items-center">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-history"></i>
            </span>
            Riwayat Aktivitas
        </h3>

        {{-- Chip tahun ajaran aktif --}}
        @if($activeYear)
            <div class="badge bg-light text-dark border">
                <i class="mdi mdi-calendar-range me-1"></i>
                Tahun ajaran aktif:
                <span class="fw-semibold">{{ $activeYear->name }}</span>
            </div>
        @endif
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">

                    {{-- FILTER BAR --}}
                    <form method="GET" action="{{ route('activity-logs.index') }}" class="mb-3">
                        <div class="row g-2 align-items-end">
                            {{-- Modul --}}
                            <div class="col-md-3">
                                <label class="form-label mb-1">Modul</label>
                                <select name="module" class="form-select form-select-sm">
                                    <option value="">Semua Modul</option>
                                    @foreach($modules as $value => $label)
                                        <option value="{{ $value }}" {{ request('module') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Action --}}
                            <div class="col-md-3">
                                <label class="form-label mb-1">Action</label>
                                <select name="action" class="form-select form-select-sm">
                                    <option value="">Semua Action</option>
                                    @foreach($availableActions as $action)
                                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                                            {{ $action }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Role --}}
                            <div class="col-md-2">
                                <label class="form-label mb-1">Role</label>
                                <select name="role" class="form-select form-select-sm">
                                    <option value="">Semua Role</option>
                                    <option value="guru" {{ request('role') === 'guru' ? 'selected' : '' }}>Guru</option>
                                    <option value="bendahara" {{ request('role') === 'bendahara' ? 'selected' : '' }}>Bendahara</option>
                                    <option value="siswa" {{ request('role') === 'siswa' ? 'selected' : '' }}>Siswa</option>
                                </select>
                            </div>

                            {{-- Date from --}}
                            <div class="col-md-2">
                                <label class="form-label mb-1">Dari Tanggal</label>
                                <input type="date" name="date_from"
                                       class="form-control form-control-sm"
                                       value="{{ request('date_from') }}">
                            </div>

                            {{-- Date to --}}
                            <div class="col-md-2">
                                <label class="form-label mb-1">Sampai Tanggal</label>
                                <input type="date" name="date_to"
                                       class="form-control form-control-sm"
                                       value="{{ request('date_to') }}">
                            </div>
                        </div>

                        <div class="row g-2 mt-2 align-items-end">
                            {{-- Search --}}
                            <div class="col-md-4">
                                <label class="form-label mb-1">Pencarian (pesan / action / entity)</label>
                                <input type="text" name="search" class="form-control form-control-sm"
                                       placeholder="Cari kata kunci..."
                                       value="{{ request('search') }}">
                            </div>

                            {{-- Hanya penghapusan --}}
                            <div class="col-md-3">
                                <div class="form-check mt-5">
                                    <label class="form-check-label small" for="only_deleted">
                                        <input class="form-check-input" type="checkbox"
                                               id="only_deleted" name="only_deleted" value="1"
                                               {{ request('only_deleted') ? 'checked' : '' }}>
                                        Tampilkan hanya aktivitas penghapusan
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-5 text-md-end mt-2 mt-md-0">
                                <button type="submit" class="btn btn-sm btn-gradient-primary me-1">
                                    <i class="mdi mdi-magnify me-1"></i> Filter
                                </button>
                                <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-light">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Legend warna --}}
                    <div class="mb-3 small text-muted">
                        <span class="me-3">
                            <span class="badge bg-success me-1">
                                <i class="mdi mdi-plus"></i>
                            </span> dibuat
                        </span>
                        <span class="me-3">
                            <span class="badge bg-primary me-1">
                                <i class="mdi mdi-check"></i>
                            </span> disetujui / dibuka / diaktifkan
                        </span>
                        <span class="me-3">
                            <span class="badge bg-warning text-dark me-1">
                                <i class="mdi mdi-alert"></i>
                            </span> ditolak / ditutup
                        </span>
                        <span class="me-3">
                            <span class="badge bg-danger me-1">
                                <i class="mdi mdi-trash-can"></i>
                            </span> dihapus
                        </span>
                    </div>
                        {{-- Tombol export --}}
<div class="d-flex justify-content-end mb-2">
    <a href="{{ route('activity-logs.export-excel', request()->query()) }}"
       class="btn btn-sm btn-success me-2">
        <i class="mdi mdi-file-excel"></i> Export Excel
    </a>

    <a href="{{ route('activity-logs.export-pdf', request()->query()) }}"
       class="btn btn-sm btn-danger" target="_blank">
        <i class="mdi mdi-file-pdf-box"></i> Export PDF
    </a>
</div>

                    {{-- TABEL LOG --}}
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 170px;">Waktu</th>
                                    <th style="width: 220px;">User & Role</th>
                                    <th>Aktivitas</th>
                                    <th style="width: 200px;">Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    @php
                                        $parts       = explode('.', $log->action);
                                        $moduleKey   = $parts[0] ?? null;
                                        $actionName  = $parts[1] ?? $log->action;
                                        $moduleLabel = $modules[$moduleKey] ?? $moduleKey ?? '-';

                                        $to   = $log->to_json ?? [];
                                        $from = $log->from_json ?? [];

                                        $message      = $to['message'] ?? null;
                                        $entityLabel  = $to['entity_label'] ?? null;
                                        $reason       = $to['reason'] ?? null;
                                        $amount       = $to['amount'] ?? $from['amount'] ?? null;

                                        // role
                                        $roleNames   = $log->actor ? $log->actor->roles->pluck('name')->toArray() : [];
                                        $prettyRoles = collect($roleNames)->map(fn($r) => ucfirst($r))->implode(', ');

                                        // badge warna
                                        $badgeClass = 'bg-secondary';
                                        if (str_contains($actionName, 'created') || $actionName === 'create') {
                                            $badgeClass = 'bg-success';
                                        } elseif (str_contains($actionName, 'approved') || $actionName === 'open' || str_contains($actionName, 'activate')) {
                                            $badgeClass = 'bg-primary';
                                        } elseif (str_contains($actionName, 'rejected') || $actionName === 'close') {
                                            $badgeClass = 'bg-warning text-dark';
                                        } elseif (str_contains($actionName, 'deleted') || $actionName === 'delete') {
                                            $badgeClass = 'bg-danger';
                                        }

                                        // label tahun ajaran → hanya pakai NAMA
                                        $classYearName = $log->classYear?->name;
                                        $humanAction   = $actionLabels[$log->action] ?? null;
                                    @endphp
                                    <tr>
                                        {{-- Waktu --}}
                                        <td>
                                            <div class="fw-semibold">
                                                {{ $log->created_at?->format('d M Y') ?? '-' }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ $log->created_at?->format('H:i') ?? '' }}
                                            </div>
                                        </td>

                                        {{-- User & Role --}}
                                        <td>
                                            @if($log->actor)
                                                <div class="fw-semibold d-flex align-items-center">
                                                    <i class="mdi mdi-account-circle text-primary me-1 fs-5"></i>
                                                    <span>{{ $log->actor->name }}</span>
                                                </div>
                                                <div class="text-muted small ms-4">
                                                    {{ $log->actor->email }}
                                                </div>
                                                @if($prettyRoles)
                                                    <div class="mt-1 ms-4">
                                                        <span class="badge bg-light text-dark">
                                                            <i class="mdi mdi-account-badge me-1"></i>
                                                            {{ $prettyRoles }}
                                                        </span>
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-muted fst-italic small">
                                                    User tidak diketahui
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Aktivitas --}}
                                        <td>
                                            {{-- Kalimat utama --}}
                                            <div class="fw-semibold">
                                                @if($humanAction)
                                                    {{ $humanAction }}
                                                @elseif($message)
                                                    {{ $message }}
                                                @else
                                                    {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                                @endif
                                            </div>

                                            {{-- Objek / nama entity --}}
                                            @if($entityLabel)
                                                <div class="small text-muted mt-1">
                                                    <i class="mdi mdi-target mr-1"></i>
                                                    Objek: {{ $entityLabel }}
                                                </div>
                                            @endif

                                            {{-- Nominal --}}
                                            @if($amount)
                                                <div class="small text-muted mt-1">
                                                    <i class="mdi mdi-cash-multiple me-1"></i>
                                                    Nominal: Rp{{ number_format($amount, 0, ',', '.') }}
                                                </div>
                                            @endif

                                            {{-- Alasan (khusus delete / reject) --}}
                                            @if($reason)
                                                <div class="small mt-1 text-danger">
                                                    <i class="mdi mdi-comment-alert-outline me-1"></i>
                                                    <strong>Alasan:</strong> {{ $reason }}
                                                </div>
                                            @endif

                                            {{-- Tahun ajaran --}}
                                            @if($classYearName)
                                                <div class="small text-muted mt-1">
                                                    <i class="mdi mdi-calendar-range me-1"></i>
                                                    Tahun ajaran: {{ $classYearName }}
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Detail singkat --}}
                                        <td>
                                            <div class="mb-1">
                                                <span class="badge bg-light text-dark">
                                                    <i class="mdi mdi-view-dashboard-outline me-1"></i>
                                                    {{ $moduleLabel }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="badge {{ $badgeClass }}">
                                                    <i class="mdi mdi-flash me-1"></i>
                                                    {{ $actionName }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            Belum ada aktivitas yang tercatat.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3 gap-2">
    <div class="small text-muted">
        Menampilkan
        <span class="fw-semibold">{{ $logs->firstItem() ?? 0 }}</span>
        -
        <span class="fw-semibold">{{ $logs->lastItem() ?? 0 }}</span>
        dari
        <span class="fw-semibold">{{ $logs->total() }}</span>
        aktivitas
    </div>

    <nav aria-label="Navigasi halaman aktivitas">
        {{-- onEachSide(1) biar nggak kepanjangan --}}
        {{ $logs->onEachSide(1)->links() }}
        {{-- Kalau mau eksplisit: --}}
        {{-- {{ $logs->onEachSide(1)->links('pagination::bootstrap-5') }} --}}
    </nav>
</div>


                </div>
            </div>
        </div>
    </div>
</div>
@endsection
