<?php

declare(strict_types=1);

namespace App\Module\Insurance\Service;

use App\Module\Billing\Repository\InvoiceRepository;
use App\Module\Insurance\Repository\ClaimRepository;
use App\Module\Insurance\Repository\InsuranceCompanyRepository;
use App\Module\Insurance\Repository\PatientInsurancePolicyRepository;

class InsuranceService
{
    private InsuranceCompanyRepository $insuranceCompanyRepository;
    private PatientInsurancePolicyRepository $patientInsurancePolicyRepository;
    private ClaimRepository $claimRepository;
    private InvoiceRepository $invoiceRepository;

    public function __construct(
        InsuranceCompanyRepository $insuranceCompanyRepository,
        PatientInsurancePolicyRepository $patientInsurancePolicyRepository,
        ClaimRepository $claimRepository,
        InvoiceRepository $invoiceRepository
    ) {
        $this->insuranceCompanyRepository = $insuranceCompanyRepository;
        $this->patientInsurancePolicyRepository = $patientInsurancePolicyRepository;
        $this->claimRepository = $claimRepository;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function addInsuranceCompany(string $name, ?string $contactPerson = null, ?string $phone = null, ?string $email = null, ?string $notes = null): int
    {
        return $this->insuranceCompanyRepository->create($name, $contactPerson, $phone, $email, $notes);
    }

    public function getInsuranceCompany(int $id): ?array
    {
        return $this->insuranceCompanyRepository->findById($id);
    }

    public function getAllInsuranceCompanies(): array
    {
        return $this->insuranceCompanyRepository->findAll();
    }

    public function updateInsuranceCompany(int $id, string $name, ?string $contactPerson = null, ?string $phone = null, ?string $email = null, ?string $notes = null): bool
    {
        return $this->insuranceCompanyRepository->update($id, $name, $contactPerson, $phone, $email, $notes);
    }

    public function deleteInsuranceCompany(int $id): bool
    {
        return $this->insuranceCompanyRepository->delete($id);
    }

    public function addPolicyToPatient(int $patientId, int $insuranceCompanyId, string $policyNumber, ?string $groupNumber, string $validFrom, ?string $validTo, bool $isActive): int
    {
        return $this->patientInsurancePolicyRepository->create($patientId, $insuranceCompanyId, $policyNumber, $groupNumber, $validFrom, $validTo, $isActive);
    }

    public function getPatientPolicies(int $patientId): array
    {
        return $this->patientInsurancePolicyRepository->findByPatientId($patientId);
    }

    public function getPatientPolicy(int $id): ?array
    {
        return $this->patientInsurancePolicyRepository->findById($id);
    }

    public function updatePatientPolicy(int $id, int $patientId, int $insuranceCompanyId, string $policyNumber, ?string $groupNumber, string $validFrom, ?string $validTo, bool $isActive): bool
    {
        return $this->patientInsurancePolicyRepository->update($id, $patientId, $insuranceCompanyId, $policyNumber, $groupNumber, $validFrom, $validTo, $isActive);
    }

    public function deletePatientPolicy(int $id): bool
    {
        return $this->patientInsurancePolicyRepository->delete($id);
    }

    public function createClaim(int $invoiceId, int $patientPolicyId, float $totalClaimed, string $status = 'draft', ?string $submittedAt = null): int
    {
        return $this->claimRepository->create($invoiceId, $patientPolicyId, $status, $totalClaimed, $submittedAt);
    }

    public function getClaim(int $id): ?array
    {
        return $this->claimRepository->findById($id);
    }

    public function getClaimWithDetails(int $id): ?array
    {
        return $this->claimRepository->findByIdWithDetails($id);
    }

    public function getClaimsForInvoice(int $invoiceId): ?array
    {
        return $this->claimRepository->findByInvoiceId($invoiceId);
    }

    public function getClaimsForPatientPolicy(int $patientPolicyId): array
    {
        return $this->claimRepository->findByPatientPolicyId($patientPolicyId);
    }

    public function getAllClaims(): array
    {
        return $this->claimRepository->findAll();
    }

    public function updateClaimStatus(int $id, string $status, ?string $submittedAt = null, ?float $totalPaid = null): bool
    {
        $success = $this->claimRepository->update($id, $status, $submittedAt, $totalPaid);

        if ($success && $status === 'paid' && $totalPaid > 0) {
            $claim = $this->claimRepository->findById($id);
            if ($claim) {
                $this->invoiceRepository->addPayment(
                    (int)$claim['invoice_id'],
                    $totalPaid,
                    'insurance',
                    'claim_id:' . $id
                );
            }
        }

        return $success;
    }
}
