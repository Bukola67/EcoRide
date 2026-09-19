<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CarpoolRepository;
use App\Repository\ReviewRepository;
use App\Repository\BookingRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\BookingService;
use App\Entity\Carpool;
use App\Entity\User;
use App\Form\CarpoolType;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;

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

    #[Route('/carpools/new', name: 'app_carpool_new')]
        public function new(
            Request $request,
            VehicleRepository $vehicleRepository,
            EntityManagerInterface $em
        ): Response {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

            /** @var User $user */
            $user = $this->getUser();

            if (!$user->isActive()) {
                throw $this->createAccessDeniedException(
                    'Votre compte est désactivé.'
                );
            }

            if (!$user->isDriver()) {
                $this->addFlash(
                    'error',
                    'Vous devez activer le statut chauffeur depuis votre profil avant de publier un trajet.'
                );

                return $this->redirectToRoute('app_profile_edit');
            }

            $vehicles = $vehicleRepository->findBy([
                'owner' => $user,
            ], [
                'id' => 'ASC',
            ]);

            if ($vehicles === []) {
                $this->addFlash(
                    'error',
                    'Ajoutez d’abord un véhicule avant de publier un covoiturage.'
                );

                return $this->redirectToRoute('app_vehicle_new');
            }

            $carpool = new Carpool();

            $form = $this->createForm(CarpoolType::class, $carpool, [
                'vehicles' => $vehicles,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $selectedVehicle = $carpool->getVehicle();

                /*
                * Contrôle de sécurité serveur :
                * même si une personne modifie les données HTTP,
                * elle ne peut pas utiliser le véhicule d’un autre compte.
                */
                if (!$selectedVehicle || $selectedVehicle->getOwner()?->getId() !== $user->getId()) {
                    throw $this->createAccessDeniedException(
                        'Le véhicule sélectionné ne vous appartient pas.'
                    );
                }

                $departureAt = $carpool->getDepartureAt();
                $arrivalAt = $carpool->getArrivalAt();
                $initialSeatCount = $carpool->getInitialSeatCount();

                if (!$departureAt || $departureAt <= new \DateTimeImmutable()) {
                    $form->addError(
                        new \Symfony\Component\Form\FormError(
                            'La date de départ doit être dans le futur.'
                        )
                    );
                }

                if (!$arrivalAt || !$departureAt || $arrivalAt <= $departureAt) {
                    $form->addError(
                        new \Symfony\Component\Form\FormError(
                            'L’arrivée doit être postérieure au départ.'
                        )
                    );
                }

                if (!$initialSeatCount || $initialSeatCount < 1) {
                    $form->addError(
                        new \Symfony\Component\Form\FormError(
                            'Le trajet doit proposer au moins une place.'
                        )
                    );
                }

                if ($form->isValid()) {
                    $carpool->setDriver($user);
                    $carpool->setRemainingSeatCount($initialSeatCount);
                    $carpool->setStatus('PLANNED');
                    $carpool->setCreatedAt(new \DateTimeImmutable());

                    $em->persist($carpool);
                    $em->flush();

                    $this->addFlash('success', 'Votre covoiturage a été publié.');

                    return $this->redirectToRoute('app_my_driven_carpools');
                }
            }

            return $this->render('carpool/new.html.twig', [
                'form' => $form,
                'vehicles' => $vehicles,
            ]);
        }

        #[Route('/carpools/{id}', name: 'app_carpool_detail', methods: ['GET'])]
        public function detail(
            int $id,
            CarpoolRepository $carpoolRepository,
            ReviewRepository $reviewRepository,
            BookingRepository $bookingRepository
        ): Response {
            $carpool = $carpoolRepository->find($id);

            if (!$carpool) {
                throw $this->createNotFoundException('Covoiturage introuvable.');
            }

            $reviews = $reviewRepository->findBy(
                [
                    'carpool' => $carpool,
                    'status' => 'APPROVED',
                ],
                [
                    'createdAt' => 'DESC',
                ]
            );

            /*
            * null : aucune réservation antérieure pour cet utilisateur et ce trajet.
            * CONFIRMED : participation active.
            * CANCELLED : ancienne participation annulée, potentiellement réactivable.
            */
            $existingBooking = null;

            if ($this->getUser() instanceof User) {
                /** @var User $user */
                $user = $this->getUser();

                $existingBooking = $bookingRepository->findOneBy([
                    'passenger' => $user,
                    'carpool' => $carpool,
                ]);
            }

            return $this->render('carpool/detail.html.twig', [
                'carpool' => $carpool,
                'reviews' => $reviews,
                'existingBooking' => $existingBooking,
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

                if (!$this->isCsrfTokenValid(
                    'booking' . $carpool->getId(),
                    (string) $request->request->get('_token')
                )) {
                    throw $this->createAccessDeniedException(
                        'Jeton de sécurité invalide. Veuillez réessayer.'
                    );
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