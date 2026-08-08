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

namespace App\Bundles\MedicalRecordBundle;

use App\Bundles\MedicalRecordBundle\Repository\MedicalRecordRepositoryInterface;
use App\Core\Auth\Policy;
use App\Core\Model\User;

class MedicalRecordPolicy implements Policy
{
    private MedicalRecordRepositoryInterface $medicalRecordRepository;

    public function __construct(MedicalRecordRepositoryInterface $medicalRecordRepository)
    {
        $this->medicalRecordRepository = $medicalRecordRepository;
    }

    public function view(User $user, array $context) : bool
    {
        if ($user->hasPermission('medical_record.view.any')) {
            return true;
        }

        if ($user->hasPermission('medical_record.view.own')) {
            $recordId = $context['id'] ?? null;
            if (!$recordId) {
                return false;
            }

            return $this->isOwner($user, (int)$recordId);
        }

        return false;
    }

    public function edit(User $user, array $context) : bool
    {
        if ($user->hasPermission('medical_record.edit.any')) {
            return true;
        }

        if ($user->hasPermission('medical_record.edit.own')) {
            $recordId = $context['id'] ?? null;
            if (!$recordId) {
                return false;
            }

            return $this->isOwner($user, (int)$recordId);
        }
        return false;
    }

    public function create(User $user, array $context) : bool
    {
        return $user->hasPermission('medical_record.create');
    }

    private function isOwner(User $user, int $recordId) : bool
    {
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }
        $medicalRecord = $this->medicalRecordRepository->findById($recordId);
        return $medicalRecord && (int)$medicalRecord['doctor_id'] === $userId;
    }
}
