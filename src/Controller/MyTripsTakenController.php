<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED')]
final class MyTripsTakenController extends AbstractController
{
    #[Route(
        '/my-trips/taken',
        name: 'app_my_taken_carpools',
        methods: ['GET']
    )]
    public function index(BookingRepository $bookingRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $bookings = $bookingRepository->findBy(
            ['passenger' => $user],
            ['id' => 'DESC']
        );

        return $this->render('my_trips/taken.html.twig', [
            'bookings' => $bookings,
        ]);
    }
}