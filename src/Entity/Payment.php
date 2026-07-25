<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payments')]
class Payment
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $invoice_id = null;
    #[ORM\Column(type: Types::FLOAT)] private ?float $amount = null;
    #[ORM\Column(length: 50)] private ?string $payment_method = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $transaction_id = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $notes = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $payment_date = null;
}
