<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;

final class IncidentService
{
    private Collection $incidents;

    public function __construct(
        Client $client,
        string $databaseName,
    ) {
        $this->incidents = $client
            ->selectDatabase($databaseName)
            ->selectCollection('incidents');
    }

    public function createFromBooking(Booking $booking, string $comment): string
    {
        $carpool = $booking->getCarpool();
        $passenger = $booking->getPassenger();
        $driver = $carpool?->getDriver();

        if (!$carpool || !$passenger || !$driver) {
            throw new \RuntimeException(
                'Impossible de créer un incident : données de réservation incomplètes.'
            );
        }

        $departureAt = $carpool->getDepartureAt();
        $arrivalAt = $carpool->getArrivalAt();

        if (!$departureAt || !$arrivalAt) {
            throw new \RuntimeException(
                'Impossible de créer un incident : dates du trajet absentes.'
            );
        }

        $comment = trim($comment);

        if (mb_strlen($comment) < 10) {
            throw new \RuntimeException(
                'Le commentaire de l’incident doit contenir au moins 10 caractères.'
            );
        }

        $result = $this->incidents->insertOne([
            'carpool_id' => $carpool->getId(),
            'booking_id' => $booking->getId(),
            'passenger_id' => $passenger->getId(),
            'driver_id' => $driver->getId(),

            'passenger_username' => $passenger->getUsername(),
            'passenger_email' => (string) $passenger->getEmail(),
            'driver_username' => $driver->getUsername(),
            'driver_email' => (string) $driver->getEmail(),

            'departure' => sprintf(
                '%s — %s',
                $carpool->getDepartureCity(),
                $carpool->getDepartureAddress()
            ),

            'arrival' => sprintf(
                '%s — %s',
                $carpool->getArrivalCity(),
                $carpool->getArrivalAddress()
            ),

            'departure_at' => $this->toUtcDateTime($departureAt),
            'arrival_at' => $this->toUtcDateTime($arrivalAt),

            'comment' => $comment,
            'status' => 'OPEN',
            'created_at' => new UTCDateTime(),
            'updated_at' => null,
        ]);

        return (string) $result->getInsertedId();
    }

    public function findAll(): array
    {
        return $this->incidents
            ->find([], [
                'sort' => [
                    'created_at' => -1,
                ],
            ])
            ->toArray();
    }

    public function updateStatus(
        string $incidentId,
        string $newStatus
    ): void {
        $allowedTransitions = [
            'OPEN' => ['IN_PROGRESS'],
            'IN_PROGRESS' => ['RESOLVED'],
            'RESOLVED' => [],
        ];

        if (!isset($allowedTransitions[$newStatus])) {
            throw new \InvalidArgumentException(
                'Le statut demandé est invalide.'
            );
        }

        try {
            $objectId = new ObjectId($incidentId);
        } catch (\Throwable) {
            throw new \InvalidArgumentException(
                'Identifiant d’incident invalide.'
            );
        }

        $incident = $this->incidents->findOne([
            '_id' => $objectId,
        ]);

        if (!$incident) {
            throw new \RuntimeException(
                'Incident introuvable.'
            );
        }

        $currentStatus = (string) ($incident['status'] ?? '');

        if (
            !in_array(
                $newStatus,
                $allowedTransitions[$currentStatus] ?? [],
                true
            )
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Transition impossible : %s → %s.',
                    $currentStatus ?: 'inconnu',
                    $newStatus
                )
            );
        }

        $this->incidents->updateOne(
            ['_id' => $objectId],
            [
                '$set' => [
                    'status' => $newStatus,
                    'updated_at' => new UTCDateTime(),
                ],
            ]
        );
    }

    private function toUtcDateTime(\DateTimeInterface $date): UTCDateTime
    {
        return new UTCDateTime(
            $date->getTimestamp() * 1000
        );
    }
}