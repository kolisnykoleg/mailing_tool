<?php

namespace App\Repository;

use App\Entity\NameGender;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NameGender|null find($id, $lockMode = null, $lockVersion = null)
 * @method NameGender|null findOneBy(array $criteria, array $orderBy = null)
 * @method NameGender[]    findAll()
 * @method NameGender[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NameGenderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NameGender::class);
    }

    // /**
    //  * @return NameGenger[] Returns an array of NameGenger objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NameGenger
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
