<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'prescription_items')]
class PrescriptionItem
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column] private ?int $id = null;
    #[ORM\Column(type: Types::INTEGER)] private ?int $prescription_id = null;
    #[ORM\Column(length: 255)] private ?string $medication_name = null;
    #[ORM\Column(length: 255)] private ?string $dosage = null;
    #[ORM\Column(length: 255)] private ?string $frequency = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $duration = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $notes = null;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function setId(?int $id) : self
    {
        $this->id = $id;
        return $this;
    }
    public function getPrescriptionId() : ?int
    {
        return $this->prescription_id;
    }
    public function setPrescriptionId(?int $prescription_id) : self
    {
        $this->prescription_id = $prescription_id;
        return $this;
    }
    public function getMedicationName() : ?string
    {
        return $this->medication_name;
    }
    public function setMedicationName(?string $medication_name) : self
    {
        $this->medication_name = $medication_name;
        return $this;
    }
    public function getDosage() : ?string
    {
        return $this->dosage;
    }
    public function setDosage(?string $dosage) : self
    {
        $this->dosage = $dosage;
        return $this;
    }
    public function getFrequency() : ?string
    {
        return $this->frequency;
    }
    public function setFrequency(?string $frequency) : self
    {
        $this->frequency = $frequency;
        return $this;
    }
    public function getDuration() : ?string
    {
        return $this->duration;
    }
    public function setDuration(?string $duration) : self
    {
        $this->duration = $duration;
        return $this;
    }
    public function getNotes() : ?string
    {
        return $this->notes;
    }
    public function setNotes(?string $notes) : self
    {
        $this->notes = $notes;
        return $this;
    }
}
