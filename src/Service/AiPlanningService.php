<?php

namespace App\Service;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiPlanningService
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private HttpClientInterface $httpClient;

    public function __construct(
        string $apiKey,
        string $apiUrl,
        string $model,
        HttpClientInterface $httpClient
    ) {
        $this->apiKey = $apiKey;
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->model = $model;
        $this->httpClient = $httpClient;
    }

    /**
     * Génère un planning pour un événement et le sauvegarde en BDD
     */
    public function generatePlanning(Event $event, EntityManagerInterface $em): array
    {
        $prompt = $this->buildPrompt($event);
        
        try {
            // Construction de l'URL complète pour Groq
            $url = $this->apiUrl . '/chat/completions';
            
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un expert en organisation d\'événements pour enfants autistes. Tu génères des plannings adaptés avec créneaux courts (15-20min), pauses sensorielles, et structure visuelle. Tu réponds uniquement en JSON valide, sans texte explicatif avant ou après.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                $errorContent = $response->getContent(false);
                throw new \Exception('API returned status ' . $statusCode . ': ' . $errorContent);
            }
            
            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'];
            
            // Nettoyer le JSON (enlever les balises markdown si présentes)
            $content = trim($content);
            $content = preg_replace('/^```json\s*|\s*```$/', '', $content);
            $content = preg_replace('/^```\s*|\s*```$/', '', $content);
            
            $result = json_decode($content, true);
            
            // Vérifier que le JSON est valide
            if ($result === null) {
                throw new \Exception('Invalid JSON response: ' . substr($content, 0, 200));
            }
            
            // 🔥 Sauvegarder le planning en base de données
            $event->setPlanning(json_encode($result, JSON_UNESCAPED_UNICODE));
            $em->persist($event);
            $em->flush();
            
            return $result;
            
        } catch (\Exception $e) {
            throw new \Exception('Erreur API Groq: ' . $e->getMessage());
        }
    }

    /**
     * Récupère le planning sauvegardé d'un événement
     */
    public function getPlanning(Event $event): ?array
    {
        $planningJson = $event->getPlanning();
        if (!$planningJson) {
            return null;
        }
        
        return json_decode($planningJson, true);
    }

    /**
     * Sauvegarde un planning modifié manuellement
     */
    public function savePlanning(Event $event, array $planning, EntityManagerInterface $em): void
    {
        $event->setPlanning(json_encode($planning, JSON_UNESCAPED_UNICODE));
        $em->persist($event);
        $em->flush();
    }

    /**
     * Construit le prompt pour l'IA
     */
    private function buildPrompt(Event $event): string
    {
        $heureDebut = $event->getHeureDebut() ? $event->getHeureDebut()->format('H:i') : '09:00';
        $heureFin = $event->getHeureFin() ? $event->getHeureFin()->format('H:i') : '17:00';
        $dateDebut = $event->getDateDebut() ? $event->getDateDebut()->format('d/m/Y') : 'à définir';
        
        return sprintf(
            'Génère un planning pour cet événement destiné à des enfants autistes.
            
            === INFORMATIONS ===
            Titre : %s
            Description : %s
            Date : %s
            Horaires : %s à %s
            
            === RÈGLES À RESPECTER ===
            1. Créneaux de 15 à 20 minutes maximum
            2. Inclure des pauses sensorielles régulières (toutes les 2-3 activités)
            3. Alterner activités calmes et actives
            4. Ajouter des transitions visuelles entre activités
            5. Adapter au public autiste (routine, prévisibilité)
            6. IMPORTANT: Les horaires doivent être compris entre %s et %s (pas de nuit)
            
            === FORMAT DE RÉPONSE ATTENDU ===
            Réponds UNIQUEMENT avec un JSON valide, sans aucun autre texte, en suivant EXACTEMENT cette structure :
            
            {
                "creneaux": [
                    {
                        "heure_debut": "09:00",
                        "heure_fin": "09:20",
                        "titre": "Accueil visuel",
                        "description": "Rituel du matin, chanson, calendrier",
                        "type": "accueil",
                        "sensoriel": "calme"
                    },
                    {
                        "heure_debut": "09:20",
                        "heure_fin": "09:40",
                        "titre": "Atelier motricité",
                        "description": "Pâte à modeler, perles à enfiler",
                        "type": "activite",
                        "sensoriel": "modere"
                    },
                    {
                        "heure_debut": "09:40",
                        "heure_fin": "09:55",
                        "titre": "Pause sensorielle",
                        "description": "Coin calme, balles anti-stress",
                        "type": "pause",
                        "sensoriel": "pause"
                    }
                ]
            }
            
            Types possibles : accueil, activite, pause, transition, cloture
            Sensoriel possibles : calme, modere, actif, pause
            
            Génère un planning complet de %s à %s, adapté à la description de l\'événement.',
            $event->getTitre(),
            $event->getDescription(),
            $dateDebut,
            $heureDebut,
            $heureFin,
            $heureDebut,
            $heureFin,
            $heureDebut,
            $heureFin
        );
    }
}