<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\IncidentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYEE')]
#[Route('/employee/incidents', name: 'app_employee_incident_')]
final class EmployeeIncidentController extends AbstractController
{

#[Route('', name: 'index', methods: ['GET'])]
    public function index(IncidentService $incidentService): Response
    {
        return $this->render('employee/incidents/index.html.twig', [
            'incidents' => $incidentService->findAll(),
        ]);
    }

#[Route('/{id}/status', name: 'update_status', methods: ['POST'])]
public function updateStatus(
    string $id,
    Request $request,
    IncidentService $incidentService,
): Response {
    if (!$this->isCsrfTokenValid(
        'incident-status-' . $id,
        (string) $request->request->get('_token')
    )) {
        throw $this->createAccessDeniedException(
            'Jeton de sécurité invalide.'
        );
    }

    try {
        $incidentService->updateStatus(
            $id,
            (string) $request->request->get('status')
        );

        $this->addFlash(
            'success',
            'Le statut de l’incident a été mis à jour.'
        );
    } catch (\InvalidArgumentException | \RuntimeException $exception) {
        $this->addFlash('error', $exception->getMessage());
    }

    return $this->redirectToRoute('app_employee_incident_index');
}
}