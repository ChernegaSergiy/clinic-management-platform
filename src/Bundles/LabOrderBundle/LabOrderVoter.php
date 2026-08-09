<?php

namespace App\Bundles\LabOrderBundle;

use App\Bundles\LabOrderBundle\Repository\LabOrderRepositoryInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LabOrderVoter extends Voter
{
    public const VIEW = 'LAB_ORDER_VIEW';
    public const EDIT = 'LAB_ORDER_EDIT';

    private LabOrderRepositoryInterface $labOrderRepository;
    private Security $security;

    public function __construct(
        LabOrderRepositoryInterface $labOrderRepository,
        Security $security
    ) {
        $this->labOrderRepository = $labOrderRepository;
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true);
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

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($user, $labOrderId);
            case self::EDIT:
                return $this->canEdit($user, $labOrderId);
        }

        return false;
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
