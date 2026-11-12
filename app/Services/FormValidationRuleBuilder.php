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
            'file' => $this->fileRules($validation),
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

    protected function fileRules(array $validation): array
    {
        $rules = [];

        if (!empty($validation['max_size'])) {
            // Laravel 'max' untuk file dalam KILOBYTE
            $rules[] = 'max:' . ($validation['max_size'] * 1024);
        }

        if (!empty($validation['extensions'])) {
            $rules[] = 'mimes:' . implode(',', $validation['extensions']);
        }

        return $rules;
    }
}
