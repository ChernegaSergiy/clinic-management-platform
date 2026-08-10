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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function create(
        int $invoiceId,
        float $amount,
        string $paymentMethod,
        ?string $transactionId = null,
        ?string $notes = null
    ) : Payment {
        $payment = new Payment();
        $payment->setInvoiceId($invoiceId);
        $payment->setAmount($amount);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setTransactionId($transactionId);
        $payment->setNotes($notes);

        $em = $this->getEntityManager();
        $em->persist($payment);
        $em->flush();

        return $payment;
    }

    public function findByInvoiceId(int $invoiceId) : array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.invoice_id = :invoice_id')
            ->setParameter('invoice_id', $invoiceId)
            ->orderBy('p.payment_date', 'DESC');

        $results = $qb->getQuery()->getArrayResult();

        return array_map(function ($row) {
            if ($row['payment_date'] instanceof \DateTimeInterface) {
                $row['payment_date'] = $row['payment_date']->format('Y-m-d H:i:s');
            }
            return $row;
        }, $results);
    }

    public function sumAmountByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate) : float
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.amount), 0)')
            ->where('p.payment_date >= :startDate')
            ->andWhere('p.payment_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function existsSubqueryForInvoice() : QueryBuilder
    {
        return $this->createQueryBuilder('p2')
            ->select('1')
            ->where('p2.invoice_id = i.id');
    }
}
