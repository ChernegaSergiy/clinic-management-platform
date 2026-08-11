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

namespace App\Domain\MedicalRecord;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MedicalRecordVoter extends Voter
{
    public const VIEW = 'MEDICAL_RECORD_VIEW';
    public const VIEW_ALL = 'MEDICAL_RECORD_VIEW_ALL';
    public const VIEW_OWN = 'MEDICAL_RECORD_VIEW_OWN';
    public const CREATE = 'MEDICAL_RECORD_CREATE';
    public const EDIT = 'MEDICAL_RECORD_EDIT';

    private MedicalRecordRepository $medicalRecordRepository;
    private Security $security;

    public function __construct(
        MedicalRecordRepository $medicalRecordRepository,
        Security $security
    ) {
        $this->medicalRecordRepository = $medicalRecordRepository;
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, [self::VIEW, self::VIEW_ALL, self::VIEW_OWN, self::CREATE, self::EDIT], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Administrators and Medical Managers can view everything
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MEDICAL_MANAGER')) {
            return true;
        }

        $recordId = $this->extractRecordId($subject);

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($user, $recordId);
            case self::VIEW_ALL:
                return $this->canViewAll();
            case self::VIEW_OWN:
                return $this->canViewOwn();
            case self::CREATE:
                return $this->canCreate();
            case self::EDIT:
                return $this->canEdit($user, $recordId);
        }

        return false;
    }

    private function canView(User $user, ?int $recordId) : bool
    {
        if ($recordId && $this->canViewOwn()) {
            return $this->isOwner($user, $recordId);
        }

        return false;
    }

    private function canViewAll() : bool
    {
        return $this->security->isGranted('ROLE_REGISTRAR');
    }

    private function canViewOwn() : bool
    {
        return $this->security->isGranted('ROLE_DOCTOR') || $this->security->isGranted('ROLE_NURSE');
    }

    private function canCreate() : bool
    {
        return $this->security->isGranted('ROLE_DOCTOR');
    }

    private function canEdit(User $user, ?int $recordId) : bool
    {
        if ($recordId && $this->security->isGranted('ROLE_DOCTOR')) {
            return $this->isOwner($user, $recordId);
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

    private function extractRecordId(mixed $subject) : ?int
    {
        if (is_int($subject) || is_string($subject)) {
            return (int) $subject;
        }

        if (is_array($subject) && isset($subject['id'])) {
            return (int) $subject['id'];
        }

        if (is_object($subject) && method_exists($subject, 'getId')) {
            return (int) $subject->getId();
        }

        return null;
    }
}
