<?php

namespace App\Http\Requests;

use App\Services\CashService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\DB;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'amount'        => ['required', 'integer', 'min:1'],
            'date'          => ['required', 'date'],
            'note'          => ['nullable', 'string', 'max:255'],
            'alloc_arrears' => ['nullable', 'boolean'],
        ];
    }

     public function withValidator(Validator $validator): void
{
    $validator->after(function (Validator $v) {
        $enrollId     = (int) $this->input('enrollment_id');
        $amount       = (int) $this->input('amount');
        $date         = $this->input('date');
        $periodId     = (int) $this->input('period_id');
        $allocArrears = (bool) $this->boolean('alloc_arrears');

        // Ambil periode (INI HARUSNYA PERIODE OPEN / MINGGU BERJALAN)
        $period = DB::table('cash_periods')->where('id', $periodId)->first();
        if (!$period) {
            $v->errors()->add('period_id', 'Periode tidak ditemukan.');
            return;
        }

        // Semua pembayaran dicatat di periode OPEN
        if ($period->status !== 'open') {
            $v->errors()->add('period_id', 'Periode sudah ditutup.');
            return;
        }

        // Cek enrollment valid di tahun yang sama
        $enroll = DB::table('student_enrollments')->where('id', $enrollId)->first();
        if (!$enroll || (int) $enroll->class_year_id !== (int) $period->class_year_id) {
            $v->errors()->add('enrollment_id', 'Siswa tidak termasuk pada tahun ajaran periode tersebut.');
            return;
        }

        // Ambil nominal kas mingguan
        $nominal = (int) DB::table('class_settings')
            ->where('class_year_id', $period->class_year_id)
            ->value('kas_nominal');

        if ($nominal <= 0) {
            $v->errors()->add('amount', 'Nominal mingguan belum diatur.');
            return;
        }

        /** @var \App\Services\CashService $svc */
        $svc = app(\App\Services\CashService::class);

        // Sisa minggu OPEN (minggu sekarang)
        $sisaCurrent = $svc->remainingFor((int) $period->id, $enrollId, $nominal);

        // Batas maksimal default: sisa minggu sekarang
        $maxAllowed = $sisaCurrent;

        // Kalau mode bayar tunggakan -> tambahkan semua sisa tunggakan lama
        if ($allocArrears) {
            $arrears = $svc->arrearsPeriods(
                (int) $period->class_year_id,
                $enrollId,
                $nominal,
                (int) $period->id // exclude periode OPEN
            );

            $totalArrearRemain = (int) $arrears->sum('remaining');
            $maxAllowed += $totalArrearRemain;
        }

        // Sekarang validasi amount terhadap TOTAL kewajiban (tunggakan + minggu ini)
        if ($amount > $maxAllowed) {
            $v->errors()->add(
                'amount',
                'Jumlah melebihi total sisa kewajiban. Maksimal: Rp ' . number_format($maxAllowed, 0, ',', '.')
            );
            return;
        }

        // Tanggal harus di dalam rentang periode OPEN (minggu berjalan)
        if ($date < $period->date_start || $date > $period->date_end) {
            $v->errors()->add('date', 'Tanggal harus di dalam rentang periode yang sedang berjalan.');
            return;
        }

        // Simpan ID scalar ke attributes (dipakai di controller)
        $this->attributes->set('_period_id',     (int) $period->id);
        $this->attributes->set('_class_year_id', (int) $period->class_year_id);
        $this->attributes->set('_enrollment_id', (int) $enrollId);
        $this->attributes->set('_nominal',       (int) $nominal);
        $this->attributes->set('_sisa',          (int) $sisaCurrent);
    });
}


    protected function prepareForValidation(): void
    {
        $this->merge([
            'alloc_arrears' => filter_var($this->input('alloc_arrears', false), FILTER_VALIDATE_BOOL),
        ]);
    }
}
