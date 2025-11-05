<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentManualRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name'   => ['required','string','max:100'],
            'email'  => ['required','email','unique:users,email'],
            'nis'    => ['nullable','string','max:30'],
            'gender' => ['nullable','in:L,P'],
        ];
    }
}
