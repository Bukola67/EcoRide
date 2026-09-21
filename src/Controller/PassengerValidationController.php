<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\CreditTransaction;
use App\Service\IncidentService;
use App\Repository\BookingRepository;
use App\Enum\PostRideValidation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED')]
final class PassengerValidationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IncidentService $incidentService,
    ) {
    }

    #[Route(
        '/my-trips/taken/{id}/validate',
        name: 'app_passenger_validate',
        methods: ['GET', 'POST']
    )]
    public function validate(
        int $id,
        Request $request,
        BookingRepository $bookingRepository
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $booking = $bookingRepository->find($id);

        if (!$booking) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        if (
            !$booking->getPassenger()
            || $booking->getPassenger()->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez valider que vos propres trajets.'
            );
        }

        $carpool = $booking->getCarpool();

        if (!$carpool || $carpool->getStatus() !== 'COMPLETED') {
            throw $this->createAccessDeniedException(
                'Seul un trajet terminé peut être validé.'
            );
        }

        if ($booking->getPostRideValidation() !== PostRideValidation::Pending) {
            $this->addFlash(
                'info',
                'Vous avez déjà traité ce trajet.'
            );

            return $this->redirectToRoute('app_my_taken_carpools');
        }

        $outcome = $request->query->get('outcome');
        $form = $this->createFormBuilder()
            ->setAction(
                $this->generateUrl(
                    'app_passenger_validate',
                    ['id' => $booking->getId()]
                )
            )
            ->add('ok', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'Le trajet s’est-il bien passé ?',
                'choices' => [
                    'Oui' => 'yes',
                    'Non' => 'no',
                ],
                'data' => $outcome === 'incident' ? 'no' : 'yes',
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ])
            ->add('rating', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'Note (1 à 5)',
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                ],
            ])
            ->add('comment', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Commentaire / avis',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ])
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, [
                'label' => 'Valider',
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $isOk = $data['ok'] === 'yes';
            $rating = $data['rating'] ;
            $comment = trim($data['comment'] ?? '');

            if ($isOk) {
                if ($rating !== null && ($rating < 1 || $rating > 5)) {
                    $this->addFlash(
                        'error',
                        'La note doit être comprise entre 1 et 5.'
                    );

                    return $this->redirectToRoute(
                        'app_passenger_validate',
                        ['id' => $booking->getId()]
                    );
                }

                $booking->setPostRideValidation(PostRideValidation::Ok);

                $this->createReview($carpool, $user, $rating, $comment);

                if (!$booking->isDriverCredited()) {
                    $this->creditDriver($carpool);
                    $booking->setDriverCredited(true);
                }

                $this->addFlash(
                    'success',
                    'Merci pour votre validation. Le chauffeur a été crédité.'
                );
            } else {
                if (mb_strlen($comment) < 10) {
                    $this->addFlash(
                        'error',
                        'En cas de problème, le commentaire doit contenir au moins 10 caractères.'
                    );

                    return $this->redirectToRoute(
                        'app_passenger_validate',
                        ['id' => $booking->getId()]
                    );
                }

                $this->createIncident($booking, $comment);
                $booking->setPostRideValidation(PostRideValidation::Incident);

                $this->addFlash(
                    'warning',
                    'Votre signalement a été enregistré. Un employé vous contactera.'
                );
            }

            $this->em->flush();

            return $this->redirectToRoute('app_my_taken_carpools');
        }

        return $this->render('passenger/validation.html.twig', [
            'booking' => $booking,
            'form' => $form,
        ]);
    
    }
    private function createReview(
        \App\Entity\Carpool $carpool,
        \App\Entity\User $passenger,
        ?int $rating,
        string $comment 
    ): void {
        $review = new \App\Entity\Review();
        $review->setCarpool($carpool);
        $review->setPassenger($passenger);
        $review->setRating($rating);
        $review->setComment($comment);
        $review->setStatus('PENDING');
        $review->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($review);
    }

    private function createIncident(Booking $booking, string $comment): void
    {
        $this->incidentService->createFromBooking($booking, $comment);
    }

    private function creditDriver(\App\Entity\Carpool $carpool): void
    {
        $driver = $carpool->getDriver();

        if (!$driver) {
            throw new \RuntimeException(
                'Impossible de créditer le chauffeur : chauffeur introuvable.'
            );
        }

        $creditCostPerPassenger = $carpool->getCreditCostPerPassenger();

        if ($creditCostPerPassenger === null) {
            throw new \RuntimeException(
                'Impossible de créditer le chauffeur : coût du trajet absent.'
            );
        }

        $platformFee = 2;
        $amountForDriver = $creditCostPerPassenger - $platformFee;

        if ($amountForDriver < 0) {
            throw new \RuntimeException(
                'Le coût du trajet doit être supérieur ou égal à 2 crédits.'
            );
        }

        $driver->setCredits(
            ($driver->getCredits() ?? 0) + $amountForDriver
        );

        $platformTransaction = new CreditTransaction();

        $platformTransaction->setAmount($platformFee);
        $platformTransaction->setTransactionType('PLATFORM_FEE');
        $platformTransaction->setDescription(
            'Commission EcoRide pour le trajet #' . $carpool->getId()
        );
        $platformTransaction->setCreatedAt(new \DateTimeImmutable());
        $platformTransaction->setUser($driver);
        $platformTransaction->setCarpool($carpool);

        $this->em->persist($platformTransaction);

    }
}