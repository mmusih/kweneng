<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMark implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && ($value < 0 || $value > 100)) {
            $fail('The :attribute must be between 0 and 100.');
        }
    }
}
