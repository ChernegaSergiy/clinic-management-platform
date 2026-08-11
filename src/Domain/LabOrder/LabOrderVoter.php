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

namespace App\Domain\LabOrder;

use App\Domain\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LabOrderVoter extends Voter
{
    public const VIEW = 'LAB_ORDER_VIEW';
    public const CREATE = 'LAB_ORDER_CREATE';
    public const EDIT = 'LAB_ORDER_EDIT';
    public const EDIT_ALL = 'LAB_ORDER_EDIT_ALL';

    private LabOrderRepository $labOrderRepository;
    private Security $security;

    public function __construct(
        LabOrderRepository $labOrderRepository,
        Security $security
    ) {
        $this->labOrderRepository = $labOrderRepository;
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::EDIT_ALL], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Administrators and Medical Managers have full access
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MEDICAL_MANAGER')) {
            return true;
        }

        $labOrderId = $this->extractLabOrderId($subject);

        return match ($attribute) {
            self::VIEW => $this->canView($user, $labOrderId),
            self::CREATE => $this->canCreate(),
            self::EDIT => $this->canEdit($user, $labOrderId),
            self::EDIT_ALL => $this->canEditAll(),
            default => false,
        };
    }

    private function canView(User $user, ?int $labOrderId) : bool
    {
        // Registrars can view all lab orders
        if ($this->security->isGranted('ROLE_REGISTRAR')) {
            return true;
        }

        // Doctors and nurses can view their own orders
        if ($labOrderId && ($this->security->isGranted('ROLE_DOCTOR') || $this->security->isGranted('ROLE_NURSE'))) {
            return $this->isOwner($user, $labOrderId);
        }

        return false;
    }

    private function canEdit(User $user, ?int $labOrderId) : bool
    {
        // Only doctors can edit their own lab orders
        if ($labOrderId && $this->security->isGranted('ROLE_DOCTOR')) {
            return $this->isOwner($user, $labOrderId);
        }

        return false;
    }

    private function canCreate() : bool
    {
        return $this->security->isGranted('ROLE_DOCTOR');
    }

    private function canEditAll() : bool
    {
        return $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_MEDICAL_MANAGER')
            || $this->security->isGranted('ROLE_REGISTRAR');
    }

    private function isOwner(User $user, int $labOrderId) : bool
    {
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }

        $labOrder = $this->labOrderRepository->findById($labOrderId);
        return $labOrder && (int)$labOrder['doctor_id'] === $userId;
    }

    private function extractLabOrderId(mixed $subject) : ?int
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
