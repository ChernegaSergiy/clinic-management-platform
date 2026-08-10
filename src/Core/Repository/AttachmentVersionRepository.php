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

use App\Entity\AttachmentVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AttachmentVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttachmentVersion::class);
    }

    public function getVersionsForAttachment(int $attachmentId) : array
    {
        return $this->createQueryBuilder('v')
            ->where('v.attachment = :attachment_id')
            ->orderBy('v.version_number', 'DESC')
            ->setParameter('attachment_id', $attachmentId)
            ->getQuery()
            ->getResult();
    }

    public function getVersion(int $attachmentId, int $versionNumber) : ?AttachmentVersion
    {
        return $this->createQueryBuilder('v')
            ->where('v.attachment = :attachment_id')
            ->andWhere('v.version_number = :version_number')
            ->setParameter('attachment_id', $attachmentId)
            ->setParameter('version_number', $versionNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(AttachmentVersion $version) : void
    {
        $this->getEntityManager()->persist($version);
        $this->getEntityManager()->flush();
    }
}
