<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Shared\Validation;

use App\Infrastructure\Validation\SymfonyValidator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Legacy Validator class - now uses SymfonyValidator internally for backward compatibility
 *
 * @deprecated Use SymfonyValidator directly for new code
 */
class Validator
{
    private SymfonyValidator $symfonyValidator;

    public function __construct(ManagerRegistry $registry)
    {
        // Pass Registry to SymfonyValidator for database-dependent validation (e.g., unique rule)
        $this->symfonyValidator = new SymfonyValidator($registry);
    }

    public function validate(array $data, array $rules) : bool
    {
        return $this->symfonyValidator->validate($data, $rules);
    }

    public function addError(string $field, string $message) : void
    {
        $this->symfonyValidator->addError($field, $message);
    }

    public function hasErrors() : bool
    {
        return $this->symfonyValidator->hasErrors();
    }

    public function getErrors() : array
    {
        return $this->symfonyValidator->getErrors();
    }
}
