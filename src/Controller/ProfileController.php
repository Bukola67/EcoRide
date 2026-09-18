<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly string $profilePicturesDir,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // S’assurer qu’au moins un rôle est coché
            if (!$user->isDriver() && !$user->isPassenger()) {
                $user->setIsPassenger(true);
            }

            $user->setUpdatedAt(new \DateTimeImmutable());

            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/profile/upload-picture', name: 'app_profile_upload_picture', methods: ['POST'])]
    public function uploadPicture(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /** @var User $user */
        $user = $this->getUser();

        // CSRF
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('upload-picture', $token)) {
            return new JsonResponse(['error' => 'Token CSRF invalide.'], 403);
        }

        /** @var UploadedFile|null $photo */
        $photo = $request->files->get('profilePicture');

        if (!$photo) {
            return new JsonResponse(['error' => 'Aucun fichier reçu.'], 400);
        }

        // Validation manuelle
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($photo->getMimeType(), $allowedMimeTypes, true)) {
            return new JsonResponse(
                ['error' => 'Format non autorisé (JPEG, PNG, WebP uniquement).'],
                422
            );
        }

        if ($photo->getSize() > 2 * 1024 * 1024) {
            return new JsonResponse(
                ['error' => 'Fichier trop volumineux (2 Mo max).'],
                422
            );
        }

        // Suppression de l’ancienne photo
        if ($user->getProfilePicture()) {
            $oldPath = $this->profilePicturesDir . '/' . $user->getProfilePicture();
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $newFilename = $this->uploadProfilePicture($photo);

        $oldFilename = $user->getProfilePicture();

        $user->setProfilePicture($newFilename);
        $user->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        if ($oldFilename) {
            $oldPath = $this->profilePicturesDir . '/' . $oldFilename;

            if (is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        return new JsonResponse([
            'success' => true,
            'filename' => $newFilename,
        ]);
    }

    private function uploadProfilePicture(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $file->guessExtension();

        $file->move($this->profilePicturesDir, $newFilename);

        return $newFilename;
    }
}