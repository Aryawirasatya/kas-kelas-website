<?php

namespace App\Services;

use App\Models\CashExpense;
use App\Models\CashPayment;
use App\Models\CashPeriod;
use App\Models\Category;
use App\Models\ClassSetting;
use App\Models\ClassYear;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ReportService
{
    public function getActiveClassYearForUser(User $user): ?ClassYear
    {
        $role = $user->getRoleNames()->first();

        if ($role === 'guru') {
            return ClassYear::where('homeroom_user_id', $user->id)
                ->where('status', 'active')
                ->first();
        }

        if (in_array($role, ['bendahara', 'siswa'])) {
            return ClassYear::where('status', 'active')
                ->whereHas('enrollments', function ($q) use ($user) {
                    $q->where('student_user_id', $user->id);
                })
                ->first();
        }

        return null;
    }

    public function buildClassReport(ClassYear $year, User $user): array
    {
        $role = $user->getRoleNames()->first();

        $setting = ClassSetting::where('class_year_id', $year->id)->first();
        $kasNominal = $setting?->kas_nominal ?? 0;

        $activeStudentsCount = StudentEnrollment::where('class_year_id', $year->id)
            ->where('is_active', 1)
            ->count();

        $periods = CashPeriod::where('class_year_id', $year->id)
            ->orderBy('date_start')
            ->get();

        $payments = CashPayment::where('class_year_id', $year->id)->get();
        $expenses = CashExpense::where('class_year_id', $year->id)->get();

        // ====== 1. RINGKASAN KAS ======
        $totalIncome = $payments->sum('amount');
        $totalExpense = $expenses->sum('amount');
        $balance = $totalIncome - $totalExpense;

        $totalTarget = $periods->count() * $activeStudentsCount * $kasNominal;
        $totalArrears = max($totalTarget - $totalIncome, 0);

        $summary = [
            'total_income'   => $totalIncome,
            'total_expense'  => $totalExpense,
            'balance'        => $balance,
            'period_count'   => $periods->count(),
            'student_count'  => $activeStudentsCount,
            'kas_nominal'    => $kasNominal,
            'total_target'   => $totalTarget,
            'total_arrears'  => $totalArrears,
        ];

        // ====== 2. REKAP PER PERIODE ======
        $periodSummaries = [];

        foreach ($periods as $period) {
            $target = $activeStudentsCount * $kasNominal;

            $periodPayments = $payments->where('period_id', $period->id);
            $paidAmount = $periodPayments->sum('amount');

            $arrears = max($target - $paidAmount, 0);
            $completion = $target > 0
                ? round(($paidAmount / $target) * 100, 2)
                : 0;

            $paidEnrollmentIds = $periodPayments->pluck('enrollment_id')->unique();
            $paidStudentsCount = $paidEnrollmentIds->count();
            $unpaidStudentsCount = max($activeStudentsCount - $paidStudentsCount, 0);

            $periodSummaries[] = [
                'id'                     => $period->id,
                'label'                  => "Minggu ke-{$period->week_no}",
                'date_start'             => $period->date_start,
                'date_end'               => $period->date_end,
                'target'                 => $target,
                'paid'                   => $paidAmount,
                'arrears'                => $arrears,
                'completion_percent'     => $completion,
                'paid_students_count'    => $paidStudentsCount,
                'unpaid_students_count'  => $unpaidStudentsCount,
            ];
        }

        // ====== 3. PENGELUARAN PER KATEGORI ======
        $expensesByCategory = [];
        if ($expenses->isNotEmpty()) {
            $grouped = $expenses->groupBy('category_id');
            foreach ($grouped as $categoryId => $rows) {
                $category = Category::find($categoryId);
                $expensesByCategory[] = [
                    'category_id'   => $categoryId,
                    'category_name' => $category?->name ?? 'Tanpa Kategori',
                    'total'         => $rows->sum('amount'),
                ];
            }
        }

        // ====== 4. DATA AGREGAT SISWA (per periode) ======
        $studentAggregates = [
            'per_period' => $periodSummaries,
        ];

        // ====== 5. RIWAYAT PRIBADI SISWA ======
        $personalHistory = null;
        if ($role === 'siswa') {
            $enrollment = StudentEnrollment::where('class_year_id', $year->id)
                ->where('student_user_id', $user->id)
                ->first();

            if ($enrollment) {
                $personalPayments = $payments
                    ->where('enrollment_id', $enrollment->id)
                    ->sortBy('date');

                $periodsById = $periods->keyBy('id');

                $personalHistory = $personalPayments->map(function (CashPayment $payment) use ($periodsById, $kasNominal) {
                    $period = $periodsById->get($payment->period_id);

                    $status = $payment->amount >= $kasNominal
                        ? 'Lunas'
                        : 'Belum Lunas Penuh';

                    return [
                        'date'         => $payment->date,
                        'amount'       => $payment->amount,
                        'status'       => $status,
                        'period_id'    => $payment->period_id,
                        'period_label' => $period
                            ? "Minggu ke-{$period->week_no}"
                            : 'Periode Tidak Dikenal',
                    ];
                })->values()->all();
            }
        }

        // ====== 6. DATA UNTUK CHART BULANAN ======
        $monthlyIncome = [];
        $monthlyExpense = [];

        foreach ($payments as $pay) {
            if (!$pay->date) continue;
            $monthKey = Carbon::parse($pay->date)->format('Y-m');
            $monthlyIncome[$monthKey] = ($monthlyIncome[$monthKey] ?? 0) + $pay->amount;
        }

        foreach ($expenses as $exp) {
            if (!$exp->date) continue;
            $monthKey = Carbon::parse($exp->date)->format('Y-m');
            $monthlyExpense[$monthKey] = ($monthlyExpense[$monthKey] ?? 0) + $exp->amount;
        }

        $allMonths = collect(array_keys($monthlyIncome) + array_keys($monthlyExpense))
            ->unique()
            ->sort()
            ->values();

        $chartLabels = $allMonths->map(function (string $ym) {
            return Carbon::createFromFormat('Y-m', $ym)->translatedFormat('M Y');
        })->all();

        $chartIncome = $allMonths->map(function (string $ym) use ($monthlyIncome) {
            return $monthlyIncome[$ym] ?? 0;
        })->all();

        $chartExpense = $allMonths->map(function (string $ym) use ($monthlyExpense) {
            return $monthlyExpense[$ym] ?? 0;
        })->all();

        $chart = [
            'months_keys' => $allMonths->all(),  // contoh: ['2025-01', '2025-02', ...]
            'labels'      => $chartLabels,       // contoh: ['Jan 2025', 'Feb 2025', ...]
            'income'      => $chartIncome,
            'expense'     => $chartExpense,
        ];

        return [
            'role'              => $role,
            'classYear'         => $year,
            'summary'           => $summary,
            'periods'           => $periodSummaries,
            'categories'        => $expensesByCategory,
            'studentAggregates' => $studentAggregates,
            'personalHistory'   => $personalHistory,
            'chart'             => $chart,
        ];
    }
}
