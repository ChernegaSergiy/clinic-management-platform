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

namespace App\Bundles\BillingBundle\Repository;

interface InvoiceRepositoryInterface
{
    public function findAll(string $searchTerm = '') : array;
    public function save(array $data) : int;
    public function findById(int $id) : ?array;
    public function update(int $id, array $data) : bool;
    public function logFinancialTransaction(
        int $patientId,
        float $amount,
        string $transactionType,
        string $description,
        ?int $entityId = null
    ) : bool;
    // public function delete(int $id): bool;
}
