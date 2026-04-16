<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZoomService
{
    private const TOKEN_CACHE_KEY = 'zoom.s2s.access_token';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        #[Autowire('%env(ZOOM_ACCOUNT_ID)%')] private readonly string $accountId,
        #[Autowire('%env(ZOOM_CLIENT_ID)%')] private readonly string $clientId,
        #[Autowire('%env(ZOOM_CLIENT_SECRET)%')] private readonly string $clientSecret,
        #[Autowire('%env(ZOOM_USER_ID)%')] private readonly string $zoomUserId,
        #[Autowire('%env(APP_TIMEZONE)%')] private readonly string $timezone,
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public function createMeeting(\DateTimeInterface $date, string $title, int $duration = 60): array
    {
        try {
            $token = $this->getAccessToken();
            $tunisDate = \DateTimeImmutable::createFromInterface($date)->setTimezone(new \DateTimeZone($this->timezone));

            $response = $this->httpClient->request('POST', sprintf('https://api.zoom.us/v2/users/%s/meetings', rawurlencode($this->zoomUserId)), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'topic' => $title,
                    'type' => 2,
                    'start_time' => $tunisDate->format('Y-m-d\TH:i:s'),
                    'duration' => max(1, $duration),
                    'timezone' => $this->timezone,
                    'settings' => [
                        'join_before_host' => false,
                        'waiting_room' => true,
                    ],
                ],
            ]);

            $data = $response->toArray(false);

            if (($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) || empty($data['join_url'])) {
                $reason = (string) ($data['reason'] ?? $data['message'] ?? $data['error'] ?? 'unknown_error');
                throw new \RuntimeException(sprintf('Zoom meeting creation failed: %s', $reason));
            }

            return [
                'id' => $data['id'] ?? null,
                'join_url' => $data['join_url'],
                'start_url' => $data['start_url'] ?? null,
                'password' => $data['password'] ?? null,
                'raw' => $data,
            ];
        } catch (ExceptionInterface $e) {
            throw new \RuntimeException('Unable to communicate with Zoom API.', 0, $e);
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function getAccessToken(): string
    {
        if ($this->accountId === '' || $this->clientId === '' || $this->clientSecret === '') {
            throw new \RuntimeException('Zoom configuration is incomplete. Please set ZOOM_ACCOUNT_ID, ZOOM_CLIENT_ID and ZOOM_CLIENT_SECRET.');
        }

        $accessToken = $this->cache->get(self::TOKEN_CACHE_KEY, function (ItemInterface $item): string {
            try {
                $response = $this->httpClient->request('POST', 'https://zoom.us/oauth/token', [
                    'query' => [
                        'grant_type' => 'account_credentials',
                        'account_id' => $this->accountId,
                    ],
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                    ],
                ]);

                $data = $response->toArray(false);
                $statusCode = $response->getStatusCode();

                if ($statusCode < 200 || $statusCode >= 300 || empty($data['access_token'])) {
                    $reason = (string) ($data['reason'] ?? $data['error_description'] ?? $data['error'] ?? 'unknown_error');
                    throw new \RuntimeException(sprintf('Zoom OAuth token retrieval failed: %s', $reason));
                }

                $expiresIn = max(60, ((int) ($data['expires_in'] ?? 3600)) - 60);
                $item->expiresAfter($expiresIn);

                return (string) $data['access_token'];
            } catch (ExceptionInterface $e) {
                throw new \RuntimeException('Unable to retrieve Zoom OAuth token.', 0, $e);
            }
        });

        if ($accessToken === '') {
            throw new \RuntimeException('Zoom OAuth token is empty.');
        }

        return $accessToken;
    }
}
