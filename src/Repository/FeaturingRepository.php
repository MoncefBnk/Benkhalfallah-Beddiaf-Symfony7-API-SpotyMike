<?php

namespace App\Repository;

use App\Entity\Featuring;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Featuring>
 *
 * @method Featuring|null find($id, $lockMode = null, $lockVersion = null)
 * @method Featuring|null findOneBy(array $criteria, array $orderBy = null)
 * @method Featuring[]    findAll()
 * @method Featuring[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeaturingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Featuring::class);
    }

    //    /**
    //     * @return Featuring[] Returns an array of Featuring objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Featuring
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
