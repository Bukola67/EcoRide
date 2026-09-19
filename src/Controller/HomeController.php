<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(BookingRepository $bookingRepository): Response
    {
        $pendingValidationBooking = null;

        if ($this->getUser() instanceof User) {
            $pendingValidationBooking = $bookingRepository
                ->findPendingPostRideValidationForPassenger($this->getUser());
        }

        return $this->render('home/index.html.twig', [
            'minDate' => (new \DateTimeImmutable())->format('Y-m-d'),
            'pendingValidationBooking' => $pendingValidationBooking,
        ]);
    }
}