<?php

namespace App\Http\Requests;

use Domain\User\Dtos\GetUserDto;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required'],
            'password' => ['required'],
        ];
    }

    public function toDTO(): GetUserDto
    {
        return new GetUserDto($this->validated());
    }
}
