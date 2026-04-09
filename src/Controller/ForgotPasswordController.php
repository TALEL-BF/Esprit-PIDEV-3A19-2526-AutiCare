<?php
// src/Controller/ForgotPasswordController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function request(Request $request, UserRepository $userRepository, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $code = sprintf("%06d", random_int(0, 999999));
                $user->setResetCode($code);
                $user->setResetCodeExpiresAt(new \DateTime('+15 minutes'));
                $entityManager->flush();
                $request->getSession()->set('reset_email', $email);

                // Envoi de l'email
                $emailMessage = (new Email())
                    ->from('no-reply@auticare.com')
                    ->to($user->getEmail())
                    ->subject('Code de réinitialisation - AutiCare')
                    ->html($this->renderView('emails/reset_code.html.twig', [
                        'user' => $user,
                        'code' => $code
                    ]));

                $mailer->send($emailMessage);

                $this->addFlash('success', 'Un code de vérification a été envoyé à votre email.');
                return $this->redirectToRoute('app_verify_code');
            } else {
                $this->addFlash('error', 'Cet email n\'existe pas.');
            }
        }

        return $this->render('auth/forgot_password.html.twig');
    }

    #[Route('/verify-code', name: 'app_verify_code', methods: ['GET', 'POST'])]
    public function verifyCode(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $email = $request->getSession()->get('reset_email');
        
        if (!$email) {
            return $this->redirectToRoute('app_forgot_password');
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if ($request->isMethod('POST')) {
            $enteredCode = $request->request->get('code');

            if ($user && $user->getResetCode() === $enteredCode && $user->isResetCodeValid()) {
                $request->getSession()->set('reset_token', $enteredCode);
                $this->addFlash('success', 'Code vérifié. Veuillez entrer votre nouveau mot de passe.');
                return $this->redirectToRoute('app_reset_password');
            } else {
                $this->addFlash('error', 'Code invalide ou expiré.');
            }
        }

        return $this->render('auth/verify_code.html.twig');
    }

    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $email = $request->getSession()->get('reset_email');
        $code = $request->getSession()->get('reset_token');

        if (!$email || !$code) {
            $this->addFlash('error', 'Session expirée. Veuillez recommencer.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $user = $userRepository->findOneBy(['email' => $email, 'resetCode' => $code]);

        if (!$user || !$user->isResetCodeValid()) {
            $this->addFlash('error', 'Session expirée. Veuillez recommencer.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                return $this->redirectToRoute('app_reset_password');
            }

            if (!preg_match('/[A-Z]/', $newPassword)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins une majuscule.');
                return $this->redirectToRoute('app_reset_password');
            }

            if (!preg_match('/[0-9]/', $newPassword)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins un chiffre.');
                return $this->redirectToRoute('app_reset_password');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_reset_password');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $user->setResetCode(null);
            $user->setResetCodeExpiresAt(null);
            $entityManager->flush();

            $request->getSession()->remove('reset_email');
            $request->getSession()->remove('reset_token');

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_signin');
        }

        return $this->render('auth/reset_password.html.twig');
    }
}