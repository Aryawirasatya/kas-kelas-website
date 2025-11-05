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
    // ====== INDEX: daftar tahun + buat draft ======
    public function index()
    {
        $years = ClassYear::latest('id')->get();
        $activeYear = ClassYear::with('setting')->active()->latest('id')->first();
        return view('year.index', compact('years','activeYear'));
    }

    // ====== STEP 1: buat draft ======
    public function storeDraft(StoreClassYearRequest $request)
    {
        $u = $request->user();
        $data = $request->validated();

        $year = DB::transaction(function () use ($data, $u) {
            $year = ClassYear::create([
                'class_label'      => $data['class_label'],
                'level'            => $data['level'],
                'academic_year'    => $data['academic_year'],
                'homeroom_name'    => $u->name,
                'homeroom_user_id' => $u->id,
                'status'           => 'draft',
            ]);

            $year->setting()->create([
                'kas_nominal' => 0,
                'periode'     => 'mingguan',
                'pay_day_hint'=> null,
            ]);

            return $year;
        });

        return redirect()->route('year.setting', $year)
            ->with('success', 'Draft dibuat. Lanjut atur nominal kas.');
    }

    // ====== STEP 2: edit nominal ======
    public function editSetting(ClassYear $year)
    {
        abort_if(!in_array($year->status, ['draft','active']), 403);
        return view('year.setting', compact('year'));
    }

    public function updateSetting(UpdateClassSettingRequest $request, ClassYear $year)
    {
        abort_if(!in_array($year->status, ['draft','active']), 403);
        $data = $request->validated();

        $year->setting()->update([
            'kas_nominal'  => $data['kas_nominal'],
            'pay_day_hint' => $data['pay_day_hint'] ?? null,
        ]);

        $next = ($year->status === 'draft') ? route('year.students', $year) : route('year.setting', $year);
        return redirect($next)->with('success', 'Nominal disimpan.');
    }

    // ====== STEP 3: halaman siswa + bendahara ======
    // Sekarang HALAL utk draft & active (archived: 403). Import & Bulk hanya di draft (dikunci di Blade & controller).
     public function students(ClassYear $year)
{
    abort_if(!in_array($year->status, ['draft','active']), 403);

    $search = request('q');

    // tabel utama + filter server-side
    $enrolls = StudentEnrollment::with('user')
        ->where('class_year_id', $year->id)
        ->when($search, function ($q) use ($search) {
            $q->where(function ($qq) use ($search) {
                $qq->whereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('nisn', 'like', "%{$search}%")
                      ->orWhere('gender', 'like', "%{$search}%");
                })
                ->orWhere('nis', 'like', "%{$search}%"); // nis milik enrollments
            });
        })
        ->when(request('only') === 'active', fn($q) => $q->where('is_active', true))
        ->when(request('only') === 'inactive', fn($q) => $q->where('is_active', false))
        ->when(request('only') === 'treasurer', fn($q) => $q->where('is_treasurer', true))
        ->orderBy('id')
        ->paginate(10);

    // data lengkap untuk modal (tanpa paginate)
    $pickerEnrolls = StudentEnrollment::with('user')
        ->where('class_year_id', $year->id)
        ->orderBy('id')
        ->get();

    return view('year.students', compact('year','enrolls','pickerEnrolls'));
}


    // Tambah siswa manual (boleh saat draft & active)
     public function storeStudentManual(Request $request, ClassYear $year)
{
    abort_if(!in_array($year->status, ['draft','active']), 403);

    $data = $request->validate([
        'name'     => ['required','string','max:100'],
        'email'    => ['required','email','max:150','unique:users,email'],
        'nisn'     => ['nullable','string','max:30','unique:users,nisn'],
        'nis'      => ['nullable','string','max:30'],
        'gender'   => ['nullable','in:L,P'],
        'password' => ['required','string','min:6'],
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

        $user->assignRole('siswa');

        StudentEnrollment::create([
            'class_year_id'   => $year->id,
            'student_user_id' => $user->id,
            'nis'             => $data['nis'] ?? null,
            'is_active'       => true,
            'is_treasurer'    => false,
        ]);
    });

    return back()->with('success','Siswa baru berhasil ditambahkan dan tersimpan di tabel users & enrollments.');
}


     public function importStudents(Request $request, ClassYear $year)
{
    abort_if($year->status !== 'draft', 403);

    // Terima xlsx, xls, csv
    $request->validate([
        'csv' => ['required','file','mimes:xlsx,xls,csv','max:20480'], // 20MB
    ],[
        'csv.mimes' => 'File harus .xlsx, .xls, atau .csv',
    ]);

    $file = $request->file('csv');

    $import = new StudentsImport($year);
    Excel::import($import, $file);

    $fails = $import->getFailures();
    if (!empty($fails)) {
        // Ringkas pesan gagal (baris + pesan)
        $summary = collect($fails)->map(function($f){
            $row = method_exists($f, 'row') ? $f->row() : '-';
            $errors = method_exists($f, 'errors') ? implode('; ', $f->errors()) : 'Invalid';
            return "Baris {$row}: {$errors}";
        })->take(10)->implode(' | ');

        return back()->with('success', 'Import selesai dengan beberapa baris gagal divalidasi.')
                     ->withErrors(['import' => 'Contoh kegagalan: '.$summary]);
    }

    return back()->with('success', 'Import selesai.');
}


    // ====== Bendahara ======
    public function assignTreasurers(Request $request, ClassYear $year)
    {
        // Boleh atur saat draft & active
        abort_if(!in_array($year->status, ['draft','active']), 403);

        $data = $request->validate([
            'treasurer_1_id' => ['required','integer','exists:student_enrollments,id'],
            'treasurer_2_id' => ['nullable','integer','different:treasurer_1_id','exists:student_enrollments,id'],
        ],[
            'treasurer_1_id.required' => 'Bendahara 1 wajib dipilih.',
            'treasurer_2_id.different' => 'Bendahara 1 dan Bendahara 2 harus berbeda.',
        ]);

        $enrollIds = array_values(array_filter([
            $data['treasurer_1_id'],
            $data['treasurer_2_id'] ?? null,
        ]));

        $cnt = StudentEnrollment::where('class_year_id',$year->id)
                 ->whereIn('id',$enrollIds)->count();

        if ($cnt !== count($enrollIds)) {
            return back()->withErrors('Pilihan bendahara tidak sesuai dengan tahun ajaran ini.');
        }

        StudentEnrollment::where('class_year_id',$year->id)->update(['is_treasurer' => false]);
        StudentEnrollment::whereIn('id',$enrollIds)->update(['is_treasurer' => true]);

        return back()->with('success','Bendahara disimpan.');
    }

    // ====== Periode Mingguan ======
    public function generatePeriods(ClassYear $year)
    {
        abort_if($year->status !== 'draft', 403);

        if ($year->periods()->exists()) {
            return back()->with('success','Periode sudah ada.');
        }

        $start = now()->startOfMonth();
        for ($w = 1; $w <= 4; $w++) {
            $weekStart = $start->copy()->addWeeks($w-1)->startOfWeek(); // Senin
            $weekEnd   = $start->copy()->addWeeks($w-1)->endOfWeek();   // Minggu

            $yearVal  = (int) $weekStart->year;
            $monthVal = (int) $weekStart->month;

            $year->periods()->create([
                'year'       => $yearVal,
                'month'      => $monthVal,
                'week_no'    => $w,
                'date_start' => $weekStart->toDateString(),
                'date_end'   => $weekEnd->toDateString(),
                'status'     => 'open',
            ]);
        }

        return back()->with('success','Periode dibuat.');
    }

    // ====== Aktivasi / Tutup tahun ======
    public function activate(ClassYear $year)
    {
        abort_if($year->status !== 'draft', 403);

        $kasOk = ($year->setting?->kas_nominal ?? 0) > 0;
        $hasStudents = $year->enrollments()->active()->exists();
        $treasurerCnt = $year->enrollments()->where('is_treasurer', true)->count();
        $hasPeriods = $year->periods()->exists();

        if (!$kasOk || !$hasStudents || $treasurerCnt < 1 || $treasurerCnt > 2) {
            return back()->withErrors('Lengkapi: nominal kas, siswa aktif, dan bendahara 1–2 orang.')->withInput();
        }

        if (!$hasPeriods) {
            $this->generatePeriods($year);
        }

        ClassYear::active()->update(['status' => 'archived']);
        $year->update(['status' => 'active']);

        return redirect()->route('year.index')->with('success','Tahun ajaran diaktifkan.');
    }

    public function close(ClassYear $year)
    {
        DB::transaction(function () use ($year) {
            $year->update(['status' => 'archived']);
            $year->periods()->update(['status' => 'closed']);
        });

        return back()->with('success','Tahun ajaran ditutup.');
    }

    // ====== BULK siswa (hanya saat draft) ======
    public function bulkStudents(Request $request, ClassYear $year)
    {
        abort_if($year->status !== 'draft', 403);

        $data = $request->validate([
            'ids'    => ['required','array','min:1'],
            'ids.*'  => ['integer'],
            'action' => ['required','in:activate,deactivate,remove'],
        ]);

        // Ambil hanya enrollment milik tahun ini
        $ids = StudentEnrollment::where('class_year_id', $year->id)
                ->whereIn('id', $data['ids'])
                ->pluck('id')
                ->all();

        if (empty($ids)) {
            return back()->withErrors('Tidak ada data yang valid untuk diproses.');
        }

        switch ($data['action']) {
            case 'activate':
                StudentEnrollment::whereIn('id',$ids)->update(['is_active' => true]);
                $msg = 'Berhasil mengaktifkan '.count($ids).' siswa.';
                break;

            case 'deactivate':
                StudentEnrollment::whereIn('id',$ids)->update(['is_active' => false, 'is_treasurer' => false]);
                $msg = 'Berhasil menonaktifkan '.count($ids).' siswa.';
                break;

            case 'remove':
                // aman karena masih draft (belum ada transaksi jalan)
                StudentEnrollment::whereIn('id',$ids)->delete();
                $msg = 'Berhasil menghapus '.count($ids).' siswa dari tahun ini.';
                break;

            default:
                return back()->withErrors('Aksi tidak dikenali.');
        }

        return back()->with('success', $msg);
    }

    // ====== EDIT satu siswa (boleh draft & active) ======
    public function updateEnrollment(Request $request, ClassYear $year, StudentEnrollment $enrollment)
    {
        abort_if(!in_array($year->status, ['draft','active']), 403);
        abort_if($enrollment->class_year_id !== $year->id, 404);

        $data = $request->validate([
            'name'   => ['required','string','max:100'],
            'email'  => ['required','email','max:150','unique:users,email,'.$enrollment->student_user_id],
            'nis'    => ['nullable','string','max:30'],
            'gender' => ['nullable','in:L,P'],
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

    // ====== TOGGLE aktif/nonaktif (boleh draft & active) ======
    public function toggleEnrollment(Request $request, ClassYear $year, StudentEnrollment $enrollment)
    {
        abort_if(!in_array($year->status, ['draft','active']), 403);
        abort_if($enrollment->class_year_id !== $year->id, 404);

        $new = !$enrollment->is_active;

        // Jika dinonaktifkan, otomatis cabut status bendahara agar tidak menggantung
        $payload = ['is_active' => $new];
        if ($new === false) {
            $payload['is_treasurer'] = false;
        }

        $enrollment->update($payload);

        return back()->with('success', $new ? 'Siswa diaktifkan.' : 'Siswa dinonaktifkan.');
    }

    // ====== HAPUS satu siswa dari tahun ======
    public function destroyEnrollment(Request $request, ClassYear $year, StudentEnrollment $enrollment)
    {
        abort_if(!in_array($year->status, ['draft','active']), 403);
        abort_if($enrollment->class_year_id !== $year->id, 404);

        // Jika tahun active & siswa ini bendahara aktif → larang hapus agar tidak invalid
        if ($year->status === 'active' && $enrollment->is_treasurer && $enrollment->is_active) {
            return back()->withErrors('Tidak bisa menghapus bendahara yang masih aktif. Ubah bendahara dulu atau nonaktifkan.');
        }

        $enrollment->delete();

        return back()->with('success','Siswa dihapus dari tahun ini.');
    }
}
