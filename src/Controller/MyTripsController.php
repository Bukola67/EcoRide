<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Booking;
use App\Entity\Carpool;
use App\Service\CancellationService;
use App\Service\RideLifeCycleService;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\BookingRepository;
use App\Repository\CarpoolRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MyTripsController extends AbstractController
{
    #[Route('/my-trips/driven', name: 'app_my_driven_carpools', methods: ['GET'])]
    public function driven(
        CarpoolRepository $carpoolRepository
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || !$user->isActive()) {
            throw $this->createAccessDeniedException(
                'Votre compte est suspendu.'
            );
        }

        $carpools = $carpoolRepository->findBy(
            ['driver' => $user],
            ['departureAt' => 'DESC']
        );

        return $this->render('my_trips/driven.html.twig', [
            'carpools' => $carpools,
        ]);
    }

    #[Route('/my-trips/participations', name: 'app_my_participations', methods: ['GET'])]
    public function participations(
        BookingRepository $bookingRepository
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || !$user->isActive()) {
            throw $this->createAccessDeniedException(
                'Votre compte est suspendu.'
            );
        }
      
        $bookings = $bookingRepository->findBy(
            ['passenger' => $user],
            ['id' => 'DESC']
        );

        return $this->render('my_trips/participations.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route(
    '/my-trips/participations/{id}/cancel',
    name: 'app_booking_cancel',
    methods: ['POST']
)]
    public function cancelParticipation(
        int $id,
        Request $request,
        BookingRepository $bookingRepository,
        CancellationService $cancellationService
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $booking = $bookingRepository->find($id);

        if (!$booking) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        if (!$this->isCsrfTokenValid(
            'cancel-booking' . $booking->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || !$user->isActive()) {
            throw $this->createAccessDeniedException(
                'Votre compte est suspendu.'
            );
        }


        try {
            $cancellationService->cancelByPassenger($booking, $user);

            $this->addFlash(
                'success',
                'Votre participation a été annulée et vos crédits ont été remboursés.'
            );
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_my_participations');
    }

    #[Route(
        '/my-trips/driven/{id}/cancel',
        name: 'app_carpool_cancel',
        methods: ['POST']
    )]
    public function cancelDrivenCarpool(
        int $id,
        Request $request,
        CarpoolRepository $carpoolRepository,
        CancellationService $cancellationService
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $carpool = $carpoolRepository->find($id);

        if (!$carpool) {
            throw $this->createNotFoundException('Covoiturage introuvable.');
        }

        if (!$this->isCsrfTokenValid(
            'cancel-carpool' . $carpool->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || !$user->isActive()) {
            throw $this->createAccessDeniedException(
                'Votre compte est suspendu.'
            );
        }

        try {
            $cancellationService->cancelByDriver($carpool, $user);

            $this->addFlash(
                'success',
                'Le covoiturage a été annulé. Les passagers ont été remboursés et notifiés.'
            );
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_my_driven_carpools');
    }

    #[Route(
    '/my-trips/driven/{id}/start',
    name: 'app_carpool_start',
    methods: ['POST']
)]
public function startDrivenCarpool(
    int $id,
    Request $request,
    CarpoolRepository $carpoolRepository,
    RideLifeCycleService $rideLifeCycleService
): Response {
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

    $carpool = $carpoolRepository->find($id);

    if (!$carpool) {
        throw $this->createNotFoundException('Covoiturage introuvable.');
    }

    if (!$this->isCsrfTokenValid(
        'start-carpool' . $carpool->getId(),
        (string) $request->request->get('_token')
    )) {
        throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
    }

    /** @var User $user */
    $user = $this->getUser();

     if (!$user instanceof \App\Entity\User || !$user->isActive()) {
        throw $this->createAccessDeniedException(
                'Votre compte est suspendu.'
        );
     }


    try {
        $rideLifeCycleService->start($carpool, $user);
        $this->addFlash('success', 'Le covoiturage a démarré.');
    } catch (\RuntimeException $exception) {
        $this->addFlash('error', $exception->getMessage());
    }

    return $this->redirectToRoute('app_my_driven_carpools');
}

    #[Route(
        '/my-trips/driven/{id}/complete',
        name: 'app_carpool_complete',
        methods: ['POST']
    )]
    public function completeDrivenCarpool(
        int $id,
        Request $request,
        CarpoolRepository $carpoolRepository,
        RideLifeCycleService $rideLifeCycleService
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $carpool = $carpoolRepository->find($id);

        if (!$carpool) {
            throw $this->createNotFoundException('Covoiturage introuvable.');
        }

        if (!$this->isCsrfTokenValid(
            'complete-carpool' . $carpool->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User || !$user->isActive()) {
            throw $this->createAccessDeniedException(
                'Votre compte est suspendu.'
            );
        }

        try {
            $rideLifeCycleService->complete($carpool, $user);
            $this->addFlash(
                'success',
                'Le trajet est terminé. Les participants ont été invités à le confirmer.'
            );
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_my_driven_carpools');
    }

}