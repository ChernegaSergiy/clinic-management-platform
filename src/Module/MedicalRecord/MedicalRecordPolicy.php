<?php

namespace App\Module\MedicalRecord;

use App\Core\Auth\Policy;
use App\Core\Model\User;
use App\Module\MedicalRecord\Repository\MedicalRecordRepository;

class MedicalRecordPolicy implements Policy
{
    private MedicalRecordRepository $medicalRecordRepository;

    public function __construct(?MedicalRecordRepository $medicalRecordRepository = null)
    {
        $this->medicalRecordRepository = $medicalRecordRepository ?? new MedicalRecordRepository();
    }

    public function view(User $user, array $context): bool
    {
        if ($user->hasPermission('medical_record.view.any')) {
            return true;
        }

        if ($user->hasPermission('medical_record.view.own')) {
            $recordId = $context['id'] ?? null;
            if (!$recordId) {
                return false;
            }

            return $this->isOwner($user, (int)$recordId);
        }

        return false;
    }

    public function edit(User $user, array $context): bool
    {
        if ($user->hasPermission('medical_record.edit.any')) {
            return true;
        }

        if ($user->hasPermission('medical_record.edit.own')) {
            $recordId = $context['id'] ?? null;
            if (!$recordId) {
                return false;
            }

            return $this->isOwner($user, (int)$recordId);
        }
        return false;
    }

    public function create(User $user, array $context): bool
    {
        return $user->hasPermission('medical_record.create');
    }

    private function isOwner(User $user, int $recordId): bool
    {
        $userId = $user->getId();
        if (!$userId) {
            return false;
        }
        $medicalRecord = $this->medicalRecordRepository->findById($recordId);
        return $medicalRecord && (int)$medicalRecord['doctor_id'] === $userId;
    }
}
