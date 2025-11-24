<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Models\ClassYear;
use App\Models\ClassSetting;
use App\Models\StudentEnrollment;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use App\Http\Requests\StoreClassYearRequest;
use App\Http\Requests\UpdateClassSettingRequest;

class YearController extends Controller
{
    // ====== INDEX: daftar tahun + tahun aktif ======
    public function index()
{
    $u = auth()->user();

    // Hanya tahun milik guru ini
    $years = ClassYear::where('homeroom_user_id', $u->id)
        ->latest('id')
        ->get();

    // Tahun aktif guru ini (kalau ada)
    $activeYear = ClassYear::with('setting')
        ->where('homeroom_user_id', $u->id)
        ->active()
        ->latest('id')
        ->first();

    return view('year.index', compact('years', 'activeYear'));
}


      public function storeDraft(StoreClassYearRequest $request)
{
    $u    = $request->user();
    $data = $request->validated();

    // 1. Pastikan guru ini tidak punya tahun ajaran DRAFT/ACTIVE lain
    $hasNonArchived = ClassYear::where('homeroom_user_id', $u->id)
        ->whereIn('status', ['draft', 'active'])
        ->exists();

    if ($hasNonArchived) {
        return back()
            ->withErrors([
                'academic_year' => 'Kamu masih punya tahun ajaran berstatus DRAFT/ACTIVE. '
                    . 'Tutup atau arsipkan dulu sebelum membuat tahun ajaran baru.',
            ])
            ->withInput();
    }

    // 2. Buat draft baru + setting default
    $year = DB::transaction(function () use ($data, $u) {
        $year = ClassYear::create([
            'school_name'      => $data['school_name'] ?? null,
            'class_label'      => $data['class_label'],
            'level'            => $data['level'],
            'academic_year'    => $data['academic_year'],
            'homeroom_name'    => $u->name,
            'homeroom_user_id' => $u->id,
            'status'           => 'draft',
        ]);

        $year->setting()->create([
            'kas_nominal'  => 0,
            'periode'      => 'mingguan',
        ]);

        return $year;
    });

    return redirect()
        ->route('year.setting', $year)
        ->with('success', 'Draft tahun ajaran dibuat. Lanjut atur nominal kas.');
}


    // ====== STEP 2: edit nominal ======
    public function editSetting(ClassYear $year)
    {
        abort_if(!in_array($year->status, ['draft', 'active']), 403);

        return view('year.setting', compact('year'));
    }

    public function updateSetting(UpdateClassSettingRequest $request, ClassYear $year)
    {
        abort_if(!in_array($year->status, ['draft', 'active']), 403);

        $data = $request->validated();

        $year->setting()->update([
            'kas_nominal'  => $data['kas_nominal'],
        ]);

        $next = ($year->status === 'draft')
            ? route('year.students', $year)
            : route('year.setting', $year);

        return redirect($next)->with('success', 'Nominal disimpan.');
    }

