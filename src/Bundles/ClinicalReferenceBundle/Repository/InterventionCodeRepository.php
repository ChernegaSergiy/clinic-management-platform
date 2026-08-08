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

namespace App\Bundles\ClinicalReferenceBundle\Repository;

use App\Entity\InterventionCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InterventionCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InterventionCode::class);
    }

    public function findAll() : array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('i.id', 'i.code', 'i.description')
            ->orderBy('i.code', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }

    public function countAll() : int
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function replaceAll(array $rows) : int
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {
            $em->createQuery('DELETE FROM App\Entity\InterventionCode')->execute();

            $count = 0;
            $seen = [];
            foreach ($rows as $row) {
                $code = trim($row['code'] ?? '');
                if ('' === $code || '-' === $code) {
                    continue; // пропускаємо пусті/технічні коди
                }
                if (isset($seen[$code])) {
                    continue; // уникаємо дублювання
                }
                $description = $row['description'] ?? '';

                $intervention = new InterventionCode();
                $intervention->setCode($code);
                $intervention->setDescription($description);
                $em->persist($intervention);

                $seen[$code] = true;
                $count++;

                if (0 === $count % 1000) {
                    $em->flush();
                    $em->clear();
                }
            }
            $em->flush();
            $em->commit();
            return $count;
        } catch (\Throwable $e) {
            $em->rollBack();
            throw $e;
        }
    }

    public function searchByCodeOrDescription(string $searchTerm) : array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('i.id', 'i.code', 'i.description')
            ->where('i.code LIKE :term')
            ->orWhere('i.description LIKE :term')
            ->setParameter('term', '%' . $searchTerm . '%')
            ->orderBy('i.code', 'ASC')
            ->setMaxResults(20);

        return $qb->getQuery()->getArrayResult();
    }
}
