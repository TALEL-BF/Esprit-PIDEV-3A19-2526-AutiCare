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
                $otp = sprintf("%06d", random_int(0, 999999));
                $user->setOtpCode($otp);
                $user->setOtpExpiry(new \DateTime('+10 minutes'));
                $entityManager->flush();
                $request->getSession()->set('reset_email', $email);

                $emailMessage = (new Email())
                    ->from('molkaraissi11@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Code OTP - AutiCare')
                    ->html("<h1>Code OTP</h1><p>Votre code est: <strong>" . $otp . "</strong></p><p>Valable 10 minutes.</p>");

                $mailer->send($emailMessage);
                $this->addFlash('success', '✅ Code OTP envoyé par email.');
                return $this->redirectToRoute('app_verify_otp');
            } else {
                $this->addFlash('error', 'Cet email n\'existe pas.');
            }
        }

        return $this->render('auth/forgot_password.html.twig');
    }

    #[Route('/verify-otp', name: 'app_verify_otp', methods: ['GET', 'POST'])]
    public function verifyOtp(Request $request, UserRepository $userRepository): Response
    {
        $email = $request->getSession()->get('reset_email');
        
        if (!$email) {
            return $this->redirectToRoute('app_forgot_password');
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if ($request->isMethod('POST')) {
            $enteredOtp = $request->request->get('otp');

            if ($user && $user->getOtpCode() === $enteredOtp && $user->getOtpExpiry() > new \DateTime()) {
                $request->getSession()->set('reset_token', $enteredOtp);
                $this->addFlash('success', '✅ Code vérifié.');
                return $this->redirectToRoute('app_new_password');
            } else {
                $this->addFlash('error', '❌ Code invalide ou expiré.');
            }
        }

        return $this->render('auth/verify_otp.html.twig');
    }

    #[Route('/new-password', name: 'app_new_password', methods: ['GET', 'POST'])]
    public function newPassword(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $email = $request->getSession()->get('reset_email');
        $otp = $request->getSession()->get('reset_token');

        if (!$email || !$otp) {
            return $this->redirectToRoute('app_forgot_password');
        }

        $user = $userRepository->findOneBy(['email' => $email, 'otpCode' => $otp]);

        if (!$user || !$user->getOtpExpiry() || $user->getOtpExpiry() <= new \DateTime()) {
            $this->addFlash('error', 'Session expirée. Veuillez recommencer.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                return $this->redirectToRoute('app_new_password');
            }

            if (!preg_match('/[A-Z]/', $newPassword)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins une majuscule.');
                return $this->redirectToRoute('app_new_password');
            }

            if (!preg_match('/[0-9]/', $newPassword)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins un chiffre.');
                return $this->redirectToRoute('app_new_password');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_new_password');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $user->setOtpCode(null);
            $user->setOtpExpiry(null);
            $entityManager->flush();

            $request->getSession()->remove('reset_email');
            $request->getSession()->remove('reset_token');

            $this->addFlash('success', '✅ Mot de passe réinitialisé avec succès.');
            return $this->redirectToRoute('app_signin');
        }

        return $this->render('auth/new_password.html.twig');
    }
}