<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('guru') ?? false;
    }

    public function rules(): array
    {
        return [
            'class_label'   => ['required','string','max:50'],
            'level'         => ['required','in:X,XI,XII'],
            'academic_year' => ['required','regex:/^\d{4}\/\d{4}$/','unique:class_years,academic_year'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            // Ambil nilai aman: validated > get
            $validated = $this->safe()->all(); // sama dengan validated() tapi tanpa throw
            $ay = (string)($validated['academic_year'] ?? $this->get('academic_year') ?? '');

            if ($ay === '') {
                return;
            }

            if (!str_contains($ay, '/')) {
                $v->errors()->add('academic_year', 'Format tahun tidak valid.');
                return;
            }

            [$y1, $y2] = explode('/', $ay);
            if ((int)$y2 !== (int)$y1 + 1) {
                $v->errors()->add('academic_year', 'Tahun harus berurutan (YYYY/YYYY+1).');
            }

            $last = \App\Models\ClassYear::orderByDesc('id')->first();
            if ($last) {
                [$ly1, $ly2] = explode('/', $last->academic_year);
                $should = $ly2 . '/' . ($ly2 + 1);
                if ($ay !== $should) {
                    $v->errors()->add('academic_year', "Tahun baru harus $should (urut dari tahun terakhir).");
                }
            }

            if (\App\Models\ClassYear::where('status','active')->exists()) {
                $v->errors()->add('academic_year', 'Masih ada tahun active. Tutup dulu.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'academic_year.regex' => 'Gunakan format YYYY/YYYY+1, mis. 2025/2026.',
        ];
    }
}
