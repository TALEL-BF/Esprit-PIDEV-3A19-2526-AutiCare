<?php

namespace App\Service;

use App\Repository\RdvRepository;
use App\Repository\SeanceRepository;
use Symfony\Bundle\SecurityBundle\Security;

class ChatContextService
{
    private RdvRepository $rdvRepository;
    private SeanceRepository $seanceRepository;
    private Security $security;

    public function __construct(
        RdvRepository $rdvRepository,
        SeanceRepository $seanceRepository,
        Security $security
    ) {
        $this->rdvRepository = $rdvRepository;
        $this->seanceRepository = $seanceRepository;
        $this->security = $security;
    }

    /**
     * Gathers context about the child's appointments and sessions.
     */
    public function getContext(): string
    {
        $user = $this->security->getUser();
        if (!$user) {
            return "Aucune donnée utilisateur trouvée.";
        }

        // Sur la base de PlanningController, on utilise l'ID de l'utilisateur
        // Dans une version plus avancée, on chercherait l'ID de l'enfant associé au parent
        $userId = method_exists($user, 'getId') ? $user->getId() : null;

        if (!$userId) {
            return "Utilisateur anonyme ou sans ID.";
        }

        $context = "Utilisateur : " . (method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : 'Inconnu') . "\n";

        // Prochains Rendez-vous
        $rdvs = $this->rdvRepository->findBy(['idAutiste' => $userId], ['dateHeureRdv' => 'ASC'], 5);
        $context .= "\n--- PROCHAINS RENDEZ-VOUS ---\n";
        if (empty($rdvs)) {
            $context .= "Aucun rendez-vous prévu.\n";
        } else {
            foreach ($rdvs as $rdv) {
                $context .= "- " . ($rdv->getDateHeureRdv() ? $rdv->getDateHeureRdv()->format('d/m/Y H:i') : '?') . " : " . $rdv->getTypeConsultation() . " (Statut: " . $rdv->getStatutRdv() . ")\n";
            }
        }

        // Dernières Séances
        $seances = $this->seanceRepository->findBy(['idAutiste' => $userId], ['dateSeance' => 'DESC'], 5);
        $context .= "\n--- DERNIÈRES SÉANCES ---\n";
        if (empty($seances)) {
            $context .= "Aucune séance enregistrée.\n";
        } else {
            foreach ($seances as $seance) {
                $context .= "- " . ($seance->getDateSeance() ? $seance->getDateSeance()->format('d/m/Y') : '?') . " : " . $seance->getTitreSeance() . "\n";
                $context .= "  Description : " . ($seance->getDescription() ?: 'Pas de description') . "\n";
            }
        }

        return $context;
    }
}
