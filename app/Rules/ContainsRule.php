<?php
// app/Rules/ContainsRule.php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ContainsRule implements ValidationRule
{
    /**
     * @param string $pattern Format: "kata1;kata2|kata3" -> (kata1 AND kata2) OR kata3
     */
    public function __construct(protected string $pattern) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->matches((string) $value)) {
            $fail("Kolom :attribute harus mengandung salah satu kombinasi kata yang disyaratkan.");
        }
    }

    protected function matches(string $value): bool
    {
        $value = Str::lower($value);
        $orGroups = explode('|', $this->pattern);

        foreach ($orGroups as $group) {
            $andTerms = array_filter(array_map('trim', explode(';', $group)));

            if (empty($andTerms)) {
                continue;
            }

            $allMatch = true;
            foreach ($andTerms as $term) {
                if (!Str::contains($value, Str::lower($term))) {
                    $allMatch = false;
                    break;
                }
            }

            if ($allMatch) {
                return true; // salah satu grup OR terpenuhi penuh (semua AND-nya cocok)
            }
        }

        return false;
    }
}
