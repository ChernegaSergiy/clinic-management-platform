<?php

namespace App\Module\Billing\Repository;

use App\Database;
use PDO;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(string $searchTerm = ''): array
    {
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
                $params[':idExact'] = (int)$searchTerm;
            }
            $sql .= ")";
            $params[':term'] = '%' . $searchTerm . '%';
        }

        $sql .= " ORDER BY i.issued_date DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function save(array $data): bool
    {
        $sql = "INSERT INTO invoices (patient_id, appointment_id, medical_record_id, amount, status, notes, type) 
                VALUES (:patient_id, :appointment_id, :medical_record_id, :amount, :status, :notes, :type)";

        $stmt = $this->pdo->prepare($sql);

        $success = $stmt->execute([
            ':patient_id' => $data['patient_id'],
            ':appointment_id' => $data['appointment_id'] ?? null,
            ':medical_record_id' => $data['medical_record_id'] ?? null,
            ':amount' => $data['amount'],
            ':status' => $data['status'] ?? 'pending',
            ':notes' => $data['notes'] ?? null,
            ':type' => $data['type'] ?? 'invoice',
        ]);
        return $success; // Return true on success, false on failure
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                i.*,
                CONCAT(p.last_name, ' ', p.first_name) as patient_name
            FROM invoices i
            JOIN patients p ON i.patient_id = p.id
            WHERE i.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();

        if ($result) {
            $result['payments'] = $this->getPaymentsForInvoice($id);
            $result['total_paid'] = array_sum(array_column($result['payments'], 'amount'));
            $result['remaining_amount'] = $result['amount'] - $result['total_paid'];
        }
        return $result === false ? null : $result;
    }

    public function update(int $id, array $data): bool
    {
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

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(
            [
                ':id' => $id,
                ':patient_id' => $data['patient_id'],
                ':appointment_id' => $data['appointment_id'] ?? null,
                ':medical_record_id' => $data['medical_record_id'] ?? null,
                ':amount' => $data['amount'],
                ':status' => $data['status'],
                ':notes' => $data['notes'] ?? null,
                ':paid_date' => ($data['status'] === 'paid' && !empty($data['paid_date']))
                    ? $data['paid_date']
                    : null,
                ':type' => $data['type'] ?? 'invoice',
            ]
        );
    }

    public function addPayment(
        int $invoiceId,
        float $amount,
        string $paymentMethod,
        ?string $transactionId = null,
        ?string $notes = null
    ): bool {
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO payments (invoice_id, amount, payment_method, transaction_id, notes) 
                    VALUES (:invoice_id, :amount, :payment_method, :transaction_id, :notes)";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                ':invoice_id' => $invoiceId,
                ':amount' => $amount,
                ':payment_method' => $paymentMethod,
                ':transaction_id' => $transactionId,
                ':notes' => $notes,
            ]);

            if ($success) {
                // Update invoice status if fully paid
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
            $this->pdo->commit();
            return $success;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            // Log error
            return false;
        }
    }

    public function getPaymentsForInvoice(int $invoiceId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE invoice_id = :invoice_id ORDER BY payment_date DESC");
        $stmt->execute([':invoice_id' => $invoiceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Placeholder for inventory movements until proper finance ledger is implemented.
     */
    public function logFinancialTransaction(
        int $patientId,
        float $amount,
        string $transactionType,
        string $description,
        ?int $entityId = null
    ): bool {
        // No-op stub to avoid runtime errors from inventory module.
        return true;
    }

    public function sumTotalAmountByDate(string $date): float
    {
        $sql = "SELECT SUM(amount) FROM invoices WHERE DATE(issued_date) = :date AND status = 'paid'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':date' => $date]);
        $sum = $stmt->fetchColumn();
        return (float)($sum ?? 0.0);
    }

    public function getDailyRevenueForPeriod(string $startDate, string $endDate): array
    {
        $sql = "
            SELECT 
                DATE(issued_date) as date,
                SUM(amount) as total_revenue
            FROM invoices
            WHERE status = 'paid' AND DATE(issued_date) BETWEEN :start_date AND :end_date
            GROUP BY DATE(issued_date)
            ORDER BY DATE(issued_date) ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
