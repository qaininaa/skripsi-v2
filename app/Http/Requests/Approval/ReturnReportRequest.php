<?php

namespace App\Http\Requests\Approval;

use Domain\Report\Dtos\ReturnReportDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

/**
 * Re-confirms the actor's identity, validates that the returned-to user
 * exists, and packages the DTO for the service.
 */
class ReturnReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username'            => ['required', 'string'],
            'password'            => ['required', 'string'],
            'returned_to_user_id' => ['required', 'string', 'exists:users,id'],
            'notes'               => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $user = $this->user();
            if (! $user) {
                $v->errors()->add('auth_error', 'Sesi Anda telah berakhir, silakan masuk kembali.');
                return;
            }
            if ((string) $user->username !== (string) $this->input('username')
                || ! Hash::check((string) $this->input('password'), $user->password)
            ) {
                $v->errors()->add('auth_error', 'Username atau password tidak valid.');
            }
        });
    }

    public function toDTO(): ReturnReportDto
    {
        return new ReturnReportDto([
            'actor_id'            => (string) $this->user()->id,
            'returned_to_user_id' => (string) $this->validated()['returned_to_user_id'],
            'notes'               => $this->validated()['notes'] ?? null,
        ]);
    }
}
