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

namespace App\Bundles\AdminBundle\Repository;

use App\Entity\Dictionary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DictionaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dictionary::class);
    }

    // --- Dictionary Definitions ---
    public function findAll() : array
    {
        $qb = $this->createQueryBuilder('d')
            ->orderBy('d.name', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }

    public function findById(int $id) : ?array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :id')
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    public function save(array $data) : ?int
    {
        $dictionary = new Dictionary();
        $dictionary->setName($data['name']);
        $dictionary->setDescription($data['description'] ?? null);
        $dictionary->setType($data['type'] ?? null);

        try {
            $this->getEntityManager()->persist($dictionary);
            $this->getEntityManager()->flush();
            return $dictionary->getId();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function update(int $id, array $data) : bool
    {
        /** @var Dictionary|null $dictionary */
        $dictionary = $this->find($id);
        if (!$dictionary) {
            return false;
        }

        if (isset($data['name'])) {
            $dictionary->setName($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $dictionary->setDescription($data['description']);
        }
        if (array_key_exists('type', $data)) {
            $dictionary->setType($data['type']);
        }

        try {
            $this->getEntityManager()->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete(int $id) : bool
    {
        /** @var Dictionary|null $dictionary */
        $dictionary = $this->find($id);
        if (!$dictionary) {
            return false;
        }

        try {
            $this->getEntityManager()->remove($dictionary);
            $this->getEntityManager()->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // --- Dictionary Values ---
    public function findValuesByDictionaryId(int $dictionaryId) : array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('dv')
            ->from(\App\Entity\DictionaryValue::class, 'dv')
            ->where('dv.dictionary_id = :dictionary_id')
            ->setParameter('dictionary_id', $dictionaryId)
            ->orderBy('dv.order_num', 'ASC')
            ->addOrderBy('dv.label', 'ASC');

        return $qb->getQuery()->getArrayResult();
    }

    public function findValueById(int $id) : ?array
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('dv')
            ->from(\App\Entity\DictionaryValue::class, 'dv')
            ->where('dv.id = :id')
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    public function saveValue(array $data) : ?int
    {
        $value = new \App\Entity\DictionaryValue();
        $value->setDictionaryId($data['dictionary_id']);
        $value->setValue($data['value']);
        $value->setLabel($data['label']);
        $value->setOrderNum((int)($data['order_num'] ?? 0));
        $value->setIsActive((bool)($data['is_active'] ?? true));

        try {
            $this->getEntityManager()->persist($value);
            $this->getEntityManager()->flush();
            return $value->getId();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function updateValue(int $id, array $data) : bool
    {
        /** @var \App\Entity\DictionaryValue|null $value */
        $value = $this->getEntityManager()->getRepository(\App\Entity\DictionaryValue::class)->find($id);
        if (!$value) {
            return false;
        }

        if (isset($data['dictionary_id'])) {
            $value->setDictionaryId($data['dictionary_id']);
        }
        if (isset($data['value'])) {
            $value->setValue($data['value']);
        }
        if (isset($data['label'])) {
            $value->setLabel($data['label']);
        }
        if (isset($data['order_num'])) {
            $value->setOrderNum((int)$data['order_num']);
        }
        if (isset($data['is_active'])) {
            $value->setIsActive((bool)$data['is_active']);
        }

        try {
            $this->getEntityManager()->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteValue(int $id) : bool
    {
        /** @var \App\Entity\DictionaryValue|null $value */
        $value = $this->getEntityManager()->getRepository(\App\Entity\DictionaryValue::class)->find($id);
        if (!$value) {
            return false;
        }

        try {
            $this->getEntityManager()->remove($value);
            $this->getEntityManager()->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
