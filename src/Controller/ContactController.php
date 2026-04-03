<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer = null): Response
    {
        $success = false;
        
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');
            
            // Validation basique
            if ($name && $email && $subject && $message) {
                // Envoi d'email (optionnel)
                if ($mailer) {
                    $emailMessage = (new Email())
                        ->from($email)
                        ->to('contact@auticare.tn')
                        ->subject('Formulaire de contact: ' . $subject)
                        ->html("<p><strong>Nom:</strong> $name</p>
                                <p><strong>Email:</strong> $email</p>
                                <p><strong>Sujet:</strong> $subject</p>
                                <p><strong>Message:</strong><br>$message</p>");
                    
                    try {
                        $mailer->send($emailMessage);
                    } catch (\Exception $e) {
                        // Log l'erreur mais continue
                    }
                }
                
                $success = true;
                
                // Ajouter un message flash
                $this->addFlash('success', 'Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.');
            } else {
                $this->addFlash('error', 'Veuillez remplir tous les champs du formulaire.');
            }
        }
        
        // Informations de contact
        $contactInfo = [
            'address' => 'Tunis, Tunisie',
            'phone' => '(+216) 71 000 000',
            'email' => 'contact@auticare.tn',
            'hours' => 'Lun - Ven: 9h00 - 18h00'
        ];
        
        return $this->render('front/contact/index.html.twig', [
            'contactInfo' => $contactInfo,
            'success' => $success
        ]);
    }
}