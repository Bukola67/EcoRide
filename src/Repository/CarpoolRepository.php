<?php

namespace App\Repository;

use App\Entity\Carpool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\Query;

class CarpoolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Carpool::class);
    }

    /**
     * Retourne les covoiturages disponibles pour une date civile précise.
     *
     * @return Carpool[]
     */
    public function findAvailableForSearch(
        string $departureCity,
        string $arrivalCity,
        \DateTimeImmutable $date
    ): array {
        $startOfDay = $date->setTime(0, 0);
        $endOfDay = $date->setTime(23, 59, 59);

        return $this->createQueryBuilder('c')
            ->innerJoin('c.vehicle', 'v')
            ->addSelect('v')
            ->innerJoin('c.driver', 'd')
            ->addSelect('d')
            ->andWhere('LOWER(c.departureCity) = LOWER(:departureCity)')
            ->andWhere('LOWER(c.arrivalCity) = LOWER(:arrivalCity)')
            ->andWhere('c.departureAt BETWEEN :startOfDay AND :endOfDay')
            ->andWhere('c.remainingSeatCount > 0')
            ->andWhere('c.status = :plannedStatus')
            ->setParameter('departureCity', trim($departureCity))
            ->setParameter('arrivalCity', trim($arrivalCity))
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->setParameter('plannedStatus', 'PLANNED')
            ->orderBy('c.departureAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le tout premier trajet futur disponible pour le même itinéraire.
     */
    public function findNextAvailableForRoute(
        string $departureCity,
        string $arrivalCity,
        \DateTimeImmutable $afterDate
    ): ?Carpool {
        $afterDate = $afterDate->setTime(23, 59, 59);

        return $this->createQueryBuilder('c')
            ->innerJoin('c.vehicle', 'v')
            ->addSelect('v')
            ->innerJoin('c.driver', 'd')
            ->addSelect('d')
            ->andWhere('LOWER(c.departureCity) = LOWER(:departureCity)')
            ->andWhere('LOWER(c.arrivalCity) = LOWER(:arrivalCity)')
            ->andWhere('c.departureAt > :afterDate')
            ->andWhere('c.remainingSeatCount > 0')
            ->andWhere('c.status = :plannedStatus')
            ->setParameter('departureCity', trim($departureCity))
            ->setParameter('arrivalCity', trim($arrivalCity))
            ->setParameter('afterDate', $afterDate)
            ->setParameter('plannedStatus', 'PLANNED')
            ->orderBy('c.departureAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForLock(int $id): ?Carpool
    {
        return $this->find($id);
    }


    public function platformFeesTotal(): int
    {
        return (int) $this->createQueryBuilder('ct')
            ->select('COALESCE(SUM(ct.amount), 0)')
            ->where('ct.transactionType = :type')
            ->setParameter('type', 'PLATFORM_FEE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCompletedByDay(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        return $connection->fetchAllAssociative(
            <<<SQL
                SELECT
                    DATE(departure_at) AS day,
                    COUNT(id) AS total
                FROM carpool
                WHERE status = :status
                GROUP BY DATE(departure_at)
                ORDER BY day ASC
            SQL,
            [
                'status' => 'COMPLETED',
            ]
        );
    }
}