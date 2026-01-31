<?php

namespace App\Core\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Unique extends Constraint
{
    public string $message = 'Значення поля "{{ field }}" вже існує.';
    public string $table;
    public string $column;
    public ?int $ignoreId = null;

    public function __construct(
        string $table,
        string $column,
        ?int $ignoreId = null,
        ?string $message = null,
        ?array $groups = null,
        $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->table = $table;
        $this->column = $column;
        $this->ignoreId = $ignoreId;

        if ($message !== null) {
            $this->message = $message;
        }
    }

    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}