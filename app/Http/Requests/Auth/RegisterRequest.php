<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'regex:/^[A-Z][a-zA-Z]*$/',
                'min:7',
                Rule::unique('users', 'username'),
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\da-zA-Z]).+$/',
                'confirmed',
            ],
            'password_confirmation' => 'required|string',
            'birthday' => [
                'required',
                'date',
                'date_format:Y-m-d', 
                function ($attribute, $value, $fail) {
                    $age = Carbon::parse($value)->age;
                    if ($age < 14) {
                        $fail('You must be at least 14 years old.');
                    }
                },
            ],
        ];
    }
}