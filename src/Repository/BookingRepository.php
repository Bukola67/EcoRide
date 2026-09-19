<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Enum\PostRideValidation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findPendingPostRideValidationForPassenger(
    User $passenger
    ): ?Booking {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.carpool', 'c')
            ->addSelect('c')
            ->where('b.passenger = :passenger')
            ->andWhere('b.status = :bookingStatus')
            ->andWhere('b.postRideValidation = :validationStatus')
            ->andWhere('c.status = :carpoolStatus')
            ->setParameter('passenger', $passenger)
            ->setParameter('bookingStatus', 'CONFIRMED')
            ->setParameter(
                'validationStatus',
                PostRideValidation::Pending
            )
            ->setParameter('carpoolStatus', 'COMPLETED')
            ->orderBy('c.arrivalAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
