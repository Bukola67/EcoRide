<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CarpoolRepository;
use App\Repository\CreditTransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin', name: 'app_admin_')]
final class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(
        CarpoolRepository $carpoolRepository,
        CreditTransactionRepository $creditTransactionRepository,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'completedCarpoolsByDay' => $carpoolRepository->countCompletedByDay(),
            'platformFeesByDay' => $creditTransactionRepository->platformFeesByDay(),
            'platformFeesTotal' => $creditTransactionRepository->platformFeesTotal(),
        ]);
    }
}