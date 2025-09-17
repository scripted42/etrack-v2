<?php

namespace App\Rules;

use App\Services\PasswordPolicyService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validation = PasswordPolicyService::validatePassword($value);
        
        if (!$validation['is_valid']) {
            $fail('Password tidak memenuhi standar keamanan: ' . implode(', ', $validation['errors']));
        }
    }
}
