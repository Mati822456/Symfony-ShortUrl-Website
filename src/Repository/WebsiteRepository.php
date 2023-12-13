<?php

namespace App\Repository;

use App\Entity\Website;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Website>
 *
 * @method Website|null find($id, $lockMode = null, $lockVersion = null)
 * @method Website|null findOneBy(array $criteria, array $orderBy = null)
 * @method Website[]    findAll()
 * @method Website[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WebsiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Website::class);
    }

    public function add(Website $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Website $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findLikeName($searchTerm, $user = null)
    {
        $queryBuilder = $this->createQueryBuilder('e')
        ->andWhere($this->getEntityManager()->getExpressionBuilder()->like('e.url', ':searchTerm'))
        ->orWhere($this->getEntityManager()->getExpressionBuilder()->like('e.hash', ':searchTerm'))
        ->setParameter('searchTerm', '%' . $searchTerm . '%');
        
        if ($user) {
            $queryBuilder->andWhere('e.user = :userId')
                         ->setParameter('userId', $user->getId());
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findMostVisitedWebsites($limit = 5)
    {
        return $this->createQueryBuilder('w')
            ->where('w.status = 1')
            ->andWhere('w.include = 1')
            ->orderBy('w.visited', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Website[] Returns an array of Website objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('w.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Website
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
