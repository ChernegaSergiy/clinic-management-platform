<?php

namespace App\Bundles\PatientBundle;

use App\Bundles\PatientBundle\Repository\PatientRepositoryInterface;
use App\Core\Auth\Policy;
use App\Core\Model\User;
use App\Module\Appointment\Repository\AppointmentRepositoryInterface;

class PatientPolicy implements Policy
{
    private AppointmentRepositoryInterface $appointmentRepository;
    /** @phpstan-ignore property.onlyWritten */
    private PatientRepositoryInterface $patientRepository;

    public function __construct(
        AppointmentRepositoryInterface $appointmentRepository,
        PatientRepositoryInterface $patientRepository
    ) {
        $this->appointmentRepository = $appointmentRepository;
        $this->patientRepository = $patientRepository;
    }

    public function view(User $user, array $context) : bool
    {
        if ($user->hasPermission('patient.view.any')) {
            return true;
        }

        if ($user->hasPermission('patient.view.own')) {
            $patientId = $context['id'] ?? null;
            if (!$patientId) {
                return false;
            }

            return $this->isUserAssignedToPatient($user, (int)$patientId);
        }

        return false;
    }

    public function edit(User $user, array $context) : bool
    {
        if ($user->hasPermission('patient.edit.any')) {
            return true;
        }

        if ($user->hasPermission('patient.edit.own')) {
            $patientId = $context['id'] ?? null;
            if (!$patientId) {
                return false;
            }

            return $this->isUserAssignedToPatient($user, (int)$patientId);
        }
        return false;
    }

    public function create(User $user, array $context) : bool
    {
        return $user->hasPermission('patient.create');
    }

    private function isUserAssignedToPatient(User $user, int $patientId) : bool
    {
        return $this->appointmentRepository->isPatientAssignedToDoctor($patientId, $user->getId());
    }
}
