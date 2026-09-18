<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CarpoolRepository;
use App\Repository\ReviewRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\BookingService;

final class CarpoolController extends AbstractController
{
    #[Route('/carpools', name: 'app_carpool_index', methods: ['GET'])]
    public function index(
        Request $request,
        CarpoolRepository $carpoolRepository
    ): Response {
        $departure = trim((string) $request->query->get('departure', ''));
        $arrival = trim((string) $request->query->get('arrival', ''));
        $dateInput = (string) $request->query->get('date', '');

        $hasSearched = $departure !== '' && $arrival !== '' && $dateInput !== '';

        $carpools = [];
        $alternativeCarpool = null;
        $searchDate = null;
        $searchError = null;

        if ($hasSearched) {
            try {
                $searchDate = new \DateTimeImmutable($dateInput);

                $carpools = $carpoolRepository->findAvailableForSearch(
                    $departure,
                    $arrival,
                    $searchDate
                );

                if ($carpools === []) {
                    $alternativeCarpool = $carpoolRepository->findNextAvailableForRoute(
                        $departure,
                        $arrival,
                        $searchDate
                    );
                }
            } catch (\Exception) {
                $searchError = 'La date saisie est invalide.';
            }
        }

        return $this->render('carpool/index.html.twig', [
            'carpools' => $carpools,
            'hasSearched' => $hasSearched,
            'departure' => $departure,
            'arrival' => $arrival,
            'searchDate' => $searchDate,
            'alternativeCarpool' => $alternativeCarpool,
            'searchError' => $searchError,
            'minDate' => (new \DateTimeImmutable())->format('Y-m-d'),
        ]);
    }

    #[Route('/carpools/{id}', name: 'app_carpool_detail')]
    public function detail(
        int $id,
        CarpoolRepository $carpoolRepository,
        ReviewRepository $reviewRepository
    ): Response {
        $carpool = $carpoolRepository->find($id);

        if (!$carpool) {
            throw $this->createNotFoundException('Covoiturage introuvable.');
        }

        $reviews = $reviewRepository->findBy([
            'carpool' => $carpool,
            'status' => 'APPROVED',
        ]);

        return $this->render('carpool/detail.html.twig', [
            'carpool' => $carpool,
            'reviews' => $reviews,
        ]);
    }

        #[Route('/carpools/{id}/book', name: 'app_carpool_book', methods: ['POST'])]
        public function book(
            int $id,
            Request $request,
            CarpoolRepository $carpoolRepository,
            BookingService $bookingService
        ): Response {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

            $carpool = $carpoolRepository->find($id);

            if (!$carpool) {
                throw $this->createNotFoundException('Covoiturage introuvable.');
            }

            /** @var \App\Entity\User $user */
            $user = $this->getUser();

            try {
                $bookingService->book($carpool, $user);

                $this->addFlash('success', 'Votre réservation a été confirmée.');
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('app_carpool_detail', ['id' => $id]);
        }

            #[Route('/carpools/{id}/booking-preview', name: 'app_carpool_booking_preview', methods: ['GET'])]
            public function bookingPreview(
                int $id,
                Request $request,
                CarpoolRepository $carpoolRepository
            ): JsonResponse {
                $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

                $carpool = $carpoolRepository->find($id);

                if (!$carpool) {
                    return $this->json([
                        'ok' => false,
                        'error' => 'Covoiturage introuvable.',
                    ], 404);
                }

                /** @var \App\Entity\User $user */
                $user = $this->getUser();

                $price = $carpool->getCreditCostPerPassenger();
                $creditsAfter = $user->getCredits() - $price;

                if ($creditsAfter < 0) {
                return $this->json([
                'ok' => false,
                'error' => 'Crédits insuffisants pour ce trajet.',
            ], 400);
                }

                return $this->json([
                    'ok' => true,
                    'price' => $price,
                    'creditsBefore' => $user->getCredits(),
                    'creditsAfter' => $creditsAfter,
                    'fromCity' => $carpool->getDepartureCity(),
                    'toCity' => $carpool->getArrivalCity(),
                    'departureAt' => $carpool->getDepartureAt()->format('d/m/Y à H:i'),
                ]);
            }
}