<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\DB;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'date'   => ['required', 'date'],
            'note'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $payment   = $this->route('pay'); // CashPayment via route-model binding
            $paymentId = (int) ($payment->id ?? 0);

            $pay = DB::table('cash_payments')->where('id', $paymentId)->first();
            if (!$pay) {
                $v->errors()->add('amount', 'Transaksi tidak ditemukan.');
                return;
            }

            $period = DB::table('cash_periods')->where('id', $pay->period_id)->first();
            if (!$period || $period->status !== 'open') {
                $v->errors()->add('amount', 'Periode sudah ditutup. Transaksi tidak dapat diedit.');
                return;
            }

            $enroll = DB::table('student_enrollments')->where('id', $pay->enrollment_id)->first();
            if (!$enroll) {
                $v->errors()->add('amount', 'Enrollment tidak ditemukan.');
                return;
            }

            $nominal = (int) DB::table('class_settings')
                ->where('class_year_id', $pay->class_year_id)
                ->value('kas_nominal');

            if ($nominal <= 0) {
                $v->errors()->add('amount', 'Nominal mingguan belum diatur.');
                return;
            }

            // Total minggu ini TANPA transaksi yang sedang diedit
            $totalTanpaIni = (int) DB::table('cash_payments')
                ->where('period_id', $pay->period_id)
                ->where('enrollment_id', $pay->enrollment_id)
                ->where('id', '<>', $pay->id)
                ->sum('amount');

            $sisa        = max(0, $nominal - $totalTanpaIni);
            $amountBaru  = (int) $this->input('amount');

            if ($amountBaru > $sisa) {
                $v->errors()->add(
                    'amount',
                    'Jumlah melebihi sisa pembayaran. Maksimal: Rp ' . number_format($sisa, 0, ',', '.')
                );
                return;
            }

            $date = $this->input('date');
            if ($date < $period->date_start || $date > $period->date_end) {
                $v->errors()->add('date', 'Tanggal harus di dalam rentang periode.');
                return;
            }

            // SIMPAN HANYA SCALAR/ID ke attributes (bukan objek!)
            $this->attributes->set('_period_id',     (int) $period->id);
            $this->attributes->set('_class_year_id', (int) $pay->class_year_id);
            $this->attributes->set('_enrollment_id', (int) $pay->enrollment_id);
            $this->attributes->set('_nominal',       (int) $nominal);
            $this->attributes->set('_sisa',          (int) $sisa);
            $this->attributes->set('_pay_id',        (int) $pay->id);
        });
    }
}
