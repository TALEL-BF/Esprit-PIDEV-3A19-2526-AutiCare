<?php
// src/Command/TestEmailCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TestEmailCommand extends Command
{
    protected static $defaultName = 'app:test-email';
    protected static $defaultDescription = 'Test d’envoi d’email OTP';

    public function __construct(private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('molkaraissi11@gmail.com')
            ->to('molkaraissi11@gmail.com')
            ->subject('Test OTP AutiCare')
            ->html('<h1>Test Email</h1><p>Code OTP: <strong>123456</strong></p>');

        try {
            $this->mailer->send($email);
            $output->writeln('✅ Email envoyé avec succès !');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('❌ Erreur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}