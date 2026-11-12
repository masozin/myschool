<?php
// app/Services/FormValidationRuleBuilder.php

namespace App\Services;

use App\Models\Formulir;
use App\Models\FormulirField;
use App\Rules\ContainsRule;
use App\Rules\NotContainsRule;
use App\Rules\NumberDateOperatorRule;

class FormValidationRuleBuilder
{
    /**
     * Pagar bawaan server untuk field file (Batch 4.5): ekstensi yang
     * diizinkan secara global. Konfigurasi Admin hanya boleh
     * mempersempit daftar ini, tidak pernah memperluasnya.
     */
    public const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'zip',
    ];

    /** Ukuran maksimum berkas global dalam KILOBYTE (10 MB). */
    public const MAX_FILE_SIZE_KB = 10240;

    /**
     * @return array{rules: array, attributes: array, messages: array}
     */
    public function build(Formulir $form): array
    {
        $rules = [];
        $attributes = [];
        $messages = [];

        foreach ($form->fields as $field) {
            if ($field->isDisplayOnly()) {
                continue;
            }

            $key = $field->isFileType() ? "files.{$field->name}" : "data.{$field->name}";
            $attributes[$key] = $field->label;

            $fieldRules = $this->baseRules($field);
            $fieldRules = array_merge($fieldRules, $this->typeSpecificRules($field));
            $fieldRules = array_merge($fieldRules, $this->configuredValidationRules($field));

            // Field file SELALU mendapat pagar bawaan server, walau
            // Admin tidak mengonfigurasi validasi apa pun.
            if ($field->isFileType()) {
                $fieldRules = array_merge($fieldRules, $this->fileGuardRules($field));
            }

            $rules[$key] = $fieldRules;
        }

        return compact('rules', 'attributes', 'messages');
    }

    protected function baseRules(FormulirField $field): array
    {
        if ($field->is_required) {
            return $field->isFileType() ? ['required', 'file'] : ['required'];
        }

        return $field->isFileType() ? ['nullable', 'file'] : ['nullable'];
    }

    protected function typeSpecificRules(FormulirField $field): array
    {
        return match ($field->type) {
            'email' => ['email'],
            'number' => ['numeric'],
            'date' => ['date'],
            'select', 'radio' => ['in:' . $this->optionValues($field)],
            'checkbox' => ['array'],
            default => [],
        };
    }

    protected function optionValues(FormulirField $field): string
    {
        return collect($field->getOptions())->pluck('value')->implode(',');
    }

    protected function configuredValidationRules(FormulirField $field): array
    {
        $validation = $field->getValidationConfig();

        if (empty($validation)) {
            return [];
        }

        return match ($field->type) {
            'text', 'textarea' => $this->textRules($validation),
            'number' => $this->operatorRule($validation, 'number'),
            'date' => $this->operatorRule($validation, 'date'),
            // Field file ditangani fileGuardRules() — selalu diterapkan.
            default => [],
        };
    }

    protected function textRules(array $validation): array
    {
        $rules = [];

        foreach ($validation['rules'] ?? [] as $rule) {
            match ($rule['type'] ?? null) {
                'length' => $rules = array_merge($rules, $this->lengthRules($rule)),
                'contains' => $rules[] = new ContainsRule($rule['value'] ?? ''),
                'not_contains' => $rules[] = new NotContainsRule($rule['value'] ?? ''),
                default => null,
            };
        }

        return $rules;
    }

    protected function lengthRules(array $rule): array
    {
        $rules = [];
        if (!empty($rule['min'])) $rules[] = 'min:' . $rule['min'];
        if (!empty($rule['max'])) $rules[] = 'max:' . $rule['max'];
        return $rules;
    }

    protected function operatorRule(array $validation, string $type): array
    {
        if (empty($validation['operator'])) {
            return [];
        }

        return [new NumberDateOperatorRule(
            operator: $validation['operator'],
            value: $validation['value'] ?? null,
            value2: $validation['value2'] ?? null,
            values: $validation['values'] ?? null,
            type: $type,
        )];
    }

    /**
     * Pagar bawaan berkas: batas ukuran + daftar putih ekstensi global.
     * Konfigurasi Admin (config.validation) hanya boleh mempersempit:
     * ukuran efektif = min(konfigurasi, global), ekstensi efektif =
     * irisan konfigurasi dengan daftar putih global. Konfigurasi yang
     * seluruhnya di luar daftar putih diabaikan (kembali ke bawaan)
     * agar field tidak terkunci total oleh kesalahan konfigurasi.
     */
    protected function fileGuardRules(FormulirField $field): array
    {
        $validation = $field->getValidationConfig();

        $maxKb = self::MAX_FILE_SIZE_KB;
        if (! empty($validation['max_size'])) {
            // max_size konfigurasi dalam MEGABYTE; Laravel 'max' file dalam KILOBYTE
            $maxKb = min($maxKb, (int) ($validation['max_size'] * 1024));
        }

        $extensions = self::ALLOWED_EXTENSIONS;
        if (! empty($validation['extensions'])) {
            $configured = array_map(
                fn ($e) => strtolower(trim((string) $e)),
                (array) $validation['extensions']
            );
            $narrowed = array_values(array_intersect($configured, self::ALLOWED_EXTENSIONS));

            if ($narrowed) {
                $extensions = $narrowed;
            }
        }

        return [
            'max:' . $maxKb,
            'mimes:' . implode(',', $extensions),
        ];
    }
}
