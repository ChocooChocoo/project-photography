<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DriveLink implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = is_string($value) ? parse_url($value) : false;
        if (! $parts || ($parts['scheme'] ?? '') !== 'https'
            || ! in_array(strtolower($parts['host'] ?? ''), ['drive.google.com', 'docs.google.com'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            $fail('Use an HTTPS Google Drive or Google Docs sharing link.');
        }
    }
}
