<?php

namespace App\Controller;

use App\Repository\CarpoolRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
}