<?php

namespace App\Bundles\InsuranceBundle;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InsuranceVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject) : bool
    {
        return false;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        return false;
    }
}
