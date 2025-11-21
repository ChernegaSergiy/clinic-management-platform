<?php

namespace App\Core;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        foreach ($rules as $field => $ruleSet) {
            foreach ($ruleSet as $rule) {
                $value = $data[$field] ?? null;

                if ($rule === 'required' && empty($value)) {
                    $this->errors[$field] = "Поле '{$field}' є обов'язковим.";
                }

                // Тут можна додати інші правила валідації (email, min, max, etc.)
            }
        }

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
