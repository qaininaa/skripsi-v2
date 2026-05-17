<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * Verifies that the submitted username matches the currently authenticated user.
 * Apply on the `username` field of any operator-confirmation form (analyst
 * monitoring/reading save, etc.) so the error attaches to the correct field.
 */
class CurrentUserUsername implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();
        if ($user === null) {
            $fail('Sesi pengguna tidak ditemukan, silakan login ulang.');
            return;
        }

        if (! is_string($value) || $value !== ($user->username ?? null)) {
            $fail('Username tidak cocok dengan akun Anda.');
        }
    }
}
