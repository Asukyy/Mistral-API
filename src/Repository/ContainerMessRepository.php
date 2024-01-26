<?php

namespace App\Repository;

use App\Entity\ContainerMess;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContainerMess>
 *
 * @method ContainerMess|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContainerMess|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContainerMess[]    findAll()
 * @method ContainerMess[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContainerMessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContainerMess::class);
    }

//    /**
//     * @return ContainerMess[] Returns an array of ContainerMess objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ContainerMess
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
