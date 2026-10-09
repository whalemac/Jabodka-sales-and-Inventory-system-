<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Letters-only names (A2) — names may contain letters, spaces, hyphens,
 * apostrophes and periods, but no digits or other symbols.
 *
 * Mirrored client side by the `name` validator in resources/js/jabodka.js.
 */
class PersonName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = trim((string) $value);

        if ($value === '') {
            return;
        }

        if (! preg_match("/^[A-Za-z][A-Za-z .,'-]*$/", $value)) {
            $fail('The :attribute may only contain letters — spaces, hyphens, apostrophes and periods are allowed.');
        }
    }
}
