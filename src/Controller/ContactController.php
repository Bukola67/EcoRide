<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name'));
            $email = trim((string) $request->request->get('email'));
            $message = trim((string) $request->request->get('message'));

            $mail = (new Email())
                ->from($_ENV['MAILER_FROM'])
                ->to('ecoride.studi.project@gmail.com')
                ->replyTo($email)
                ->subject('Nouveau message de contact EcoRide')
                ->text("Nom : $name\nE-mail : $email\n\nMessage :\n$message");

            $mailer->send($mail);

            $this->addFlash('success', 'Votre message a bien été envoyé.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig');
    }
}