<?php

namespace App\Bundles\LabOrderBundle;

use App\Bundles\LabOrderBundle\Repository\LabOrderRepositoryInterface;
use App\Core\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LabOrderVoter extends Voter
{
    private LabOrderRepositoryInterface $labOrderRepository;

    public function __construct(LabOrderRepositoryInterface $labOrderRepository)
    {
        $this->labOrderRepository = $labOrderRepository;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, ['ROLE_LAB_ORDER_VIEW', 'ROLE_LAB_ORDER_EDIT'], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $context = is_array($subject) ? $subject : [];

        switch ($attribute) {
            case 'ROLE_LAB_ORDER_VIEW_OWN':

                $labOrderId = $context['id'] ?? null;
                if (!$labOrderId) {
                    return false;
                }

                return $this->isOwner($user, (int)$labOrderId);
            case 'ROLE_LAB_ORDER_EDIT_OWN':

                $labOrderId = $context['id'] ?? null;
                if (!$labOrderId) {
                    return false;
                }

                return $this->isOwner($user, (int)$labOrderId);
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
}
