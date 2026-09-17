<?php

namespace App\Repository;

use App\Entity\Carpool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
            ->andWhere('c.status != :cancelledStatus')
            ->setParameter('departureCity', trim($departureCity))
            ->setParameter('arrivalCity', trim($arrivalCity))
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->setParameter('cancelledStatus', 'CANCELLED')
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
            ->andWhere('c.status != :cancelledStatus')
            ->setParameter('departureCity', trim($departureCity))
            ->setParameter('arrivalCity', trim($arrivalCity))
            ->setParameter('afterDate', $afterDate)
            ->setParameter('cancelledStatus', 'CANCELLED')
            ->orderBy('c.departureAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}