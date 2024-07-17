<?php

namespace App\Repository;

use App\Config\TransactionType;
use App\Dto\TransactionIndexFilterDto;
use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByUserWithFilter(User $user, TransactionIndexFilterDto $filter): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->andWhere('t.billing_user = :user_id')
            ->setParameter('user_id', $user->getId());
        if ($filter->type) {
            $queryBuilder->andWhere('t.type = :type')
                ->setParameter('type', TransactionType::stringToType($filter->type));
        }
        if ($filter->skip_expired) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->isNull('t.expires_at')
            );
        }
        if ($filter->course_code) {
            $queryBuilder->join('t.course', 'tc')
                ->andWhere('tc.chars_code = :course_code')
                ->setParameter('course_code', $filter->course_code);
        }
        return $queryBuilder->getQuery()->getResult();
    }

    

    //    /**
    //     * @return Transaction[] Returns an array of Transaction objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Transaction
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
