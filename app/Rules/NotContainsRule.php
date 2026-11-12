<?php
// app/Rules/NotContainsRule.php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class NotContainsRule implements ValidationRule
{
    public function __construct(protected string $pattern) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $found = $this->findForbiddenMatch((string) $value);

        if ($found !== null) {
            $fail("Kolom :attribute tidak boleh mengandung kata \"{$found}\".");
        }
    }

    /**
     * Mengembalikan grup OR mana yang match (yang seharusnya dihindari),
     * atau null jika tidak ada satupun grup yang match (berarti valid).
     */
    protected function findForbiddenMatch(string $value): ?string
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
                return $group; // grup terlarang ini ditemukan di value -> gagal validasi
            }
        }

        return null;
    }
}
