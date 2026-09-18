<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Carpool;
use App\Entity\CreditTransaction;
use App\Entity\User;
use App\Repository\BookingRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class CancellationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BookingRepository $bookingRepository,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * Annule une réservation à l'initiative du passager.
     *
     * @throws \RuntimeException lorsque l'annulation est impossible
     */
    public function cancelByPassenger(Booking $booking, User $passenger): void
    {
        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $booking = $this->em->getRepository(Booking::class)
                ->createQueryBuilder('b')
                ->where('b.id = :id')
                ->setParameter('id', $booking->getId())
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$booking) {
                throw new \RuntimeException('Réservation introuvable.');
            }

            $carpool = $this->em->getRepository(Carpool::class)
                ->createQueryBuilder('c')
                ->where('c.id = :id')
                ->setParameter('id', $booking->getCarpool()->getId())
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            $passenger = $this->em->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('u.id = :id')
                ->setParameter('id', $passenger->getId())
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$carpool || !$passenger) {
                throw new \RuntimeException('Données de réservation introuvables.');
            }

            if ($booking->getPassenger()?->getId() !== $passenger->getId()) {
                throw new \RuntimeException(
                    'Vous ne pouvez pas annuler la réservation d’un autre utilisateur.'
                );
            }

            if ($booking->getStatus() !== 'CONFIRMED') {
                throw new \RuntimeException(
                    'Cette réservation est déjà annulée ou ne peut plus être modifiée.'
                );
            }

            if ($carpool->getStatus() !== 'PLANNED') {
                throw new \RuntimeException(
                    'Cette participation ne peut plus être annulée : le trajet a démarré ou est terminé.'
                );
            }

            if ($carpool->getDepartureAt() <= new \DateTimeImmutable()) {
                throw new \RuntimeException(
                    'Cette participation ne peut plus être annulée : le départ est passé.'
                );
            }

            $amount = $carpool->getCreditCostPerPassenger();

            $booking->setStatus('CANCELLED');
            $passenger->setCredits($passenger->getCredits() + $amount);
            $carpool->setRemainingSeatCount(
                $carpool->getRemainingSeatCount() + 1
            );

            $transaction = new CreditTransaction();
            $transaction->setAmount($amount);
            $transaction->setTransactionType('BOOKING_REFUND');
            $transaction->setDescription(
                sprintf('Remboursement de la réservation du trajet #%d', $carpool->getId())
            );
            $transaction->setCreatedAt(new \DateTimeImmutable());
            $transaction->setUser($passenger);
            $transaction->setCarpool($carpool);

            $this->em->persist($transaction);
            $this->em->flush();

            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Annule un trajet à l'initiative de son chauffeur.
     *
     * @throws \RuntimeException lorsque l'annulation est impossible
     */
    public function cancelByDriver(Carpool $carpool, User $driver): void
    {
        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $carpool = $this->em->getRepository(Carpool::class)
                ->createQueryBuilder('c')
                ->where('c.id = :id')
                ->setParameter('id', $carpool->getId())
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$carpool) {
                throw new \RuntimeException('Covoiturage introuvable.');
            }

            $driver = $this->em->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('u.id = :id')
                ->setParameter('id', $driver->getId())
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$driver) {
                throw new \RuntimeException('Chauffeur introuvable.');
            }

            if ($carpool->getDriver()?->getId() !== $driver->getId()) {
                throw new \RuntimeException(
                    'Vous ne pouvez pas annuler un covoiturage qui ne vous appartient pas.'
                );
            }

            if ($carpool->getStatus() !== 'PLANNED') {
                throw new \RuntimeException(
                    'Seul un covoiturage planifié peut être annulé.'
                );
            }

            if ($carpool->getDepartureAt() <= new \DateTimeImmutable()) {
                throw new \RuntimeException(
                    'Un covoiturage dont le départ est passé ne peut plus être annulé.'
                );
            }

            $confirmedBookings = $this->bookingRepository->findBy([
                'carpool' => $carpool,
                'status' => 'CONFIRMED',
            ]);

            $price = $carpool->getCreditCostPerPassenger();
            $passengersToNotify = [];
            
            foreach ($confirmedBookings as $booking) {
                $passenger = $booking->getPassenger();

                if (!$passenger) {
                    continue;
                }

                /*
                 * Le passager est verrouillé avant la mise à jour de son solde.
                 * Cela évite de perdre un remboursement ou un débit concurrent.
                 */
                $passenger = $this->em->getRepository(User::class)
                    ->createQueryBuilder('u')
                    ->where('u.id = :id')
                    ->setParameter('id', $passenger->getId())
                    ->getQuery()
                    ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                    ->getOneOrNullResult();

                if (!$passenger) {
                    throw new \RuntimeException('Passager introuvable.');
                }

                $booking->setStatus('CANCELLED');
                $passenger->setCredits($passenger->getCredits() + $price);

                $transaction = new CreditTransaction();
                $transaction->setAmount($price);
                $transaction->setTransactionType('BOOKING_REFUND');
                $transaction->setDescription(
                    sprintf(
                        'Remboursement : trajet #%d annulé par le chauffeur',
                        $carpool->getId()
                    )
                );
                $transaction->setCreatedAt(new \DateTimeImmutable());
                $transaction->setUser($passenger);
                $transaction->setCarpool($carpool);

                $this->em->persist($transaction);

                /*
                 * L'envoi est fait après flush/commit ci-dessous.
                 * Ici, on peut mémoriser les destinataires.
                 */
                $passengersToNotify[] = $passenger;
            }

            $carpool->setStatus('CANCELLED');

            /*
             * Le trajet n'est plus réservable ; remettre ce compteur à son
             * nombre initial rend la donnée cohérente avec toutes les
             * réservations désormais annulées.
             */
            $carpool->setRemainingSeatCount($carpool->getInitialSeatCount());

            $this->em->flush();
            $connection->commit();

            foreach ($passengersToNotify ?? [] as $passenger) {
                $this->sendCancellationEmail($passenger, $carpool);
            }
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private function sendCancellationEmail(User $passenger, Carpool $carpool): void
    {
        $email = (new Email())
            ->from('no-reply@ecoride.local')
            ->to((string) $passenger->getEmail())
            ->subject('Annulation de votre covoiturage EcoRide')
            ->text(sprintf(
                "Bonjour %s,\n\nLe covoiturage #%d (%s → %s), prévu le %s, a été annulé par son chauffeur.\n\nVos %d crédits ont été remboursés.\n\nL’équipe EcoRide",
                $passenger->getUsername(),
                $carpool->getId(),
                $carpool->getDepartureCity(),
                $carpool->getArrivalCity(),
                $carpool->getDepartureAt()->format('d/m/Y à H:i'),
                $carpool->getCreditCostPerPassenger()
            ));

        $this->mailer->send($email);
    }
}