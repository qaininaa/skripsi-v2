<?php

namespace App\Http\Requests\Approval;

use Domain\Report\Dtos\ApproveReportDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

/**
 * Re-confirms the actor's identity (username + password) before applying the
 * approval action.
 */
class ApproveReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Cross-field check: matches the entered credentials against the
     * authenticated user.
     */
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

    public function toDTO(): ApproveReportDto
    {
        return new ApproveReportDto([
            'actor_id' => (string) $this->user()->id,
        ]);
    }
}
