<?php

namespace App\Http\Controllers;

use App\Exports\ActivityLogsExport;
use App\Models\ActivityLog;
use App\Models\ClassYear;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ActivityLogController extends Controller
{
    /**
     * Tampilkan list activity log dengan filter + tahun aktif.
     */
    public function index(Request $request)
    {
        $activeYear   = ClassYear::active()->latest('id')->first();
        $modules      = $this->getModules();
        $actionLabels = $this->getActionLabels();

        // 🔹 Query dasar (tahun ajaran + relasi)
        $baseQuery = $this->buildBaseQuery($activeYear);

        // 🔹 Cloning untuk filter + pagination
        $query = clone $baseQuery;
        $this->applyFilters($query, $request, $modules);

        // 🔹 Pagination
        $logs = $query->paginate(20)->withQueryString();

        // 🔹 Daftar action unik untuk dropdown (hanya filter tahun aktif)
        $availableActions = ActivityLog::query()
            ->when($activeYear, function ($q) use ($activeYear) {
                $q->where(function ($q) use ($activeYear) {
                    $q->where('class_year_id', $activeYear->id)
                        ->orWhereNull('class_year_id');
                });
            })
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('activity_logs.index', [
            'logs'             => $logs,
            'modules'          => $modules,
            'availableActions' => $availableActions,
            'actionLabels'     => $actionLabels,
            'activeYear'       => $activeYear,
        ]);
    }

    /**
     * Export activity log ke Excel (mengikuti semua filter).
     */
    public function exportExcel(Request $request)
    {
        $activeYear   = ClassYear::active()->latest('id')->first();
        $modules      = $this->getModules();
        $actionLabels = $this->getActionLabels();

        $query = $this->buildBaseQuery($activeYear);
        $this->applyFilters($query, $request, $modules);

        $logs = $query->get();

        $fileName = 'activity_logs_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new ActivityLogsExport($logs, $modules, $actionLabels), $fileName);
    }

    /**
     * Export activity log ke PDF (mengikuti semua filter).
     */
    public function exportPdf(Request $request)
    {
        $activeYear   = ClassYear::active()->latest('id')->first();
        $modules      = $this->getModules();
        $actionLabels = $this->getActionLabels();

        $query = $this->buildBaseQuery($activeYear);
        $this->applyFilters($query, $request, $modules);

        $logs = $query->get();

        $pdf = Pdf::loadView('activity_logs.pdf', [
            'logs'         => $logs,
            'modules'      => $modules,
            'actionLabels' => $actionLabels,
            'activeYear'   => $activeYear,
        ])->setPaper('a4', 'portrait');

        $fileName = 'activity_logs_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }

    // ======================
    // 🔻 Helper functions 🔻
    // ======================

    /**
     * Daftar modul (prefix action).
     */
    private function getModules(): array
    {
        return [
            'expense_request' => 'Pengajuan Pengeluaran',
            'cash_expense'    => 'Pengeluaran Kas',
            'cash_payment'    => 'Kas Masuk',
            'period'          => 'Periode Kas',
            'year'            => 'Tahun Ajaran',
            'category'        => 'Kategori',
            'student'         => 'Data Siswa',
            'profile'         => 'Profil & Akun',
            'unpaid_reason'   => 'Alasan Belum Bayar',
        ];
    }

    /**
     * Mapping action → kalimat manusiawi.
     */
    private function getActionLabels(): array
    {
        return [
            // Expense Request
            'expense_request.created'   => 'Bendahara mengajukan pengeluaran',
            'expense_request.approved'  => 'Guru menyetujui pengajuan pengeluaran',
            'expense_request.rejected'  => 'Guru menolak pengajuan pengeluaran',
            'expense_request.deleted'   => 'Pengajuan pengeluaran dihapus',

            // Cash Expense
            'cash_expense.created'      => 'Pengeluaran kas dicatat',
            'cash_expense.deleted'      => 'Pengeluaran kas dihapus',

            // Cash Payment
            'cash_payment.create'       => 'Bendahara mencatat kas masuk',
            'cash_payment.update'       => 'Kas masuk diperbarui',
            'cash_payment.deleted'      => 'Kas masuk dihapus',

            // Period
            'period.create_today'       => 'Periode kas minggu ini dibuat & dibuka',
            'period.create_custom'      => 'Periode kas kustom dibuat & dibuka',
            'period.open'               => 'Periode kas dibuka',
            'period.close'              => 'Periode kas ditutup',

            // Year
            'year.created'              => 'Tahun ajaran baru dibuat',
            'year.activate'             => 'Tahun ajaran diaktifkan',
            'year.close'                => 'Tahun ajaran ditutup',

            // Category
            'category.created'          => 'Kategori baru ditambahkan',
            'category.updated'          => 'Kategori diperbarui',
            'category.deleted'          => 'Kategori dihapus',

            // Unpaid Reason
            'unpaid_reason.view_form'   => 'Guru/bendahara membuka form alasan belum bayar',
            'unpaid_reason.save'        => 'Alasan belum bayar disimpan',

            // Profile & Akun
            'profile.update'            => 'Profil akun diperbarui',
            'profile.deleted'           => 'Akun pengguna dihapus',
            'profile.password_update'   => 'Password akun diubah',
        ];
    }

    /**
     * Query dasar (relasi + filter tahun aktif).
     */
    private function buildBaseQuery(?ClassYear $activeYear)
    {
        $query = ActivityLog::with(['actor.roles', 'classYear'])
            ->orderByDesc('created_at');

        if ($activeYear) {
            $query->where(function ($q) use ($activeYear) {
                $q->where('class_year_id', $activeYear->id)
                    ->orWhereNull('class_year_id');
            });
        }

        return $query;
    }

    /**
     * Terapkan semua filter dari request ke query.
     */
    private function applyFilters($query, Request $request, array $modules): void
    {
        // Modul
        if ($request->filled('module') && isset($modules[$request->module])) {
            $query->where('action', 'like', $request->module . '.%');
        }

        // Action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Role (guru / bendahara / siswa)
        if ($request->filled('role')) {
            $role = $request->role;

            $query->whereHas('actor.roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Range tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Hanya aktivitas penghapusan
        if ($request->boolean('only_deleted')) {
            $query->where(function ($q) {
                $q->where('action', 'like', '%.deleted')
                    ->orWhere('action', 'like', '%.delete');
            });
        }

        // Pencarian
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', '%' . $search . '%')
                    ->orWhere('entity_type', 'like', '%' . $search . '%')
                    ->orWhere('to_json->message', 'like', '%' . $search . '%')
                    ->orWhere('to_json->entity_label', 'like', '%' . $search . '%');
            });
        }
    }
}
