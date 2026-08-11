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

namespace App\Domain\Prescription;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PrescriptionItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrescriptionItem::class);
    }

    public function saveItems(int $prescriptionId, array $items) : void
    {
        $em = $this->getEntityManager();
        foreach ($items as $item) {
            $pi = new PrescriptionItem();
            $pi->setPrescriptionId($prescriptionId);
            $pi->setMedicationName($item['medication_name']);
            $pi->setDosage($item['dosage']);
            $pi->setFrequency($item['frequency']);
            $pi->setDuration($item['duration'] ?? null);
            $pi->setNotes($item['notes'] ?? null);
            $em->persist($pi);
        }
        $em->flush();
    }

    public function findItemsByPrescriptionId(int $prescriptionId) : array
    {
        $qb = $this->createQueryBuilder('pi')
            ->where('pi.prescription_id = :prescription_id')
            ->setParameter('prescription_id', $prescriptionId);

        return $qb->getQuery()->getArrayResult();
    }
}
