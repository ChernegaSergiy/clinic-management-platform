<?php

namespace App\Bundles\DepartmentBundle;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DepartmentVoter extends Voter
{
    public const VIEW = 'DEPARTMENT_VIEW';
    public const CREATE = 'DEPARTMENT_CREATE';
    public const EDIT = 'DEPARTMENT_EDIT';
    public const DELETE = 'DEPARTMENT_DELETE';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE], true);
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
