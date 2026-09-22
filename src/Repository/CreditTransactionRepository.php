<?php

namespace App\Repository;

use App\Entity\CreditTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CreditTransaction>
 */
class CreditTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditTransaction::class);
    }

    public function platformFeesByDay(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        return $connection->fetchAllAssociative(
            <<<SQL
                SELECT
                    DATE(created_at) AS day,
                    SUM(amount) AS total
                FROM credit_transaction
                WHERE transaction_type = :transactionType
                GROUP BY DATE(created_at)
                ORDER BY day ASC
            SQL,
            [
                'transactionType' => 'PLATFORM_FEE',
            ]
        );
    }

    public function platformFeesTotal(): int
    {
        $connection = $this->getEntityManager()->getConnection();

        return (int) $connection->fetchOne(
            <<<SQL
                SELECT COALESCE(SUM(amount), 0)
                FROM credit_transaction
                WHERE transaction_type = :transactionType
            SQL,
            [
                'transactionType' => 'PLATFORM_FEE',
            ]
        );
    }

    //    /**
    //     * @return CreditTransaction[] Returns an array of CreditTransaction objects
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

    //    public function findOneBySomeField($value): ?CreditTransaction
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
