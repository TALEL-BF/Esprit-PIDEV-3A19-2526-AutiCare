<?php

namespace App\Service;

use App\Entity\Seance;

class WebNotificationService
{
    /**
     * Prépare les données pour afficher une notification toast dans le navigateur
     * @return array Données formatées pour le frontend
     */
    public function prepareNotificationData(string $type, Seance $seance, string $action = 'create'): array
    {
        $dateSeance = $seance->getDateSeance();
        $dateFormatee = $dateSeance ? $dateSeance->format('d/m/Y') : 'Non spécifiée';
        $heureFormatee = $dateSeance ? $dateSeance->format('H:i') : 'Non spécifiée';

        return match($action) {
            'create' => $this->buildNewSeanceNotification($seance, $dateFormatee, $heureFormatee),
            'update' => $this->buildUpdateSeanceNotification($seance, $dateFormatee, $heureFormatee),
            'delete' => $this->buildDeleteSeanceNotification($seance),
            default => [],
        };
    }

    /**
     * Construit les données pour une nouvelle séance
     */
    private function buildNewSeanceNotification(Seance $seance, string $dateFormatee, string $heureFormatee): array
    {
        return [
            'type' => 'success',
            'icon' => '✅',
            'title' => 'Nouvelle Séance Créée',
            'message' => 'Une nouvelle séance a été ajoutée avec succès',
            'details' => [
                'icon' => '📚',
                'titre' => $seance->getTitreSeance(),
                'date' => "📅 $dateFormatee",
                'heure' => "⏰ $heureFormatee",
                'duree' => "⏱️ {$seance->getDuree()} minutes",
            ],
            'duration' => 5000, // 5 secondes
            'dismissible' => true,
        ];
    }

    /**
     * Construit les données pour une séance modifiée
     */
    private function buildUpdateSeanceNotification(Seance $seance, string $dateFormatee, string $heureFormatee): array
    {
        return [
            'type' => 'info',
            'icon' => '✏️',
            'title' => 'Séance Modifiée',
            'message' => 'La séance a été mise à jour avec succès',
            'details' => [
                'icon' => '📚',
                'titre' => $seance->getTitreSeance(),
                'date' => "📅 $dateFormatee",
                'heure' => "⏰ $heureFormatee",
                'duree' => "⏱️ {$seance->getDuree()} minutes",
            ],
            'duration' => 5000,
            'dismissible' => true,
        ];
    }

    /**
     * Construit les données pour une séance supprimée
     */
    private function buildDeleteSeanceNotification(Seance $seance): array
    {
        return [
            'type' => 'warning',
            'icon' => '🗑️',
            'title' => 'Séance Supprimée',
            'message' => $seance->getTitreSeance() . ' a été supprimée',
            'details' => [],
            'duration' => 3000,
            'dismissible' => true,
        ];
    }

    /**
     * Convertit les données de notification en JSON pour envoyer au frontend
     */
    public function toJSON(array $notificationData): string
    {
        return json_encode($notificationData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
