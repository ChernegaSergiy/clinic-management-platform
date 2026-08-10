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

namespace App\Bundles\BillingBundle\Repository;

use App\Entity\BundleService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BundleServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BundleService::class);
    }

    public function getServicesInBundle(int $bundleId) : array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('s.id', 's.name', 's.price')
            ->from(BundleService::class, 'bs')
            ->join(\App\Entity\Service::class, 's', \Doctrine\ORM\Query\Expr\Join::WITH, 'bs.service_id = s.id')
            ->where('bs.bundle_id = :bundle_id')
            ->setParameter('bundle_id', $bundleId)
            ->orderBy('s.name', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }

    public function syncServices(int $bundleId, array $serviceIds) : void
    {
        $em = $this->getEntityManager();

        $deleteQb = $this->createQueryBuilder('bs')
            ->delete(BundleService::class, 'bs')
            ->where('bs.bundle_id = :bundle_id')
            ->setParameter('bundle_id', $bundleId);

        $deleteQb->getQuery()->execute();

        if (empty($serviceIds)) {
            return;
        }

        foreach ($serviceIds as $serviceId) {
            $bs = new BundleService();
            $bs->setBundleId($bundleId);
            $bs->setServiceId($serviceId);
            $em->persist($bs);
        }
        $em->flush();
    }
}
