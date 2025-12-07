<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Aktivitas</title>
    <style>
        /* ====== GLOBAL ====== */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 20px;
            color: #222;
        }

        h1 {
            margin: 0 0 4px 0;
            text-align: center;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .subtitle {
            text-align: center;
            font-size: 10px;
            margin-bottom: 12px;
            color: #555;
        }

        .meta {
            width: 100%;
            margin-bottom: 10px;
            font-size: 9px;
        }

        .meta td {
            padding: 2px 0;
        }

        .meta-label {
            width: 80px;
            color: #555;
        }

        /* ====== TABLE ====== */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #bfbfbf;
            padding: 4px 6px;
            word-wrap: break-word;
            vertical-align: top;
        }

        th {
            background: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        /* Lebar kolom dibuat proporsional */
        th.col-tanggal { width: 60px; }
        th.col-jam     { width: 38px; }
        th.col-user    { width: 110px; }
        th.col-role    { width: 70px; }
        th.col-modul   { width: 90px; }
        th.col-aksi    { width: 65px; }
        th.col-desc    { width: auto; }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-muted {
            color: #777;
        }

        .small {
            font-size: 9px;
        }

        /* Baris selang-seling biar mudah dibaca */
        tr.striped {
            background-color: #fafafa;
        }
    </style>
</head>
<body>
    {{-- HEADER --}}
    <h1>Riwayat Aktivitas</h1>
    <div class="subtitle">
 
        <br>
        Tanggal export: {{ now()->format('d-m-Y H:i') }}
    </div>

    {{-- INFO SINGKAT (boleh dikembangkan kalau mau menampilkan filter) --}}
    <table class="meta">
        <tr>
            <td class="meta-label">Total data</td>
            <td>: {{ $logs->count() }} aktivitas</td>
        </tr>
    </table>

    {{-- TABEL UTAMA --}}
    <table>
        <thead>
            <tr>
                <th class="col-tanggal">Tanggal</th>
                <th class="col-jam">Jam</th>
                <th class="col-user">User</th>
                <th class="col-role">Role</th>
                <th class="col-modul">Modul</th>
                <th class="col-aksi">Aksi</th>
                <th class="col-desc">Ringkasan Aktivitas</th>
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

                $message     = $to['message'] ?? null;
                $entityLabel = $to['entity_label'] ?? null;
                $reason      = $to['reason'] ?? null;
                $amount      = $to['amount'] ?? $from['amount'] ?? null;

                $roleNames   = $log->actor ? $log->actor->roles->pluck('name')->toArray() : [];
                $prettyRoles = collect($roleNames)->map(fn($r) => ucfirst($r))->implode(', ');

                $classYearName = $log->classYear?->name;
                $humanAction   = $actionLabels[$log->action] ?? null;

                $baseDescription = $humanAction ?: $message ?: ucfirst(str_replace('_', ' ', $log->action));

                // Susun detail tambahan dalam satu blok yang rapi
                $detailLines = [];

                if ($entityLabel) {
                    $detailLines[] = "Objek: {$entityLabel}";
                }

                if ($amount) {
                    $detailLines[] = 'Nominal: Rp' . number_format($amount, 0, ',', '.');
                }

                if ($reason) {
                    $detailLines[] = 'Alasan: ' . $reason;
                }

                if ($classYearName) {
                    $detailLines[] = 'Tahun ajaran: ' . $classYearName;
                }

                $detailText = implode(' | ', $detailLines);
            @endphp
            <tr class="{{ $loop->odd ? 'striped' : '' }}">
                {{-- Tanggal --}}
                <td class="text-center">
                    {{ $log->created_at?->format('d-m-Y') ?? '-' }}
                </td>

                {{-- Jam --}}
                <td class="text-center">
                    {{ $log->created_at?->format('H:i') ?? '' }}
                </td>

                {{-- User --}}
                <td>
                    {{ $log->actor->name ?? '-' }}<br>
                    <span class="small text-muted">
                        {{ $log->actor->email ?? '-' }}
                    </span>
                </td>

                {{-- Role --}}
                <td>
                    @if($prettyRoles)
                        {{ $prettyRoles }}
                    @else
                        <span class="text-muted small">-</span>
                    @endif
                </td>

                {{-- Modul --}}
                <td>{{ $moduleLabel }}</td>

                {{-- Aksi --}}
                <td>{{ $actionName }}</td>

                {{-- Ringkasan aktivitas --}}
                <td>
                    {{-- Kalimat utama --}}
                    <strong>{{ $baseDescription }}</strong>

                    {{-- Detail tambahan digabung dalam satu baris kecil --}}
                    @if($detailText)
                        <br>
                        <span class="small text-muted">
                            {{ $detailText }}
                        </span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">
                    Tidak ada aktivitas yang dapat ditampilkan.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
