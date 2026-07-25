<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Module\Billing\Repository\InvoiceRepository;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoices')]
class Invoice
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $patient_id = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $appointment_id = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $medical_record_id = null;
    #[ORM\Column(type: Types::FLOAT)] private ?float $amount = null;
    #[ORM\Column(length: 50)] private ?string $status = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $notes = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $type = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $issued_date = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $paid_date = null;
    #[ORM\Column(type: Types::FLOAT, nullable: true)] private ?float $insurance_due = null;
    #[ORM\Column(type: Types::FLOAT, nullable: true)] private ?float $patient_due = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $created_at = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $updated_at = null;
}
