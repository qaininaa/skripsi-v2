<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Verifies that a given username + password match the currently authenticated
 * user. Used by save actions that must re-confirm operator identity (analyst
 * monitoring/reading, signature, etc.).
 *
 * Apply on the password field; the username comparison is read from the
 * data set via DataAwareRule.
 *
 * Example:
 *     'password' => ['required', new CurrentUserCredentials('username')]
 */
class CurrentUserCredentials implements ValidationRule, DataAwareRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    public function __construct(protected string $usernameField = 'username')
    {
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();
        if ($user === null) {
            $fail('Sesi pengguna tidak ditemukan, silakan login ulang.');
            return;
        }

        $username = $this->data[$this->usernameField] ?? null;
        if (! is_string($username) || $username === '') {
            $fail('Username wajib diisi.');
            return;
        }

        if ($username !== ($user->username ?? null)) {
            $fail('Username tidak cocok dengan akun Anda.');
            return;
        }

        if (! is_string($value) || ! Hash::check($value, $user->getAuthPassword())) {
            $fail('Password salah.');
        }
    }
}
