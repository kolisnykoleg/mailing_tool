<?php

namespace App\Repository;

use App\Entity\Pool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Pool|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pool|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pool[]    findAll()
 * @method Pool[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PoolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pool::class);
    }

    public function getFreeIndex(string $name): int
    {
        $pool = $this->createQueryBuilder('p')
            ->where('p.name LIKE :name')
            ->setParameter('name', "$name%")
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $pool
            ? array_slice(explode(substr($name, -1), $pool->getName()), -1)[0] + 1
            : 1;
    }

    // /**
    //  * @return Pool[] Returns an array of Pool objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Pool
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
