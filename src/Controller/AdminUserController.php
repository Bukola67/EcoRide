<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users', name: 'app_admin_user_')]
final class AdminUserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'users' => $userRepository->findAllForAdministration(),
        ]);
    }

    #[Route('/{id}/toggle-active', name: 'toggle_active', methods: ['POST'])]
    public function toggleActive(
        User $user,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'toggle-active-' . $user->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }

        /** @var User $admin */
        $admin = $this->getUser();

        if ($user->getId() === $admin->getId()) {
            $this->addFlash(
                'error',
                'Vous ne pouvez pas suspendre votre propre compte.'
            );

            return $this->redirectToRoute('app_admin_user_index');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $this->addFlash(
                'error',
                'Un compte administrateur ne peut pas être suspendu depuis cette interface.'
            );

            return $this->redirectToRoute('app_admin_user_index');
        }

        $user->setIsActive(!(bool) $user->isActive());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        $this->addFlash(
            'success',
            $user->isActive()
                ? sprintf('Le compte « %s » a été réactivé.', $user->getUsername())
                : sprintf('Le compte « %s » a été suspendu.', $user->getUsername())
        );

        return $this->redirectToRoute('app_admin_user_index');
    }
}