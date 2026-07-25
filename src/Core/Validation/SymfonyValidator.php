<?php

namespace App\Core\Validation;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SymfonyValidator
{
    private ValidatorInterface $validator;
    private ManagerRegistry $registry;
    private array $errors = [];

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
        $this->validator = Validation::createValidator();
    }

    /**
     * Validate data against rules using Symfony Validator
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules in the format ['field' => ['rule1', 'rule2:param']]
     * @return bool True if validation passes, false otherwise
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        $constraints = [];
        $uniqueRules = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldConstraints = [];
            foreach ($fieldRules as $rule) {
                if (str_starts_with($rule, 'unique:')) {
                    $uniqueRules[$field] = $rule;
                } else {
                    $constraint = $this->parseRule($rule, $field);
                    if ($constraint !== null) {
                        $fieldConstraints[] = $constraint;
                    }
                }
            }
            if (!empty($fieldConstraints)) {
                $constraints[$field] = $fieldConstraints;
            }
        }

        // Create a simple data object for validation
        $dataObject = new class ($data) {
            public function __construct(private array $data)
            {
            }

            public function __get(string $name)
            {
                return $this->data[$name] ?? null;
            }
        };

        // Validate non-unique rules with Symfony Validator
        foreach ($constraints as $field => $fieldConstraints) {
            $value = $data[$field] ?? null;
            $violations = $this->validator->validate($value, $fieldConstraints);

            if (count($violations) > 0) {
                $this->errors[$field] = [];
                foreach ($violations as $violation) {
                    $this->errors[$field][] = $violation->getMessage();
                }
            }
        }

        // Validate unique rules manually
        foreach ($uniqueRules as $field => $rule) {
            $this->validateUniqueRule($data[$field] ?? null, $rule, $field);
        }

        return empty($this->errors);
    }

    private function validateUniqueRule(?string $value, string $rule, string $field): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $params = explode(',', substr($rule, 7));
        $table = $params[0];
        $column = $params[1] ?? $field;
        $ignoreId = $params[2] ?? null;

        try {
            $conn = $this->registry->getConnection();
            $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :value";
            $queryParams = ['value' => $value];

            if ($ignoreId !== null) {
                $sql .= " AND `id` != :ignore_id";
                $queryParams['ignore_id'] = $ignoreId;
            }

            $result = $conn->executeQuery($sql, $queryParams);

            if ($result->fetchOne() > 0) {
                $this->errors[$field][] = "Значення поля '{$field}' вже існує.";
            }
        } catch (\Exception $e) {
            // In testing environment, re-throw the exception for proper testing
            throw $e;
        }
    }

    private function parseRule(string $rule, string $field): ?object
    {
        if ($rule === 'required') {
            return new Assert\NotBlank(['message' => "Поле '{$field}' обов'язкове для заповнення."]);
        }

        if ($rule === 'email') {
            return new Assert\Email(['message' => "Поле '{$field}' повинно містити дійсну електронну адресу."]);
        }

        if (str_starts_with($rule, 'min:')) {
            $minLength = (int)substr($rule, 4);
            return new Assert\Length([
                'min' => $minLength,
                'minMessage' => "Поле '{$field}' повинно містити не менше {$minLength} символів."
            ]);
        }

        if (str_starts_with($rule, 'max:')) {
            $maxLength = (int)substr($rule, 4);
            return new Assert\Length([
                'max' => $maxLength,
                'maxMessage' => "Поле '{$field}' повинно містити не більше {$maxLength} символів."
            ]);
        }

        if ($rule === 'date') {
            return new Assert\Date(['message' => "Поле '{$field}' повинно містити дійсну дату."]);
        }

        if ($rule === 'datetime') {
            return new Assert\Callback(function ($value, $context) use ($field) {
                if (empty($value)) {
                    return;
                }
                $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
                $dateTimeShort = \DateTime::createFromFormat('Y-m-d H:i', $value);
                if (!$dateTime && !$dateTimeShort) {
                    $context->buildViolation("Поле '{$field}' повинно містити дійсні дату та час.")
                        ->addViolation();
                }
            });
        }

        if (str_starts_with($rule, 'in:')) {
            $options = explode(',', substr($rule, 3));
            return new Assert\Choice([
                'choices' => $options,
                'message' => "Поле '{$field}' повинно мати одне зі значень: " . implode(', ', $options) . "."
            ]);
        }

        if ($rule === 'numeric') {
            return new Assert\Regex([
                'pattern' => '/^[0-9]+(\.[0-9]+)?$/',
                'message' => "Поле '{$field}' повинно бути числом."
            ]);
        }

        if (str_starts_with($rule, 'min_value:')) {
            $minValue = (float)explode(':', $rule)[1];
            return new Assert\GreaterThanOrEqual([
                'value' => $minValue,
                'message' => "Поле '{$field}' повинно бути не менше {$minValue}."
            ]);
        }

        if (str_starts_with($rule, 'max_value:')) {
            $maxValue = (float)explode(':', $rule)[1];
            return new Assert\LessThanOrEqual([
                'value' => $maxValue,
                'message' => "Поле '{$field}' повинно бути не більше {$maxValue}."
            ]);
        }

        if ($rule === 'array') {
            return new Assert\Type(
                type: 'array',
                message: "Поле '{$field}' повинно бути масивом."
            );
        }

        if (str_starts_with($rule, 'unique:')) {
            // Unique validation is handled separately
            return null;
        }

        return null;
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
