<?php

namespace App\Http\Requests;

use Domain\PasswordPolicy\Dtos\UpdatePasswordPolicyDto;
use Illuminate\Foundation\Http\FormRequest;

class PasswordPolicyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_expiration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'password_history_count' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function toDTO(): UpdatePasswordPolicyDto
    {
        return new UpdatePasswordPolicyDto($this->validated());
    }
}
