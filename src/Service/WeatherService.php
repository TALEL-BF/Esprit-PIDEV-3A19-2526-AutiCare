<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WeatherService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $apiKey
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
    }

    /**
     * Récupère la météo pour une ville
     */
    public function getWeather(string $city): array
    {
        try {
            // Appel à l'API OpenWeather
            $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',  // Celsius
                    'lang' => 'fr'        // Français
                ]
            ]);
            
            $data = $response->toArray();
            
            return [
                'success' => true,
                'temperature' => round($data['main']['temp']),
                'feels_like' => round($data['main']['feels_like']),
                'humidity' => $data['main']['humidity'],
                'wind_speed' => round($data['wind']['speed']),
                'condition' => $data['weather'][0]['description'],
                'icon' => $this->getWeatherIcon($data['weather'][0]['icon']),
                'city' => $data['name']
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('OpenWeather API error: ' . $e->getMessage());
            return $this->getFallbackWeather();
        }
    }
    
    /**
     * Récupère les prévisions pour une ville (optionnel)
     */
    public function getForecast(string $city, int $days = 5): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/forecast', [
                'query' => [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                    'cnt' => $days * 8  // 8 prévisions par jour (toutes les 3h)
                ]
            ]);
            
            return $response->toArray();
            
        } catch (\Exception $e) {
            $this->logger->error('OpenWeather forecast error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Icône météo selon le code OpenWeather
     */
    private function getWeatherIcon(string $iconCode): string
    {
        $icons = [
            '01d' => '☀️', '01n' => '🌙',
            '02d' => '🌤️', '02n' => '☁️',
            '03d' => '⛅', '03n' => '☁️',
            '04d' => '☁️', '04n' => '☁️',
            '09d' => '🌧️', '09n' => '🌧️',
            '10d' => '🌦️', '10n' => '🌧️',
            '11d' => '⛈️', '11n' => '⛈️',
            '13d' => '❄️', '13n' => '❄️',
            '50d' => '🌫️', '50n' => '🌫️'
        ];
        
        return $icons[$iconCode] ?? '🌡️';
    }
    
    /**
     * Météo par défaut (si API indisponible)
     */
    private function getFallbackWeather(): array
    {
        return [
            'success' => false,
            'temperature' => 22,
            'feels_like' => 22,
            'humidity' => 65,
            'wind_speed' => 10,
            'condition' => 'Ensoleillé',
            'icon' => '☀️',
            'city' => 'Ville non trouvée'
        ];
    }
}