<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\EmployeeCreateType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/employees', name: 'app_admin_employee_')]
final class AdminEmployeeController extends AbstractController
{
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $employee = new User();

        $form = $this->createForm(EmployeeCreateType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();

            /*
             * Sécurité importante :
             * le rôle est imposé côté serveur.
             * Aucun champ HTML ne permet de demander ROLE_ADMIN.
             */
            $employee->setRoles(['ROLE_EMPLOYEE']);
            $employee->setPassword(
                $passwordHasher->hashPassword($employee, $plainPassword)
            );

            $employee->setIsActive(true);
            $employee->setIsPassenger(false);
            $employee->setIsDriver(false);
            $employee->setCredits(0);
            $employee->setCreatedAt(new \DateTimeImmutable());

            $em->persist($employee);
            $em->flush();

            $this->addFlash(
                'success',
                sprintf(
                    'Le compte employé « %s » a été créé.',
                    $employee->getUsername()
                )
            );

            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/employees/new.html.twig', [
            'form' => $form,
        ]);
    }
}