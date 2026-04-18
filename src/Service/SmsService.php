<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SmsService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(TEXTLINK_API_KEY)%')] private readonly string $apiKey,
        #[Autowire('%env(default::TEXTLINK_BASE_URL)%')] private readonly string $baseUrl,
        #[Autowire('%env(default::TEXTLINK_DEFAULT_COUNTRY_CODE)%')] private readonly string $defaultCountryCode
    ) {
    }

    /**
     * Envoyer un SMS via TextLink API
     *
     * @param string $to Numéro au format international (sera nettoyé)
     * @param string $message Corps du message
     * @return bool Succès de l'opération
     */
    public function sendSms(string $to, string $message): bool
    {
        if (empty($this->apiKey)) {
            $this->logger->warning('SMS non envoyé : clé API TextLink non configurée.');
            return false;
        }

        $phone = $this->normalizePhoneNumber($to);
        if ($phone === '') {
            $this->logger->warning('SMS non envoyé : numéro de téléphone invalide.');
            return false;
        }

        try {
            $baseUrl = trim($this->baseUrl) !== '' ? $this->baseUrl : 'https://textlinksms.com';
            if (!str_starts_with($baseUrl, 'http')) {
                $baseUrl = 'https://' . $baseUrl;
            }
            $url = rtrim($baseUrl, '/') . '/api/send-sms';

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'phone_number' => $phone,
                    'text' => $message,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode >= 200 && $statusCode < 300 && ($content['ok'] ?? true)) {
                $this->logger->info(sprintf('SMS TextLink envoyé avec succès à %s. Réponse: %s', $to, json_encode($content)));
                return true;
            }

            $this->logger->error(sprintf('Échec envoi SMS TextLink à %s. Code: %d. Réponse: %s', $to, $statusCode, json_encode($content)));
            return false;

        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Erreur lors de l\'envoi SMS TextLink à %s : %s', $to, $e->getMessage()));
            return false;
        }
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $phone = trim($phoneNumber);
        if ($phone === '') {
            return '';
        }

        $phone = preg_replace('/[\s\-\.\(\)]/', '', $phone) ?? $phone;

        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        if (str_starts_with($phone, '+')) {
            $digits = '+' . preg_replace('/\D+/', '', substr($phone, 1));
            return strlen($digits) > 1 ? $digits : '';
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        $countryCode = preg_replace('/\D+/', '', $this->defaultCountryCode) ?? '';
        if ($countryCode !== '') {
            if (str_starts_with($digits, '0')) {
                $digits = ltrim($digits, '0');
            }

            if ($digits !== '' && !str_starts_with($digits, $countryCode)) {
                return '+' . $countryCode . $digits;
            }

            return '+' . $digits;
        }

        return '+' . $digits;
    }
}
