<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\UnpaidReason;
use App\Services\CashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnpaidReasonController extends Controller
{
    public function __construct(private CashService $svc) {}

    // ============================
    // HALAMAN KHUSUS (opsional)
    // ============================
    public function index(Request $request)
    {
        $user   = $request->user();
        $year   = $this->svc->getActiveYear();
        $period = $year ? $this->svc->getOpenPeriod($year) : null;
        $nominal = $year ? $this->svc->nominal($year) : 0;

        if (!$year || !$period || $nominal <= 0) {
            return view('unpaid.index', [
                'year'       => $year,
                'period'     => $period,
                'nominal'    => $nominal,
                'rows'       => collect(),
                'reasonsMap' => collect(),
            ]);
        }

        $enrollments = $this->svc->activeEnrollments($year);

        $reasonsMap = UnpaidReason::where('class_year_id', $year->id)
            ->where('period_id', $period->id)
            ->pluck('reason', 'enrollment_id');

        $rows = $enrollments->map(function ($en) use ($period, $nominal, $reasonsMap) {
            $total  = $this->svc->totalPaidForEnrollment($period, $en->id);
            $status = $this->svc->statusForStudent($nominal, $total);
            $reason = $reasonsMap[$en->id] ?? null;

            return (object) [
                'enrollment' => $en,
                'user'       => $en->user,
                'total'      => $total,
                'status'     => $status,
                'reason'     => $reason,
            ];
        })->filter(fn ($r) => $r->status === 'BELUM');

        try {
            ActivityLog::create([
                'class_year_id' => $year->id,
                'actor_id'      => $user?->id,
                'action'        => 'unpaid_reason.view_form',
                'entity_type'   => 'cash_period',
                'entity_id'     => $period->id,
                'from_json'     => null,
                'to_json'       => [
                    'period_id'   => $period->id,
                    'week_no'     => $period->week_no,
                    'belum_count' => $rows->count(),
                    'nominal'     => $nominal,
                ],
            ]);
        } catch (\Throwable $e) {}

        return view('unpaid.index', [
            'year'       => $year,
            'period'     => $period,
            'nominal'    => $nominal,
            'rows'       => $rows,
            'reasonsMap' => $reasonsMap,
        ]);
    }

  
    public function store(Request $request)
{
    $user   = $request->user();
    $year   = $this->svc->getActiveYear();
    $period = $year ? $this->svc->getOpenPeriod($year) : null;

    if (!$year || !$period) {
        return back()->withErrors('Tahun ajaran atau periode aktif tidak ditemukan.');
    }

    /**
     * MODE 1: SINGLE (dipanggil dari modal di halaman KAS)
     * -> kirim: enrollment_id + reason
     * route: cash.unpaidReason.store (POST /kas/unpaid-reason)
     */
    if ($request->has('enrollment_id')) {

        $data = $request->validate([
            'enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'reason'        => ['required', 'string', 'max:255'],
        ]);

        $enrollmentId = (int) $data['enrollment_id'];

        // snapshot sebelum (untuk log)
        $before = UnpaidReason::where('class_year_id', $year->id)
            ->where('period_id', $period->id)
            ->where('enrollment_id', $enrollmentId)
            ->first();

        $beforeArr = $before
            ? $before->only(['enrollment_id', 'reason', 'set_by'])
            : null;

        // upsert 1 baris (SELALU isi set_by)
        $record = UnpaidReason::updateOrCreate(
            [
                'class_year_id' => $year->id,
                'period_id'     => $period->id,
                'enrollment_id' => $enrollmentId,
            ],
            [
                'reason'        => $data['reason'],
                'set_by'        => $user?->id,   // ⬅️ PENTING
            ]
        );

        // snapshot sesudah
        $afterArr = $record->only(['enrollment_id', 'reason', 'set_by']);

        // LOG
        try {
            ActivityLog::create([
                'class_year_id' => $year->id,
                'actor_id'      => $user?->id,
                'action'        => 'unpaid_reason.save_single',
                'entity_type'   => 'student_enrollment',
                'entity_id'     => $enrollmentId,
                'from_json'     => $beforeArr,
                'to_json'       => $afterArr,
            ]);
        } catch (\Throwable $e) {}

        return back()->with('success', 'Alasan belum bayar disimpan.');
    }

    /**
     * MODE 2: BULK (halaman khusus, kirim reasons[enrollment_id] = "...")
     */
    $data = $request->validate([
        'reasons'   => ['nullable', 'array'],
        'reasons.*' => ['nullable', 'string', 'max:255'],
    ]);

    $reasons = $data['reasons'] ?? [];

    // Snapshot sebelum untuk seluruh periode
    $before = UnpaidReason::where('class_year_id', $year->id)
        ->where('period_id', $period->id)
        ->get()
        ->map(fn ($r) => $r->only(['enrollment_id', 'reason', 'set_by']))
        ->values()
        ->all();

    DB::transaction(function () use ($year, $period, $reasons, $user) {
        // HAPUS DULU SEMUA alasan periode ini (khusus mode bulk)
        UnpaidReason::where('class_year_id', $year->id)
            ->where('period_id', $period->id)
            ->delete();

        $insertRows = [];
        foreach ($reasons as $enrollmentId => $reasonText) {
            $reasonText = trim((string) $reasonText);
            if ($reasonText === '') {
                continue;
            }

            $insertRows[] = [
                'class_year_id'  => $year->id,
                'period_id'      => $period->id,
                'enrollment_id'  => (int) $enrollmentId,
                'reason'         => $reasonText,
                'set_by'         => $user?->id,   // ⬅️ DIISI JUGA
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        if (!empty($insertRows)) {
            UnpaidReason::insert($insertRows);
        }
    });

    // Snapshot sesudah
    $after = UnpaidReason::where('class_year_id', $year->id)
        ->where('period_id', $period->id)
        ->get()
        ->map(fn ($r) => $r->only(['enrollment_id', 'reason', 'set_by']))
        ->values()
        ->all();

    // LOG: simpan massal
    try {
        ActivityLog::create([
            'class_year_id' => $year->id,
            'actor_id'      => $user?->id,
            'action'        => 'unpaid_reason.save_bulk',
            'entity_type'   => 'cash_period',
            'entity_id'     => $period->id,
            'from_json'     => $before,
            'to_json'       => $after,
        ]);
    } catch (\Throwable $e) {}

    return back()->with('success', 'Alasan belum bayar disimpan.');
}

}
