<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Carpool;
use App\Entity\User;
use App\Repository\BookingRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class RideLifeCycleService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BookingRepository $bookingRepository,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function start(Carpool $carpool, User $driver): void
    {
        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $carpool = $this->lockCarpool($carpool->getId());

            if (!$carpool) {
                throw new \RuntimeException('Covoiturage introuvable.');
            }

            $this->assertDriver($carpool, $driver);

            if ($carpool->getStatus() !== 'PLANNED') {
                throw new \RuntimeException(
                    'Seul un covoiturage planifié peut être démarré.'
                );
            }

            $carpool->setStatus('STARTED');
            $carpool->setUpdatedAt(new \DateTimeImmutable());

            $this->em->flush();
            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public function complete(Carpool $carpool, User $driver): void
    {
        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $carpool = $this->lockCarpool($carpool->getId());

            if (!$carpool) {
                throw new \RuntimeException('Covoiturage introuvable.');
            }

            $this->assertDriver($carpool, $driver);

            if ($carpool->getStatus() !== 'STARTED') {
                throw new \RuntimeException(
                    'Seul un covoiturage démarré peut être clôturé.'
                );
            }

            $carpool->setStatus('COMPLETED');
            $carpool->setUpdatedAt(new \DateTimeImmutable());

            $bookings = $this->bookingRepository->findBy([
                'carpool' => $carpool,
                'status' => 'CONFIRMED',
            ]);

            $passengersToNotify = [];

            foreach ($bookings as $booking) {
                $passenger = $booking->getPassenger();

                if ($passenger) {
                    $passengersToNotify[] = $passenger;
                }
            }

            $this->em->flush();
            $connection->commit();

            foreach ($passengersToNotify as $passenger) {
                $this->sendCompletionEmail($passenger, $carpool);
            }
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private function lockCarpool(?int $id): ?Carpool
    {
        if (!$id) {
            return null;
        }

        return $this->em->getRepository(Carpool::class)
            ->createQueryBuilder('c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    private function assertDriver(Carpool $carpool, User $driver): void
    {
        if ($carpool->getDriver()?->getId() !== $driver->getId()) {
            throw new \RuntimeException(
                'Vous ne pouvez pas modifier un covoiturage qui ne vous appartient pas.'
            );
        }

        if (!$driver->isActive()) {
            throw new \RuntimeException(
                'Votre compte est désactivé.'
            );
        }

        if (!$driver->isDriver()) {
            throw new \RuntimeException(
                'Votre compte ne possède pas le statut chauffeur.'
            );
        }
    }

    private function sendCompletionEmail(User $passenger, Carpool $carpool): void
    {
        $email = (new Email())
            ->from('no-reply@ecoride.local')
            ->to((string) $passenger->getEmail())
            ->subject('Votre covoiturage EcoRide est terminé')
            ->text(sprintf(
                "Bonjour %s,\n\nLe covoiturage #%d (%s → %s) est terminé.\n\nConnectez-vous à votre espace EcoRide pour confirmer que le trajet s’est bien déroulé, laisser une note et un avis, ou signaler un incident.\n\nL’équipe EcoRide",
                $passenger->getUsername(),
                $carpool->getId(),
                $carpool->getDepartureCity(),
                $carpool->getArrivalCity()
            ));

        $this->mailer->send($email);
    }
}