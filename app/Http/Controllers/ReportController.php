<?php

namespace App\Http\Controllers;

use App\Exports\ClassReportExport;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PersonalHistoryExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        // Middleware TIDAK dipakai di sini
        // Karena kamu sudah pakai middleware di routes/web.php
        $this->reportService = $reportService;
    }

    /**
     * Halaman laporan utama.
     */
    public function index(Request $request)
{
    $user = Auth::user();

    $classYear = $this->reportService->getActiveClassYearForUser($user);

    if (!$classYear) {
        return redirect()
            ->back()
            ->with('error', 'Belum ada tahun ajaran aktif yang terhubung dengan akun Anda.');
    }

    $report = $this->reportService->buildClassReport($classYear, $user);

    $role        = $report['role'] ?? $user->getRoleNames()->first();
    $periods     = collect($report['periods'] ?? []);
    $categories  = collect($report['categories'] ?? []);
    $chart       = $report['chart'] ?? null;

    // ============ BACA FILTER DARI QUERY ============
    $filterMonth      = $request->query('month');        // format 'Y-m'
    $filterPeriodId   = $request->query('period_id');    // id periode
    $filterCategoryId = $request->query('category_id');  // id kategori

    // Filter periode berdasarkan bulan
    if ($filterMonth) {
        $periods = $periods->filter(function ($p) use ($filterMonth) {
            if (empty($p['date_start'])) return false;
            $monthKey = \Illuminate\Support\Carbon::parse($p['date_start'])->format('Y-m');
            return $monthKey === $filterMonth;
        });
    }

    // Filter periode berdasarkan id
    if ($filterPeriodId) {
        $periods = $periods->where('id', (int) $filterPeriodId);
    }

    // Filter kategori pengeluaran
    if ($filterCategoryId) {
        $categories = $categories->where('category_id', (int) $filterCategoryId);
    }

    // ============ OPTIONS DROPDOWN ============
    // Opsi bulan: ambil dari chart (months_keys + labels)
    $monthOptions = [];
    if ($chart && !empty($chart['months_keys'] ?? [])) {
        foreach ($chart['months_keys'] as $idx => $ym) {
            $label = $chart['labels'][$idx] ?? $ym;
            $monthOptions[] = [
                'value' => $ym,
                'label' => $label,
            ];
        }
    }

    // Opsi periode (Minggu ke-x)
    $periodOptions = $report['periods'] ?? [];

    // Opsi kategori (dari data kategori yang sudah di-aggregate)
    $categoryOptions = $report['categories'] ?? [];

    return view('reports.index', [
        'user'            => $user,
        'report'          => $report,
        'role'            => $role,
        'periods'         => $periods->values()->all(),
        'categories'      => $categories->values()->all(),
        'monthOptions'    => $monthOptions,
        'periodOptions'   => $periodOptions,
        'categoryOptions' => $categoryOptions,
        'chart'           => $chart,
        'filters'         => [
            'month'       => $filterMonth,
            'period_id'   => $filterPeriodId,
            'category_id' => $filterCategoryId,
        ],
    ]);
}


    /**
     * Export laporan ke Excel (khusus guru & bendahara).
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $role = $user->getRoleNames()->first();

        if (!in_array($role, ['guru', 'bendahara'])) {
            abort(403, 'Hanya guru dan bendahara yang boleh mengekspor laporan.');
        }

        $classYear = $this->reportService->getActiveClassYearForUser($user);

        if (!$classYear) {
            return redirect()
                ->back()
                ->with('error', 'Belum ada tahun ajaran aktif untuk diekspor.');
        }

        $report = $this->reportService->buildClassReport($classYear, $user);

        $fileName = 'laporan-kas-' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new ClassReportExport($report), $fileName);
    }

    /**
     * Export laporan ke PDF (khusus guru & bendahara).
     */
    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        $role = $user->getRoleNames()->first();

        if (!in_array($role, ['guru', 'bendahara'])) {
            abort(403, 'Hanya guru dan bendahara yang boleh mengekspor laporan.');
        }

        $classYear = $this->reportService->getActiveClassYearForUser($user);

        if (!$classYear) {
            return redirect()
                ->back()
                ->with('error', 'Belum ada tahun ajaran aktif untuk diekspor.');
        }

        $report = $this->reportService->buildClassReport($classYear, $user);

        $pdf = Pdf::loadView('reports.export_pdf', [
            'report'     => $report,
            'summary'    => $report['summary'] ?? [],
            'periods'    => $report['periods'] ?? [],
            'categories' => $report['categories'] ?? [],
            'classYear'  => $report['classYear'] ?? null,
        ])->setPaper('a4', 'portrait');

        $fileName = 'laporan-kas-' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }

    public function exportPersonalExcel(Request $request)
{
    $user = Auth::user();
    $role = $user->getRoleNames()->first();

    if ($role !== 'siswa') {
        abort(403, 'Export riwayat pribadi hanya untuk siswa.');
    }

    $classYear = $this->reportService->getActiveClassYearForUser($user);
    if (!$classYear) {
        return redirect()->back()->with('error', 'Belum ada tahun ajaran aktif untuk diekspor.');
    }

    $report = $this->reportService->buildClassReport($classYear, $user);
    $history = $report['personalHistory'] ?? [];

    $fileName = 'riwayat-kas-saya-' . now()->format('Ymd_His') . '.xlsx';

    return Excel::download(
        new PersonalHistoryExport(
            $history,
            $user->name,
            $classYear->class_label ?? null,
            $classYear->academic_year ?? null
        ),
        $fileName
    );
}

public function exportPersonalPdf(Request $request)
{
    $user = Auth::user();
    $role = $user->getRoleNames()->first();

    if ($role !== 'siswa') {
        abort(403, 'Export riwayat pribadi hanya untuk siswa.');
    }

    $classYear = $this->reportService->getActiveClassYearForUser($user);
    if (!$classYear) {
        return redirect()->back()->with('error', 'Belum ada tahun ajaran aktif untuk diekspor.');
    }

    $report = $this->reportService->buildClassReport($classYear, $user);
    $history = $report['personalHistory'] ?? [];

    $pdf = Pdf::loadView('reports.personal_pdf', [
        'history'      => $history,
        'studentName'  => $user->name,
        'classLabel'   => $classYear->class_label ?? null,
        'academicYear' => $classYear->academic_year ?? null,
    ])->setPaper('a4', 'portrait');

    $fileName = 'riwayat-kas-saya-' . now()->format('Ymd_His') . '.pdf';

    return $pdf->download($fileName);
}

}
