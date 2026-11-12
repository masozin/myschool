<?php
// app/Rules/NumberDateOperatorRule.php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class NumberDateOperatorRule implements ValidationRule
{
    public function __construct(
        protected string $operator,
        protected mixed $value = null,
        protected mixed $value2 = null,
        protected ?string $values = null, // untuk not_equals: "1;2;3"
        protected string $type = 'number', // 'number' | 'date'
    ) {}

    public function validate(string $attribute, mixed $incoming, Closure $fail): void
    {
        $a = $this->cast($incoming);

        $ok = match ($this->operator) {
            'equals' => $a == $this->cast($this->value),
            'not_equals' => !$this->matchesAnyOfList($a),
            'gt' => $a > $this->cast($this->value),
            'lt' => $a < $this->cast($this->value),
            'between' => $a >= $this->cast($this->value) && $a <= $this->cast($this->value2),
            'not_between' => $a < $this->cast($this->value) || $a > $this->cast($this->value2),
            default => true,
        };

        if (!$ok) {
            $fail($this->message());
        }
    }

    protected function matchesAnyOfList(mixed $a): bool
    {
        $list = array_filter(array_map('trim', explode(';', (string) $this->values)));

        foreach ($list as $item) {
            if ($a == $this->cast($item)) {
                return true; // ditemukan di daftar terlarang -> not_equals gagal
            }
        }

        return false;
    }

    protected function cast(mixed $val): mixed
    {
        if ($this->type === 'date') {
            return Carbon::parse($val)->timestamp;
        }

        return is_numeric($val) ? (float) $val : $val;
    }

    protected function message(): string
    {
        return match ($this->operator) {
            'equals' => 'Kolom :attribute harus sama dengan nilai yang ditentukan.',
            'not_equals' => 'Kolom :attribute tidak boleh sama dengan nilai yang dilarang.',
            'gt' => 'Kolom :attribute harus lebih dari nilai minimum.',
            'lt' => 'Kolom :attribute harus kurang dari nilai maksimum.',
            'between' => 'Kolom :attribute harus berada di antara rentang yang ditentukan.',
            'not_between' => 'Kolom :attribute tidak boleh berada di antara rentang yang dilarang.',
            default => 'Kolom :attribute tidak valid.',
        };
    }
}
