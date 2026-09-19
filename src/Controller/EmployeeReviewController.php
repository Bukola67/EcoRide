<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYEE')]
#[Route('/employee/reviews', name: 'app_employee_review_')]
final class EmployeeReviewController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findBy(
            ['status' => 'PENDING'],
            ['createdAt' => 'ASC']
        );

        return $this->render('employee/reviews/index.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(Review $review, Request $request): Response
    {
        $this->moderate($review, $request, 'APPROVED');

        return $this->redirectToRoute('app_employee_review_index');
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(Review $review, Request $request): Response
    {
        $this->moderate($review, $request, 'REJECTED');

        return $this->redirectToRoute('app_employee_review_index');
    }

    private function moderate(
        Review $review,
        Request $request,
        string $status
    ): void {
        if (!$this->isCsrfTokenValid(
            'review-moderation-' . $review->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }

        if ($review->getStatus() !== 'PENDING') {
            $this->addFlash(
                'warning',
                'Cet avis a déjà été traité.'
            );

            return;
        }

        $review->setStatus($status);
        $review->setModeratedAt(new \DateTimeImmutable());
        $review->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        $this->addFlash(
            'success',
            $status === 'APPROVED'
                ? 'L’avis est désormais public.'
                : 'L’avis a été refusé.'
        );
    }
}