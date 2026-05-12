<?php

namespace App\Http\Requests;

use App\Domain\User\Dtos\CreateUserDto;
use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:super,admin,analyst,supervisor,manager'],
        ];
    }

    public function toDTO(): CreateUserDto
    {
        return new CreateUserDto($this->validated());
    }
}
