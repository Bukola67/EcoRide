<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Carpool;
use App\Entity\CreditTransaction;
use App\Entity\User;
use App\Enum\PostRideValidation;
use App\Repository\BookingRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class BookingService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BookingRepository $bookingRepository,
    ) {
    }

    /**
     * @throws \RuntimeException Si la réservation est impossible
     */
    public function book(Carpool $carpool, User $passenger): Booking
    {
        $this->em->getConnection()->beginTransaction();

        try {
            // Verrouillage pessimiste du trajet
            $carpool = $this->em->getRepository(Carpool::class)->createQueryBuilder('c')
                ->where('c.id = :id')
                ->setParameter('id', $carpool->getId())
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$carpool) {
                throw new \RuntimeException('Covoiturage introuvable.');
            }

            // Verrouillage pessimiste de l'utilisateur (pour le solde)
            $passenger = $this->em->getRepository(User::class)->createQueryBuilder('u')
                ->where('u.id = :id')
                ->setParameter('id', $passenger->getId())
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$passenger) {
                throw new \RuntimeException('Utilisateur introuvable.');
            }

            // 1. Compte actif
            if (!$passenger->isActive()) {
                throw new \RuntimeException('Votre compte n’est plus actif.');
            }

            // 2. Trajet à l’état PLANNED
            if ($carpool->getStatus() !== 'PLANNED') {
                throw new \RuntimeException('Ce trajet n’est plus disponible.');
            }

            // 3. Places restantes
            if ($carpool->getRemainingSeatCount() <= 0) {
                throw new \RuntimeException('Plus de places disponibles.');
            }

            // 4. Le passager ne peut pas réserver son propre trajet
            if ($carpool->getDriver()->getId() === $passenger->getId()) {
                throw new \RuntimeException('Vous ne pouvez pas réserver votre propre trajet.');
            }

            // 5. Pas de double réservation
            $existingBooking = $this->bookingRepository->findOneBy([
                'passenger' => $passenger,
                'carpool' => $carpool,
            ]);

            if ($existingBooking && $existingBooking->getStatus() === 'CONFIRMED') {
                throw new \RuntimeException(
                    'Vous avez déjà réservé ce trajet.'
                );
            }

            // 6. Crédits suffisants
            $price = $carpool->getCreditCostPerPassenger();
            if ($passenger->getCredits() < $price) {
                throw new \RuntimeException('Crédits insuffisants pour ce trajet.');
            }

            // 7. Vérification que la réservation ne va pas entraîner un solde négatif (au cas où le passager aurait perdu des crédits entre-temps)
            if (($passenger->getCredits() - $price) < 0) {
                throw new \RuntimeException('Cette réservation entraînerait un solde négatif.');
            }

            // Création réservation
            if ($existingBooking && $existingBooking->getStatus() === 'CANCELLED') {
                /*
                * Réservation renouvelée : on conserve l’unique ligne Booking,
                * compatible avec la contrainte SQL (passenger_id, carpool_id).
                */
                $booking = $existingBooking;
                $booking->setStatus('CONFIRMED');
                $booking->setPostRideValidation(PostRideValidation::Pending);
            } else {
                $booking = new Booking();
                $booking->setPassenger($passenger);
                $booking->setCarpool($carpool);
                $booking->setStatus('CONFIRMED');
                $booking->setPostRideValidation(PostRideValidation::Pending);

                $this->em->persist($booking);
            }
            

            // Débit passager
            $passenger->setCredits($passenger->getCredits() - $price);

            // Transaction
            $transaction = new CreditTransaction();
            $transaction->setAmount(-$price);
            $transaction->setTransactionType('BOOKING_DEBIT');
            $transaction->setDescription('Réservation trajet #' . $carpool->getId());
            $transaction->setCreatedAt(new \DateTimeImmutable());
            $transaction->setUser($passenger);
            $transaction->setCarpool($carpool);

            $this->em->persist($transaction);

            // Décrémenter places
            $carpool->setRemainingSeatCount($carpool->getRemainingSeatCount() - 1);

            $this->em->flush();
            $this->em->getConnection()->commit();

            return $booking;
        } catch (\Throwable $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }
}