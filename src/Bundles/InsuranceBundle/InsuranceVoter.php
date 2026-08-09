<?php

namespace App\Bundles\InsuranceBundle;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InsuranceVoter extends Voter
{
    public const MANAGE = 'INSURANCE_MANAGE';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return self::MANAGE === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Administrators and Medical Managers can manage insurance providers
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_MEDICAL_MANAGER')) {
            return true;
        }

        return false;
    }
}
