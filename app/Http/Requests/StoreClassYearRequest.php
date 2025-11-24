<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya role guru yang boleh bikin tahun ajaran
        return $this->user()?->hasRole('guru') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            // Nama sekolah opsional, supaya app bisa dipakai di sekolah mana saja
            'school_name' => ['nullable', 'string', 'max:150'],

            // Nama kelas: fleksibel, misal "4B", "8C", "RPL 2"
            'class_label' => ['required', 'string', 'max:50'],

            // Level / jenjang: fleksibel, misal "4 SD", "8 SMP", "X SMK", "XII SMA"
            'level' => ['required', 'string', 'max:50'],

            // Tahun ajaran, format YYYY/YYYY+1 dan unik per wali kelas
            'academic_year' => [
                'required',
                'regex:/^\d{4}\/\d{4}$/',
                Rule::unique('class_years', 'academic_year')
                    ->where(fn ($q) => $q->where('homeroom_user_id', $userId)),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            // Ambil nilai yang paling aman
            $validated = $this->safe()->all();
            $ay = (string)($validated['academic_year'] ?? $this->get('academic_year') ?? '');

            if ($ay === '') {
                return;
            }

            if (!str_contains($ay, '/')) {
                $v->errors()->add('academic_year', 'Format tahun tidak valid.');
                return;
            }

            [$y1, $y2] = explode('/', $ay) + [null, null];

            // Pastikan numeric & berurutan (2024/2025, 2025/2026, dst)
            if (!ctype_digit((string) $y1) || !ctype_digit((string) $y2)) {
                $v->errors()->add('academic_year', 'Tahun harus berupa angka (YYYY/YYYY+1).');
                return;
            }

            if ((int) $y2 !== (int) $y1 + 1) {
                $v->errors()->add('academic_year', 'Tahun harus berurutan (YYYY/YYYY+1).');
            }
 
        });
    }

    public function messages(): array
    {
        return [
            'school_name.max'      => 'Nama sekolah maksimal 150 karakter.',
            'class_label.required' => 'Nama kelas wajib diisi.',
            'level.required'       => 'Tingkat/level wajib diisi.',
            'academic_year.required' => 'Tahun ajaran wajib diisi.',
            'academic_year.regex'  => 'Gunakan format YYYY/YYYY+1, misalnya 2025/2026.',
            'academic_year.unique' => 'Tahun ajaran ini sudah pernah kamu buat.',
        ];
    }
}
