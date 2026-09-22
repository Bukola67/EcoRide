<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleType;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VehicleController extends AbstractController
{
    #[Route('/vehicles', name: 'app_vehicle_index')]
    public function index(VehicleRepository $vehicleRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $vehicles = $vehicleRepository->findBy(['owner' => $user], ['id' => 'DESC']);

        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicles,
        ]);
    }

    #[Route('/vehicles/new', name: 'app_vehicle_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $vehicle = new Vehicle();
        $vehicle->setOwner($this->getUser());

        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule ajouté avec succès.');

            return $this->redirectToRoute('app_vehicle_index');
        }

        return $this->render('vehicle/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/vehicles/{id}/edit', name: 'app_vehicle_edit')]
    public function edit(
        int $id,
        Request $request,
        VehicleRepository $vehicleRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $vehicle = $vehicleRepository->find($id);

        if (!$vehicle) {
            throw $this->createNotFoundException('Véhicule introuvable.');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($vehicle->getOwner()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce véhicule.');
        }

        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Véhicule modifié avec succès.');

            return $this->redirectToRoute('app_vehicle_index');
        }

        return $this->render('vehicle/edit.html.twig', [
            'form' => $form,
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/vehicles/{id}/delete', name: 'app_vehicle_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        VehicleRepository $vehicleRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $vehicle = $vehicleRepository->find($id);

        if (!$vehicle) {
            throw $this->createNotFoundException('Véhicule introuvable.');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($vehicle->getOwner()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce véhicule.');
        }

        if ($this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            $em->remove($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule supprimé avec succès.');
        }

        return $this->redirectToRoute('app_vehicle_index');
    }
}