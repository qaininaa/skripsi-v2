<?php

namespace App\Http\Requests;

use Domain\User\Dtos\UpdateUserDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'role' => ['required', 'in:super,admin,analyst,supervisor,manager'],
        ];
    }

    public function toDTO(): UpdateUserDto
    {
        return new UpdateUserDto($this->validated());
    }
}
