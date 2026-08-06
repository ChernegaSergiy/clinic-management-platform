<?php

namespace App\Entity;

use App\Module\Appointment\Repository\AppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ORM\Table(name: 'appointments')]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false)]
    private ?Patient $patient = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'doctor_id', referencedColumnName: 'id', nullable: false)]
    private ?User $doctor = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $start_time = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $end_time = null;

    #[ORM\Column(length: 50, options: ['default' => 'scheduled'])]
    private string $status = 'scheduled';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $waitlist_id = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $room_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ehealth_episode_id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getPatient() : ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient) : static
    {
        $this->patient = $patient;
        return $this;
    }

    public function getDoctor() : ?User
    {
        return $this->doctor;
    }

    public function setDoctor(?User $doctor) : static
    {
        $this->doctor = $doctor;
        return $this;
    }

    public function getStartTime() : ?\DateTimeInterface
    {
        return $this->start_time;
    }

    public function setStartTime(\DateTimeInterface $startTime) : static
    {
        $this->start_time = $startTime;
        return $this;
    }

    public function getEndTime() : ?\DateTimeInterface
    {
        return $this->end_time;
    }

    public function setEndTime(\DateTimeInterface $endTime) : static
    {
        $this->end_time = $endTime;
        return $this;
    }

    public function getStatus() : string
    {
        return $this->status;
    }

    public function setStatus(string $status) : static
    {
        $this->status = $status;
        return $this;
    }

    public function getNotes() : ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes) : static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getWaitlistId() : ?int
    {
        return $this->waitlist_id;
    }

    public function setWaitlistId(?int $waitlistId) : static
    {
        $this->waitlist_id = $waitlistId;
        return $this;
    }

    public function getRoomId() : ?int
    {
        return $this->room_id;
    }

    public function setRoomId(?int $roomId) : static
    {
        $this->room_id = $roomId;
        return $this;
    }

    public function getCreatedAt() : ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt) : static
    {
        $this->created_at = $createdAt;
        return $this;
    }

    public function getUpdatedAt() : ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt) : static
    {
        $this->updated_at = $updatedAt;
        return $this;
    }
}
