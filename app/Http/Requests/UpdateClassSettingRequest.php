<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassSettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'kas_nominal' => ['required','integer','min:1000','max:1000000'],
            'pay_day_hint'=> ['nullable','in:Sen,Sel,Rab,Kam,Jum'],
        ];
    }
}
