<?php

namespace App\Module\Room\Repository;

use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    public function findAll(): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.name', 'ASC');
            
        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    public function findById(int $id): ?array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.id = :id')
            ->setParameter('id', $id);
            
        $result = $qb->getQuery()->getOneOrNullResult(Query::HYDRATE_ARRAY);
        return $result ?: null;
    }

    public function findAvailable(): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.is_available = 1')
            ->orderBy('r.name', 'ASC');
            
        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    public function create(array $data): int
    {
        $room = new Room();
        $room->setName($data['name']);
        $room->setType($data['type']);
        $room->setCapacity((int)$data['capacity']);
        
        if (array_key_exists('location', $data)) {
            $room->setLocation($data['location']);
        }
        
        if (array_key_exists('equipment', $data)) {
            $room->setEquipment($data['equipment']);
        }
        
        if (array_key_exists('is_available', $data)) {
            $room->setIsAvailable((bool)$data['is_available']);
        }

        $this->getEntityManager()->persist($room);
        $this->getEntityManager()->flush();

        return $room->getId();
    }

    public function update(int $id, array $data): bool
    {
        /** @var Room|null $room */
        $room = $this->find($id);
        
        if (!$room) {
            return false;
        }

        if (array_key_exists('name', $data)) {
            $room->setName($data['name']);
        }
        
        if (array_key_exists('type', $data)) {
            $room->setType($data['type']);
        }
        
        if (array_key_exists('capacity', $data)) {
            $room->setCapacity((int)$data['capacity']);
        }
        
        if (array_key_exists('location', $data)) {
            $room->setLocation($data['location']);
        }
        
        if (array_key_exists('equipment', $data)) {
            $room->setEquipment($data['equipment']);
        }
        
        if (array_key_exists('is_available', $data)) {
            $room->setIsAvailable((bool)$data['is_available']);
        }

        try {
            $this->getEntityManager()->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        $room = $this->find($id);
        
        if (!$room) {
            return false;
        }

        try {
            $this->getEntityManager()->remove($room);
            $this->getEntityManager()->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
