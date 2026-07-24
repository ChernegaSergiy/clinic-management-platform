<?php

namespace App\Module\Billing\Repository;

use App\Entity\Invoice;
use App\Core\Event\EventDispatcherService;
use App\Event\EntityChangedEvent;
use App\Event\PatientNotificationEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvoiceRepository extends ServiceEntityRepository implements InvoiceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function findAll(string $searchTerm = ''): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                i.id, 
                CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                i.amount,
                i.status,
                i.issued_date
            FROM invoices i
            JOIN patients p ON i.patient_id = p.id
        ";

        $params = [];
        if (!empty($searchTerm)) {
            $sql .= " WHERE (p.last_name LIKE :term OR p.first_name LIKE :term"
                . " OR CONCAT(p.last_name, ' ', p.first_name) LIKE :term OR i.status LIKE :term";
            if (is_numeric($searchTerm)) {
                $sql .= " OR i.id = :idExact";
                $params['idExact'] = (int)$searchTerm;
            }
            $sql .= ")";
            $params['term'] = '%' . $searchTerm . '%';
        }

        $sql .= " ORDER BY i.issued_date DESC";

        return $conn->fetchAllAssociative($sql, $params);
    }

    public function save(array $data): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "INSERT INTO invoices (patient_id, appointment_id, medical_record_id, amount, status, notes, type) 
                VALUES (:patient_id, :appointment_id, :medical_record_id, :amount, :status, :notes, :type)";

        $conn->executeStatement($sql, [
            'patient_id' => $data['patient_id'],
            'appointment_id' => $data['appointment_id'] ?? null,
            'medical_record_id' => $data['medical_record_id'] ?? null,
            'amount' => $data['amount'],
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null,
            'type' => $data['type'] ?? 'invoice',
        ]);
        
        $invoiceId = (int)$conn->lastInsertId();
        
        EventDispatcherService::getDispatcher()->dispatch(new EntityChangedEvent('invoice', $invoiceId, 'create', null, $data));
        EventDispatcherService::getDispatcher()->dispatch(new PatientNotificationEvent(
            $data['patient_id'],
            'invoice_created',
            sprintf('Створено рахунок на суму %.2f грн', $data['amount']),
            ['invoice_id' => $invoiceId]
        ));
        
        return $invoiceId;
    }

    public function updateInsuranceDue(int $invoiceId, float $insuranceDue, float $patientDue): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE invoices SET insurance_due = :insurance_due, patient_due = :patient_due WHERE id = :id";
        
        return $conn->executeStatement($sql, [
            'id' => $invoiceId,
            'insurance_due' => $insuranceDue,
            'patient_due' => $patientDue,
        ]) > 0;
    }

    public function findById(int $id): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                i.*,
                CONCAT(p.last_name, ' ', p.first_name) as patient_name
            FROM invoices i
            JOIN patients p ON i.patient_id = p.id
            WHERE i.id = :id
        ";
        
        $result = $conn->fetchAssociative($sql, ['id' => $id]);

        if ($result) {
            $result['payments'] = $this->getPaymentsForInvoice($id);
            $result['total_paid'] = array_sum(array_column($result['payments'], 'amount'));
            $result['remaining_amount'] = $result['amount'] - $result['total_paid'];
        }
        
        return $result ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $oldInvoice = $this->findById($id);
        if (!$oldInvoice) {
            return false;
        }

        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE invoices SET 
                    patient_id = :patient_id, 
                    appointment_id = :appointment_id, 
                    medical_record_id = :medical_record_id, 
                    amount = :amount, 
                    status = :status, 
                    notes = :notes,
                    paid_date = :paid_date,
                    type = :type
                WHERE id = :id";

        $result = $conn->executeStatement($sql, [
            'id' => $id,
            'patient_id' => $data['patient_id'],
            'appointment_id' => $data['appointment_id'] ?? null,
            'medical_record_id' => $data['medical_record_id'] ?? null,
            'amount' => $data['amount'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'paid_date' => ($data['status'] === 'paid' && !empty($data['paid_date'])) ? $data['paid_date'] : null,
            'type' => $data['type'] ?? 'invoice',
        ]);

        if ($result > 0) {
            EventDispatcherService::getDispatcher()->dispatch(new EntityChangedEvent('invoice', $id, 'update', $oldInvoice, $data));
            return true;
        }

        return false;
    }

    public function addPayment(
        int $invoiceId,
        float $amount,
        string $paymentMethod,
        ?string $transactionId = null,
        ?string $notes = null
    ): bool {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        
        try {
            $sql = "INSERT INTO payments (invoice_id, amount, payment_method, transaction_id, notes) 
                    VALUES (:invoice_id, :amount, :payment_method, :transaction_id, :notes)";
                    
            $success = $conn->executeStatement($sql, [
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'notes' => $notes,
            ]);

            if ($success > 0) {
                $invoice = $this->findById($invoiceId);
                if (
                    $invoice &&
                    $invoice['remaining_amount'] <= 0.01 &&
                    $invoice['status'] !== 'paid'
                ) {
                    $updateData = array_merge($invoice, [
                        'status' => 'paid',
                        'paid_date' => date('Y-m-d H:i:s')
                    ]);
                    $this->update($invoiceId, $updateData);
                }
            }
            
            $conn->commit();
            return $success > 0;
        } catch (\Exception $e) {
            $conn->rollBack();
            return false;
        }
    }

    public function getPaymentsForInvoice(int $invoiceId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM payments WHERE invoice_id = :invoice_id ORDER BY payment_date DESC";
        return $conn->fetchAllAssociative($sql, ['invoice_id' => $invoiceId]);
    }

    public function logFinancialTransaction(
        int $patientId,
        float $amount,
        string $transactionType,
        string $description,
        ?int $entityId = null
    ): bool {
        return true;
    }

    public function sumTotalAmountByDate(string $date): float
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $paymentsSql = "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE(payment_date) = :date";
        $paymentsSum = (float)$conn->fetchOne($paymentsSql, ['date' => $date]);

        $invoicesSql = "
            SELECT COALESCE(SUM(i.amount), 0)
            FROM invoices i
            WHERE i.status = 'paid'
              AND DATE(COALESCE(i.paid_date, i.issued_date)) = :date
              AND NOT EXISTS (
                  SELECT 1 FROM payments p WHERE p.invoice_id = i.id
              )
        ";
        $invoicesSum = (float)$conn->fetchOne($invoicesSql, ['date' => $date]);

        return $paymentsSum + $invoicesSum;
    }

    public function getDailyRevenueForPeriod(string $startDate, string $endDate): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                DATE(issued_date) as date,
                SUM(amount) as total_revenue
            FROM invoices
            WHERE status = 'paid' AND DATE(issued_date) BETWEEN :start_date AND :end_date
            GROUP BY DATE(issued_date)
            ORDER BY DATE(issued_date) ASC
        ";
        
        return $conn->fetchAllAssociative($sql, [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function sumRevenueForPeriod(string $startDate, string $endDate): float
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT COALESCE(SUM(amount), 0) 
            FROM invoices 
            WHERE status = 'paid' AND DATE(issued_date) BETWEEN :start_date AND :end_date
        ";
        
        return (float)$conn->fetchOne($sql, [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