    // ====== STEP 3: halaman siswa + bendahara ======
    public function students(ClassYear $year)
    {
        abort_if(!in_array($year->status, ['draft', 'active']), 403);

        $search = request('q');

        $enrolls = StudentEnrollment::with('user')
            ->where('class_year_id', $year->id)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->whereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('nisn', 'like', "%{$search}%")
                          ->orWhere('gender', 'like', "%{$search}%");
                    })->orWhere('nis', 'like', "%{$search}%");
                });
            })
            ->when(request('only') === 'active', fn ($q) => $q->where('is_active', true))
            ->when(request('only') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when(request('only') === 'treasurer', fn ($q) => $q->where('is_treasurer', true))
            ->orderBy('id')
            ->paginate(10);

        $pickerEnrolls = StudentEnrollment::with('user')
            ->where('class_year_id', $year->id)
            ->orderBy('id')
            ->get();

        return view('year.students', compact('year', 'enrolls', 'pickerEnrolls'));
    }

    // Tambah siswa manual
    public function storeStudentManual(Request $request, ClassYear $year)
    {
        abort_if(!in_array($year->status, ['draft', 'active']), 403);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'nisn'     => ['nullable', 'string', 'max:30', 'unique:users,nisn'],
            'nis'      => ['nullable', 'string', 'max:30'],
            'gender'   => ['nullable', 'in:L,P'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        DB::transaction(function () use ($data, $year) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'nisn'     => $data['nisn'] ?? null,
                'password' => bcrypt($data['password']),
                'gender'   => $data['gender'] ?? null,
                'active'   => true,
            ]);

            // default tetap siswa
            $user->assignRole('siswa');

            StudentEnrollment::create([
                'class_year_id'   => $year->id,
                'student_user_id' => $user->id,
                'nis'             => $data['nis'] ?? null,
                'is_active'       => true,
                'is_treasurer'    => false,
            ]);
        });

        return back()->with('success', 'Siswa baru berhasil ditambahkan dan tersimpan di tabel users & enrollments.');
    }

    // Import siswa (hanya draft)
    public function importStudents(Request $request, ClassYear $year)
    {
        abort_if($year->status !== 'draft', 403);

        $request->validate([
            'csv' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ], [
            'csv.mimes' => 'File harus .xlsx, .xls, atau .csv',
        ]);

        $file = $request->file('csv');

        $import = new StudentsImport($year);
        Excel::import($import, $file);

        $fails = $import->getFailures();
        if (!empty($fails)) {
            $summary = collect($fails)->map(function ($f) {
                $row    = method_exists($f, 'row') ? $f->row() : '-';
                $errors = method_exists($f, 'errors') ? implode('; ', $f->errors()) : 'Invalid';
                return "Baris {$row}: {$errors}";
            })->take(10)->implode(' | ');

            return back()
                ->with('success', 'Import selesai dengan beberapa baris gagal divalidasi.')
                ->withErrors(['import' => 'Contoh kegagalan: ' . $summary]);
        }

        return back()->with('success', 'Import selesai.');
    }

    // Atur bendahara + sinkron role user
    public function assignTreasurers(Request $request, ClassYear $year)
    {
        abort_if(!in_array($year->status, ['draft', 'active']), 403);

        $data = $request->validate([
            'treasurer_1_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'treasurer_2_id' => ['nullable', 'integer', 'different:treasurer_1_id', 'exists:student_enrollments,id'],
        ], [
            'treasurer_1_id.required'  => 'Bendahara 1 wajib dipilih.',
            'treasurer_2_id.different' => 'Bendahara 1 dan Bendahara 2 harus berbeda.',
        ]);

        $enrollIds = array_values(array_filter([
            $data['treasurer_1_id'],
            $data['treasurer_2_id'] ?? null,
        ]));

        // Validasi: semua id bendahara harus milik tahun ini
        $cnt = StudentEnrollment::where('class_year_id', $year->id)
            ->whereIn('id', $enrollIds)
            ->count();

        if ($cnt !== count($enrollIds)) {
            return back()->withErrors('Pilihan bendahara tidak sesuai dengan tahun ajaran ini.');
        }

        DB::transaction(function () use ($year, $enrollIds) {
            // 1) Ambil semua enrollment tahun ini (dengan user) untuk reset
            $allEnrolls = StudentEnrollment::with('user')
                ->where('class_year_id', $year->id)
                ->get();

            // Reset is_treasurer + cabut role bendahara dari semua siswa di tahun ini
            foreach ($allEnrolls as $en) {
                $en->is_treasurer = false;
                $en->save();

                if ($en->user) {
                    // Jika tadinya bendahara tapi sekarang tidak terpilih lagi → cabut role bendahara
                    if ($en->user->hasRole('bendahara')) {
                        $en->user->removeRole('bendahara');
                    }

                    // Pastikan tetap punya role siswa
                    if (!$en->user->hasRole('siswa')) {
                        $en->user->assignRole('siswa');
                    }
                }
            }

            // 2) Set bendahara baru + beri role bendahara ke user-nya
            $treasurerEnrolls = StudentEnrollment::with('user')
                ->whereIn('id', $enrollIds)
                ->get();

            foreach ($treasurerEnrolls as $en) {
                $en->is_treasurer = true;
                $en->save();

                if ($en->user) {
                    // Pastikan dia siswa
                    if (!$en->user->hasRole('siswa')) {
                        $en->user->assignRole('siswa');
                    }
                    // Tambahkan role bendahara
                    if (!$en->user->hasRole('bendahara')) {
                        $en->user->assignRole('bendahara');
                    }
                }
            }
        });

        return back()->with('success', 'Bendahara disimpan dan role pengguna sudah disesuaikan.');
    }

    // ====== AKTIVASI / TUTUP TAHUN ======
     public function activate(ClassYear $year)
{
    abort_if($year->status !== 'draft', 403);

    $kasOk        = ($year->setting?->kas_nominal ?? 0) > 0;
    $hasStudents  = $year->enrollments()->active()->exists();
    $treasurerCnt = $year->enrollments()->where('is_treasurer', true)->count();

    if (!$kasOk || !$hasStudents || $treasurerCnt < 1 || $treasurerCnt > 2) {
        return back()
            ->withErrors('Lengkapi: nominal kas, siswa aktif, dan bendahara 1–2 orang.')
            ->withInput();
    }

    DB::transaction(function () use ($year) {
        // Arsipkan tahun aktif lain milik wali ini saja
        ClassYear::active()
            ->where('homeroom_user_id', $year->homeroom_user_id)
            ->update(['status' => 'archived']);

        $year->update(['status' => 'active']);
    });

    return redirect()->route('year.index')->with('success', 'Tahun ajaran diaktifkan.');
}


    public function close(ClassYear $year)
    {
        DB::transaction(function () use ($year) {
            $year->update(['status' => 'archived']);
            $year->periods()->update(['status' => 'closed']);
        });

        return back()->with('success', 'Tahun ajaran ditutup.');
    }

    // ====== BULK siswa (hanya draft) ======
    public function bulkStudents(Request $request, ClassYear $year)
    {
        abort_if($year->status !== 'draft', 403);

        $data = $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer'],
            'action' => ['required', 'in:activate,deactivate,remove'],
        ]);

        $ids = StudentEnrollment::where('class_year_id', $year->id)
            ->whereIn('id', $data['ids'])
            ->pluck('id')
            ->all();

        if (empty($ids)) {
            return back()->withErrors('Tidak ada data yang valid untuk diproses.');
        }

        switch ($data['action']) {
            case 'activate':
                StudentEnrollment::whereIn('id', $ids)->update(['is_active' => true]);
                $msg = 'Berhasil mengaktifkan ' . count($ids) . ' siswa.';
                break;

            case 'deactivate':
                StudentEnrollment::whereIn('id', $ids)
                    ->update(['is_active' => false, 'is_treasurer' => false]);
                $msg = 'Berhasil menonaktifkan ' . count($ids) . ' siswa.';
                break;

            case 'remove':
                StudentEnrollment::whereIn('id', $ids)->delete();
                $msg = 'Berhasil menghapus ' . count($ids) . ' siswa dari tahun ini.';
                break;

            default:
                return back()->withErrors('Aksi tidak dikenali.');
        }

        return back()->with('success', $msg);
    }

    // ====== EDIT satu siswa ======
    public function updateEnrollment(Request $request, ClassYear $year, StudentEnrollment $enrollment)
    {
        abort_if(!in_array($year->status, ['draft', 'active']), 403);
        abort_if($enrollment->class_year_id !== $year->id, 404);

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:100'],
            'email'  => ['required', 'email', 'max:150', 'unique:users,email,' . $enrollment->student_user_id],
            'nis'    => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:L,P'],
        ]);

        DB::transaction(function () use ($data, $enrollment) {
            $user = $enrollment->user;
            $user->update([
                'name'   => $data['name'],
                'email'  => $data['email'],
                'gender' => $data['gender'] ?? null,
            ]);

            $enrollment->update([
                'nis' => $data['nis'] ?? null,
            ]);
        });

        return back()->with('success', 'Data siswa diperbarui.');
    }

    // ====== TOGGLE aktif/nonaktif ======
    public function toggleEnrollment(Request $request, ClassYear $year, StudentEnrollment $enrollment)
    {
        abort_if(!in_array($year->status, ['draft', 'active']), 403);
        abort_if($enrollment->class_year_id !== $year->id, 404);

        $new = !$enrollment->is_active;

        $payload = ['is_active' => $new];
        if ($new === false) {
            $payload['is_treasurer'] = false;
        }

        $enrollment->update($payload);

        return back()->with('success', $new ? 'Siswa diaktifkan.' : 'Siswa dinonaktifkan.');
    }
    public function summary(ClassYear $year)
{
    // Opsional: batasi hanya wali pemilik tahun ini yang boleh lihat
    // abort_if($year->homeroom_user_id !== auth()->id(), 403);

    $yearId = $year->id;

    /*
    |--------------------------------------------------------------
    | 1. Rekap total MASUK & KELUAR 1 tahun ini (header ringkasan)
    |--------------------------------------------------------------
    */
    $totalMasuk = (int) DB::table('cash_payments')
        ->where('class_year_id', $yearId)
        ->sum('amount');

    $totalKeluar = (int) DB::table('cash_expenses')
        ->where('class_year_id', $yearId)
        ->sum('amount');

    $saldoAkhir = $totalMasuk - $totalKeluar;

    /*
    |--------------------------------------------------------------
    | 2. Ambil semua periode (minggu) tahun ini
    |--------------------------------------------------------------
    */
    $periods = DB::table('cash_periods as p')
        ->where('p.class_year_id', $yearId)
        ->orderBy('p.date_start')
        ->get([
            'p.id',
            'p.week_no',
            'p.date_start',
            'p.date_end',
            'p.status',
        ]);

    if ($periods->isEmpty()) {
        $rekapPeriode  = collect();
        $chartLabels   = [];
        $chartMasuk    = [];
        $chartKeluar   = [];
    } else {
        /*
        |----------------------------------------------------------
        | 3. Rekap kas MASUK per periode (pakai period_id)
        |----------------------------------------------------------
        */
        $masukByPeriod = DB::table('cash_payments')
            ->select('period_id', DB::raw('SUM(amount) as total'))
            ->where('class_year_id', $yearId)
            ->groupBy('period_id')
            ->pluck('total', 'period_id');  // [period_id => total]

        /*
        |----------------------------------------------------------
        | 4. Rekap kas KELUAR per periode pakai range tanggal
        |----------------------------------------------------------
        | Karena cash_expenses tidak punya period_id, kita pakai
        | date_start & date_end setiap periode.
        */
        $rekapPeriode = $periods->map(function ($p) use ($masukByPeriod, $yearId) {
            $totalMasuk = (int) ($masukByPeriod[$p->id] ?? 0);

            $totalKeluar = (int) DB::table('cash_expenses')
                ->where('class_year_id', $yearId)
                ->whereBetween('date', [$p->date_start, $p->date_end])
                ->sum('amount');

            return (object) [
                'id'           => $p->id,
                'week_no'      => $p->week_no,
                'date_start'   => $p->date_start,
                'date_end'     => $p->date_end,
                'status'       => $p->status,
                'total_masuk'  => $totalMasuk,
                'total_keluar' => $totalKeluar,
            ];
        });

        /*
        |----------------------------------------------------------
        | 5. Data untuk Chart.js (kalau nanti mau dipakai)
        |----------------------------------------------------------
        */
        $chartLabels = $rekapPeriode
            ->map(fn ($r) => 'Minggu ' . $r->week_no)
            ->toArray();

        $chartMasuk = $rekapPeriode
            ->map(fn ($r) => $r->total_masuk)
            ->toArray();

        $chartKeluar = $rekapPeriode
            ->map(fn ($r) => $r->total_keluar)
            ->toArray();
    }

    /*
    |--------------------------------------------------------------
    | 6. Info siswa & bendahara
    |--------------------------------------------------------------
    */
    $totalSiswa      = $year->enrollments()->count();
    $siswaAktif      = $year->enrollments()->where('is_active', true)->count();
    $siswaNonaktif   = $totalSiswa - $siswaAktif;
    $jumlahBendahara = $year->enrollments()->where('is_treasurer', true)->count();

    // List nama bendahara (untuk ditampilkan di Blade)
    $bendaharaList = $year->enrollments()
        ->where('is_treasurer', true)
        ->with('user:id,name')
        ->get()
        ->pluck('user.name')
        ->filter()
        ->values();

    /*
    |--------------------------------------------------------------
    | 7. Kirim ke view
    |--------------------------------------------------------------
    */
    return view('year.summary', [
        'year'            => $year,

        'totalMasuk'      => $totalMasuk,
        'totalKeluar'     => $totalKeluar,
        'saldoAkhir'      => $saldoAkhir,
        'saldoKas'        => $saldoAkhir, // supaya boleh pakai nama sama seperti dashboard

        'rekapPeriode'    => $rekapPeriode,
        'chartLabels'     => $chartLabels,
        'chartMasuk'      => $chartMasuk,
        'chartKeluar'     => $chartKeluar,

        'totalSiswa'      => $totalSiswa,
        'siswaAktif'      => $siswaAktif,
        'siswaNonaktif'   => $siswaNonaktif,
        'jumlahBendahara' => $jumlahBendahara,
        'bendaharaList'   => $bendaharaList,
    ]);
}


    // ====== HAPUS satu siswa ======
    public function destroyEnrollment(Request $request, ClassYear $year, StudentEnrollment $enrollment)
    {
        abort_if(!in_array($year->status, ['draft', 'active']), 403);
        abort_if($enrollment->class_year_id !== $year->id, 404);

        if ($year->status === 'active' && $enrollment->is_treasurer && $enrollment->is_active) {
            return back()->withErrors('Tidak bisa menghapus bendahara yang masih aktif. Ubah bendahara dulu atau nonaktifkan.');
        }

        $enrollment->delete();

        return back()->with('success', 'Siswa dihapus dari tahun ini.');
    }
}
