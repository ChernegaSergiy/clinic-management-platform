<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class RepositoryTestCase extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    protected $entityManager;

    /** @var ManagerRegistry&MockObject */
    protected $managerRegistry;

    protected function setUp() : void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
    }

    protected function createMockManagerRegistry(string $entityClass) : ManagerRegistry&MockObject
    {
        $this->managerRegistry->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($this->entityManager);

        $this->entityManager->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn(new ClassMetadata($entityClass));

        return $this->managerRegistry;
    }

    protected function createMockQueryBuilder(mixed $result = null, bool $isSingleResult = false) : QueryBuilder&MockObject
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);

        if ($isSingleResult) {
            $query->method('getOneOrNullResult')->willReturn($result);
        } else {
            $query->method('getResult')->willReturn($result ?? []);
            $query->method('getArrayResult')->willReturn($result ?? []);
        }

        $queryBuilder->method('getQuery')->willReturn($query);

        // Allow method chaining
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('addSelect')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('orWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('addOrderBy')->willReturnSelf();
        $queryBuilder->method('leftJoin')->willReturnSelf();
        $queryBuilder->method('innerJoin')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();

        $this->entityManager->method('createQueryBuilder')->willReturn($queryBuilder);

        return $queryBuilder;
    }
}
