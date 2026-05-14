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
            'password' => ['nullable', 'string', 'confirmed', 'required_with:password_confirmation'],
            'password_confirmation' => ['nullable', 'string', 'required_with:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'username.required' => 'Username wajib diisi.',
            'username.string' => 'Username harus berupa teks.',
            'username.max' => 'Username maksimal 255 karakter.',
            'username.unique' => 'Username sudah digunakan oleh pengguna lain.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'password.string' => 'Password harus berupa teks.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
            'password.required_with' => 'Password wajib diisi jika konfirmasi password diisi.',
            'password_confirmation.required_with' => 'Konfirmasi password wajib diisi jika password diisi.',
        ];
    }

    public function toDTO(): UpdateUserDto
    {
        return new UpdateUserDto($this->validated());
    }
}
