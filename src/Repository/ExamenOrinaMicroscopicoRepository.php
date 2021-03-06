<?php

namespace App\Repository;

use App\Entity\ExamenOrinaMicroscopico;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ExamenOrinaMicroscopico|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExamenOrinaMicroscopico|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExamenOrinaMicroscopico[]    findAll()
 * @method ExamenOrinaMicroscopico[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExamenOrinaMicroscopicoRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ExamenOrinaMicroscopico::class);
    }

    // /**
    //  * @return ExamenOrinaMicroscopico[] Returns an array of ExamenOrinaMicroscopico objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ExamenOrinaMicroscopico
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
