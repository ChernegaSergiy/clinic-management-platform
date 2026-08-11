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

namespace App\Domain\Department;

use App\Domain\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DepartmentVoter extends Voter
{
    public const VIEW = 'DEPARTMENT_VIEW';
    public const CREATE = 'DEPARTMENT_CREATE';
    public const EDIT = 'DEPARTMENT_EDIT';
    public const DELETE = 'DEPARTMENT_DELETE';
    public const MANAGE = 'DEPARTMENT_MANAGE';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE, self::MANAGE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Administrators and Medical Managers have full access to manage departments
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MEDICAL_MANAGER')) {
            return true;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($user);
            case self::CREATE:
            case self::EDIT:
            case self::DELETE:
            case self::MANAGE:
                return false; // Only admins and medical managers can modify departments
        }

        return false;
    }

    private function canView(User $user) : bool
    {
        // Almost all staff roles need to view the list of departments
        return $this->security->isGranted('ROLE_DOCTOR')
            || $this->security->isGranted('ROLE_NURSE')
            || $this->security->isGranted('ROLE_REGISTRAR')
            || $this->security->isGranted('ROLE_HR_MANAGER');
    }
}
