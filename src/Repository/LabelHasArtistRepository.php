<?php

namespace App\Repository;

use App\Entity\LabelHasArtist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LabelHasArtist>
 *
 * @method LabelHasArtist|null find($id, $lockMode = null, $lockVersion = null)
 * @method LabelHasArtist|null findOneBy(array $criteria, array $orderBy = null)
 * @method LabelHasArtist[]    findAll()
 * @method LabelHasArtist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LabelHasArtistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LabelHasArtist::class);
    }

    //    /**
    //     * @return LabelHasArtist[] Returns an array of LabelHasArtist objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?LabelHasArtist
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
