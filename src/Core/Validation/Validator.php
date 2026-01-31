<?php

namespace App\Core\Validation;

use PDO;

/**
 * Legacy Validator class - now uses SymfonyValidator internally for backward compatibility
 * @deprecated Use SymfonyValidator directly for new code
 */
class Validator
{
    private SymfonyValidator $symfonyValidator;

    public function __construct(PDO $pdo)
    {
        // Pass PDO to SymfonyValidator for database-dependent validation (e.g., unique rule)
        $this->symfonyValidator = new SymfonyValidator($pdo);
    }

    public function validate(array $data, array $rules): bool
    {
        return $this->symfonyValidator->validate($data, $rules);
    }

    public function addError(string $field, string $message): void
    {
        $this->symfonyValidator->addError($field, $message);
    }

    public function hasErrors(): bool
    {
        return $this->symfonyValidator->hasErrors();
    }

    public function getErrors(): array
    {
        return $this->symfonyValidator->getErrors();
    }
}
