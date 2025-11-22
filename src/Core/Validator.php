<?php

namespace App\Core;

use PDO;

class Validator
{
    private array $errors = [];
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function validate(array $data, array $rules): bool
    {
        foreach ($rules as $field => $ruleSet) {
            foreach ($ruleSet as $rule) {
                $value = $data[$field] ?? null;

                if (is_string($rule) && str_starts_with($rule, 'required')) {
                    if (empty($value)) {
                        $this->errors[$field][] = "Поле '{$field}' є обов'язковим.";
                    }
                }

                if (is_string($rule) && str_starts_with($rule, 'email')) {
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[$field][] = "Поле '{$field}' повинно бути дійсною електронною адресою.";
                    }
                }

                if (is_string($rule) && str_starts_with($rule, 'min:')) {
                    $min = (int)explode(':', $rule)[1];
                    if (!empty($value) && strlen($value) < $min) {
                        $this->errors[$field][] = "Поле '{$field}' повинно містити щонайменше {$min} символів.";
                    }
                }

                if (is_string($rule) && str_starts_with($rule, 'date')) {
                    if (!empty($value) && !\DateTime::createFromFormat('Y-m-d', $value)) {
                        $this->errors[$field][] = "Поле '{$field}' повинно бути у форматі YYYY-MM-DD.";
                    }
                }

                if (is_string($rule) && str_starts_with($rule, 'unique:')) {
                    [$ruleName, $table, $column, $ignoreId] = array_pad(explode(':', $rule), 4, null);
                    if (!empty($value)) {
                        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";
                        $params = [':value' => $value];
                        if ($ignoreId !== null) {
                            $sql .= " AND id != :ignore_id";
                            $params[':ignore_id'] = $ignoreId;
                        }
                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute($params);
                        if ($stmt->fetchColumn() > 0) {
                            $this->errors[$field][] = "Значення поля '{$field}' вже існує.";
                        }
                    }
                }
                // Додайте інші правила валідації тут
            }
        }

        return empty($this->errors);
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
