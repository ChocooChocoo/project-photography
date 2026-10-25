<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A complete Philippine mobile number in any usual shape: 0917 123 4567,
 * 917 123 4567, +63 917 123 4567 or the form mask "+(63)917 123 4567".
 *
 * Pass the number already on file when editing: some stored numbers are a digit
 * short (seed data), and saving a profile must not fail over a number the user
 * did not touch.
 */
class PhilippineMobileNumber implements ValidationRule
{
    public function __construct(private ?string $current = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if ($this->current !== null && $digits !== '' && $digits === preg_replace('/\D/', '', $this->current)) {
            return;
        }

        if (! preg_match('/^(?:63|0)?9\d{9}$/', $digits)) {
            $fail('Enter a complete mobile number, for example 0917 123 4567.');
        }
    }
}
