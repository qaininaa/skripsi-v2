<?php

namespace App\Rules;

use Closure;
use Domain\Report\Support\MicrobialValue;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a microbial count input. Allowed inputs:
 *   - empty / null
 *   - non-negative integer (digits only)
 *   - "<1"
 *   - "TNTC" (case-insensitive)
 *
 * Rejects anything with minus signs, decimals, or arbitrary text.
 */
class MicrobialCount implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! MicrobialValue::isValid($value)) {
            $fail('Format nilai harus bilangan bulat positif (mis. 1, 250), "<1", atau "TNTC". Nilai 0, desimal, dan negatif tidak diperbolehkan.');
        }
    }
}
