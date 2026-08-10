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

namespace App\Core\Repository;

use App\Entity\Attachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attachment::class);
    }

    public function findById(int $id) : ?Attachment
    {
        return $this->find($id);
    }

    public function getAttachmentsForEntity(string $entityType, int $entityId) : array
    {
        return $this->createQueryBuilder('a')
            ->where('a.entity_type = :entity_type')
            ->andWhere('a.entity_id = :entity_id')
            ->orderBy('a.created_at', 'DESC')
            ->setParameter('entity_type', $entityType)
            ->setParameter('entity_id', $entityId)
            ->getQuery()
            ->getResult();
    }

    public function save(Attachment $attachment) : void
    {
        $this->getEntityManager()->persist($attachment);
        $this->getEntityManager()->flush();
    }
}
