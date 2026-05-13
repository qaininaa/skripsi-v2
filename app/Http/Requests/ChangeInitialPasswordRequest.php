<?php

namespace App\Http\Requests;

use Domain\User\Dtos\ChangeInitialPasswordDto;
use Illuminate\Foundation\Http\FormRequest;

class ChangeInitialPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.regex' => 'Password baru harus mengandung angka dan simbol.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ];
    }

    public function toDTO(): ChangeInitialPasswordDto
    {
        return new ChangeInitialPasswordDto($this->validated());
    }
}
