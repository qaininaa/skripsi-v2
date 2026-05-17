<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Verifies that the submitted password matches the currently authenticated
 * user's password hash. Pair with CurrentUserUsername on the username field
 * so each error attaches to its own input.
 */
class CurrentUserPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();
        if ($user === null) {
            $fail('Sesi pengguna tidak ditemukan, silakan login ulang.');
            return;
        }

        if (! is_string($value) || ! Hash::check($value, $user->getAuthPassword())) {
            $fail('Password salah.');
        }
    }
}
