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
use App\Core\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MedicalRecordVoter extends Voter
{
    private MedicalRecordRepositoryInterface $medicalRecordRepository;

    public function __construct(MedicalRecordRepositoryInterface $medicalRecordRepository)
    {
        $this->medicalRecordRepository = $medicalRecordRepository;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, ['ROLE_MEDICAL_RECORD_VIEW_OWN', 'ROLE_MEDICAL_RECORD_EDIT_OWN']);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $context = is_array($subject) ? $subject : [];

        switch ($attribute) {
            case 'ROLE_MEDICAL_RECORD_VIEW_OWN':
                $recordId = $context['id'] ?? null;
                if (!$recordId) {
                    return false;
                }

                return $this->isOwner($user, (int)$recordId);
            case 'ROLE_MEDICAL_RECORD_EDIT_OWN':
                $recordId = $context['id'] ?? null;
                if (!$recordId) {
                    return false;
                }

                return $this->isOwner($user, (int)$recordId);
        }

        return false;
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
