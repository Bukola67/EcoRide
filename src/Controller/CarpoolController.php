<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CarpoolController extends AbstractController
{
    #[Route('/carpools', name: 'app_carpool_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('carpool/index.html.twig', [
            'carpools' => [],
            'hasSearched' => false,
        ]);
    }
}