<?php

namespace App\Controller\event;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MapController extends AbstractController
{
    private HttpClientInterface $httpClient;
    
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }
    
    #[Route('/api/map/events', name: 'api_map_events', methods: ['GET'])]
    public function getEventsForMap(EventRepository $eventRepository, CacheInterface $cache): JsonResponse
    {
        $eventsData = $cache->get('map_events_data', function () use ($eventRepository) {
            $events = $eventRepository->findAll();
            $data = [];
            
            foreach ($events as $event) {
                $coordinates = $this->getCoordinates($event->getLieu());
                
                $data[] = [
                    'id' => $event->getIdEvent(),
                    'title' => $event->getTitre(),
                    'description' => substr($event->getDescription(), 0, 120),
                    'type' => $event->getTypeEvent(),
                    'lieu' => $event->getLieu(),
                    'status' => $event->getStatus(),
                    'date' => $event->getDateDebut() ? $event->getDateDebut()->format('d/m/Y') : null,
                    'time' => $event->getHeureDebut() ? $event->getHeureDebut()->format('H:i') : null,
                    'latitude' => $coordinates['lat'],
                    'longitude' => $coordinates['lng'],
                    'image' => $event->getImage(),
                    'capacity' => $event->getMaxParticipant(),
                    'url' => $this->generateUrl('app_event_show', ['id' => $event->getIdEvent()]),
                ];
            }
            
            return $data;
        });
        
        return $this->json([
            'success' => true,
            'events' => $eventsData,
            'count' => count($eventsData)
        ]);
    }
    
    private function getCoordinates(string $address): array
    {
        $defaultLat = 36.8065;
        $defaultLng = 10.1815;
        
        if (empty($address)) {
            return ['lat' => $defaultLat, 'lng' => $defaultLng];
        }
        
        try {
            $encodedAddress = urlencode($address . ', Tunisie');
            $url = "https://nominatim.openstreetmap.org/search?q={$encodedAddress}&format=json&limit=1";
            
            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['User-Agent' => 'AutiCareApp/1.0']
            ]);
            
            $data = $response->toArray();
            
            if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                return [
                    'lat' => (float) $data[0]['lat'],
                    'lng' => (float) $data[0]['lon']
                ];
            }
        } catch (\Exception $e) {
            // Fallback
        }
        
        // Fallback: generate deterministic coordinates based on address
        $hash = crc32($address);
        $lat = $defaultLat + (($hash % 200) - 100) / 1000;
        $lng = $defaultLng + (($hash % 200) - 100) / 1000;
        
        return ['lat' => $lat, 'lng' => $lng];
    }
}