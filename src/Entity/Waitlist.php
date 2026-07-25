<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'waitlists')]
class Waitlist
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 255)] private ?string $ticket_number = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $patient_id = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)] private ?int $desired_doctor_id = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $desired_start_time = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $desired_end_time = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $notes = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $contact_phone = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $contact_email = null;
    #[ORM\Column(length: 50, nullable: true)] private ?string $status = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $created_at = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)] private ?\DateTimeInterface $updated_at = null;
}
