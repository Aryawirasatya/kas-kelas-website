<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePeriodRequest;
use App\Models\CashPeriod;
use App\Models\ClassYear;
use App\Models\ActivityLog; // sesuaikan kalau nama model log beda
use App\Services\PeriodService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PeriodController extends Controller
{
    public function __construct(private PeriodService $service) {}

    /** Halaman periode untuk tahun aktif */
    public function index(Request $request)
    {
        $activeYear  = ClassYear::with('setting')->active()->latest('id')->first();
        $periods     = collect();
        $currentOpen = null;

        if ($activeYear) {
            $periods     = $activeYear->periods()->orderBy('week_no')->get();
            $currentOpen = $this->service->currentOpen($activeYear);
        }

        return view('periods.index', compact('activeYear', 'periods', 'currentOpen'));
    }

    /**
     * OPS A: Buat periode "minggu ini" secara cepat.
     * - date_start = today (atau minggu depan kalau mode testing)
     * - date_end   = date_start + 4 hari
     * - langsung OPEN, dan pastikan hanya ada 1 OPEN.
     * - Cegah overlap range tanggal dengan periode lain di tahun ini.
     */
    public function createToday(Request $request)
    {
        $year = ClassYear::active()->latest('id')->firstOrFail();

        // ==============================
        // MODE PRODUKSI (pakai HARI INI)
        // ==============================
        $start = Carbon::today();

        // ==============================
        // MODE TEST (seolah MINGGU DEPAN)
        // ==============================
        // kalau mau seolah-olah minggu depan:
        // $start = Carbon::today()->addWeek();  
        $end   = (clone $start)->addDays(4);

        try {
            // Buat & OPEN (cek overlap + single-open ada di PeriodService::createOneWeek)
            $period = $this->service->createOneWeek($year, $start, $end, openImmediately: true);
        } catch (\RuntimeException $e) {
            // Misal: "Rentang tanggal minggu baru bertabrakan dengan periode lain."
            return back()->withErrors($e->getMessage());
        } catch (\Throwable $e) {
            // Guard tambahan kalau ada error lain tak terduga
            return back()->withErrors('Gagal membuat periode baru: '.$e->getMessage());
        }

        // LOG (opsional)
        try {
            ActivityLog::create([
                'actor_id'  => $request->user()->id ?? null,
                'action'    => 'period.create_today',
                'entity'    => 'cash_period',
                'entity_id' => $period->id,
                'meta'      => json_encode([
                    'week_no'    => $period->week_no,
                    'date_start' => $period->date_start->toDateString(),
                    'date_end'   => $period->date_end->toDateString(),
                ]),
            ]);
        } catch (\Throwable $e) {}

        return back()->with(
            'success',
            "Periode baru dibuat & dibuka: #{$period->week_no} ({$period->date_start->format('d M')}–{$period->date_end->format('d M Y')})."
        );
    }

    /**
     * OPS A: Buat periode custom (tanggal mulai & akhir dari user).
     */
    public function createCustom(CreatePeriodRequest $request)
    {
        $year  = ClassYear::active()->latest('id')->firstOrFail();
        $start = Carbon::parse($request->date_start)->startOfDay();
        $end   = $request->filled('date_end')
            ? Carbon::parse($request->date_end)->endOfDay()
            : (clone $start)->addDays(4)->endOfDay();

        try {
            $period = $this->service->createOneWeek($year, $start, $end, openImmediately: true);
        } catch (\RuntimeException $e) {
            return back()->withErrors($e->getMessage());
        } catch (\Throwable $e) {
            return back()->withErrors('Gagal membuat periode baru: '.$e->getMessage());
        }

        try {
            ActivityLog::create([
                'actor_id'  => $request->user()->id ?? null,
                'action'    => 'period.create_custom',
                'entity'    => 'cash_period',
                'entity_id' => $period->id,
                'meta'      => json_encode([
                    'week_no'    => $period->week_no,
                    'date_start' => $period->date_start->toDateString(),
                    'date_end'   => $period->date_end->toDateString(),
                ]),
            ]);
        } catch (\Throwable $e) {}

        return back()->with(
            'success',
            "Periode kustom dibuat & dibuka: #{$period->week_no} ({$period->date_start->format('d M')}–{$period->date_end->format('d M Y')})."
        );
    }

    /** Buka 1 periode (guard: milik tahun aktif & single-open) */
    public function open(Request $request, CashPeriod $period)
    {
        $year = ClassYear::active()->latest('id')->firstOrFail();
        if ((int)$period->class_year_id !== (int)$year->id) {
            return back()->withErrors('Periode tidak termasuk tahun ajaran aktif.');
        }

        try {
            $this->service->open($year, $period);
        } catch (\RuntimeException $e) {
            return back()->withErrors('Gagal membuka periode: '.$e->getMessage());
        } catch (\Throwable $e) {
            return back()->withErrors('Gagal membuka periode: '.$e->getMessage());
        }

        try {
            ActivityLog::create([
                'actor_id'  => $request->user()->id ?? null,
                'action'    => 'period.open',
                'entity'    => 'cash_period',
                'entity_id' => $period->id,
                'meta'      => json_encode([
                    'week_no'    => $period->week_no,
                    'date_start' => $period->date_start,
                    'date_end'   => $period->date_end,
                ]),
            ]);
        } catch (\Throwable $e) {}

        return back()->with('success', "Periode #{$period->week_no} dibuka.");
    }

    /** Tutup 1 periode (guard: alasan BELUM harus ada) */
    public function close(Request $request, CashPeriod $period)
    {
        $year    = ClassYear::active()->latest('id')->firstOrFail();
        $nominal = (int) ($year->setting?->kas_nominal ?? 0);

        // Wajib: semua yang BELUM harus punya alasan
        $missing = $this->service->unpaidWithoutReason($year->id, $period->id, $nominal);
        if ($missing->isNotEmpty()) {
            $names = $missing->pluck('user.name')->implode(', ');
            return back()->withErrors("Tidak bisa menutup periode. Alasan belum diisi untuk: {$names}.");
        }

        if ((int)$period->class_year_id !== (int)$year->id) {
            return back()->withErrors('Periode tidak termasuk tahun ajaran aktif.');
        }

        try {
            $this->service->close($period);
        } catch (\Throwable $e) {
            return back()->withErrors('Gagal menutup periode: '.$e->getMessage());
        }

        try {
            ActivityLog::create([
                'actor_id'  => $request->user()->id ?? null,
                'action'    => 'period.close',
                'entity'    => 'cash_period',
                'entity_id' => $period->id,
                'meta'      => json_encode([
                    'week_no'    => $period->week_no,
                    'date_start' => $period->date_start,
                    'date_end'   => $period->date_end,
                ]),
            ]);
        } catch (\Throwable $e) {}

        return back()->with('success', "Periode #{$period->week_no} ditutup.");
    }
}
