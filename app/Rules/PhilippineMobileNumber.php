<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A complete Philippine mobile number in any usual shape: 0917 123 4567,
 * 917 123 4567, +63 917 123 4567 or the form mask "+(63)917 123 4567".
 */
class PhilippineMobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (! preg_match('/^(?:63|0)?9\d{9}$/', $digits)) {
            $fail('Enter a complete mobile number, for example 0917 123 4567.');
        }
    }
}
