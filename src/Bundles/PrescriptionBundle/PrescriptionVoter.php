<?php

namespace App\Bundles\PrescriptionBundle;

use App\Bundles\AppointmentBundle\Repository\AppointmentRepositoryInterface;
use App\Bundles\PrescriptionBundle\Repository\PrescriptionRepository;
use App\Core\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PrescriptionVoter extends Voter
{
    private PrescriptionRepository $prescriptionRepository;
    private AppointmentRepositoryInterface $appointmentRepository;

    public function __construct(
        PrescriptionRepository $prescriptionRepository,
        AppointmentRepositoryInterface $appointmentRepository
    ) {
        $this->prescriptionRepository = $prescriptionRepository;
        $this->appointmentRepository = $appointmentRepository;
    }

    protected function supports(string $attribute, mixed $subject) : bool
    {
        return in_array($attribute, ['ROLE_PRESCRIPTION_VIEW', 'ROLE_PRESCRIPTION_CREATE', 'ROLE_PRESCRIPTION_EDIT'], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token) : bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $context = is_array($subject) ? $subject : [];

        switch ($attribute) {
            case 'ROLE_PRESCRIPTION_VIEW':
                if ($user->hasPermission('prescription.view.any')) {
                    return true;
                }

                if ($user->hasPermission('prescription.view.own')) {
                    $prescriptionId = $context['id'] ?? null;
                    if (!$prescriptionId) {
                        return false;
                    }

                    return $this->isOwner($user, (int)$prescriptionId);
                }
                return false;

            case 'ROLE_PRESCRIPTION_CREATE':
                if ($user->hasPermission('prescription.create.any')) {
                    return true;
                }

                if ($user->hasPermission('prescription.create.own')) {
                    $submittedDoctorId = $context['doctor_id'] ?? null;
                    if (!$submittedDoctorId) {
                        return true;
                    }
                    return $user->getId() === (int)$submittedDoctorId;
                }
                return false;

            case 'ROLE_PRESCRIPTION_EDIT':
                if ($user->hasPermission('prescription.edit.any')) {
                    return true;
                }

                if ($user->hasPermission('prescription.edit.own')) {
                    $prescriptionId = $context['id'] ?? null;
                    if (!$prescriptionId) {
                        return false;
                    }

                    return $this->isOwner($user, (int)$prescriptionId);
                }
                return false;
        }

        return false;
    }

    private function isOwner(User $user, int $prescriptionId) : bool
    {
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }

        $prescription = $this->prescriptionRepository->findById($prescriptionId);
        if ($prescription && (int)$prescription['doctor_id'] === $userId) {
            return true;
        }
        if (isset($prescription['patient_id'])) {
            return $this->appointmentRepository->isPatientAssignedToDoctor((int)$prescription['patient_id'], $userId);
        }

        return false;
    }
}
