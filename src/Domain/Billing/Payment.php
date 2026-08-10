<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Domain\Billing;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
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

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getInvoiceId() : ?int
    {
        return $this->invoice_id;
    }

    public function setInvoiceId(?int $invoice_id) : self
    {
        $this->invoice_id = $invoice_id;
        return $this;
    }

    public function getAmount() : ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount) : self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPaymentMethod() : ?string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(?string $payment_method) : self
    {
        $this->payment_method = $payment_method;
        return $this;
    }

    public function getTransactionId() : ?string
    {
        return $this->transaction_id;
    }

    public function setTransactionId(?string $transaction_id) : self
    {
        $this->transaction_id = $transaction_id;
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

    public function getPaymentDate() : ?\DateTimeInterface
    {
        return $this->payment_date;
    }

    public function setPaymentDate(?\DateTimeInterface $payment_date) : self
    {
        $this->payment_date = $payment_date;
        return $this;
    }

    public function setId(?int $id) : self
    {
        $this->id = $id;
        return $this;
    }
}
