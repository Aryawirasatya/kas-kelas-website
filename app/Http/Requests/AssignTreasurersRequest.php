<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTreasurersRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'treasurer_ids'   => ['required','array','min:1','max:2'],
            'treasurer_ids.*' => ['integer','exists:student_enrollments,id'],
        ];
    }
}
