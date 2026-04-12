<?php

namespace App\Controller;

use App\Repository\RdvRepository;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'app_calendar')]
    public function index()
    {
        return $this->render('calendar/index.html.twig');
    }

    #[Route('/api/events', name: 'api_events', methods: ['GET'])]
    public function getEvents(Request $request, RdvRepository $rdvRepository, SeanceRepository $seanceRepository)
    {
        $typeFilter = $request->query->get('type');
        $events = [];

        if (!$typeFilter || $typeFilter === 'rdv') {
            $rdvs = $rdvRepository->findAll();
            foreach ($rdvs as $rdv) {
                $events[] = [
                    'id' => 'rdv_' . $rdv->getId(),
                    'title' => '[RDV] ' . $rdv->getTypeConsultation(),
                    'start' => $rdv->getDateHeureRdv()->format('c'),
                    'end' => (clone $rdv->getDateHeureRdv())->modify('+' . $rdv->getDureeRdvMinutes() . ' minutes')->format('c'),
                    'backgroundColor' => '#f472b6', /* Retour au Rose pour RDV */
                    'borderColor' => '#f472b6',
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'type' => 'rdv',
                        'dbId' => $rdv->getId(),
                        'statut' => $rdv->getStatutRdv(),
                    ]
                ];
            }
        }

        if (!$typeFilter || $typeFilter === 'seance') {
            $seances = $seanceRepository->findAll();
            foreach ($seances as $seance) {
                $events[] = [
                    'id' => 'seance_' . $seance->getId(),
                    'title' => '[Séance] ' . $seance->getTitreSeance(),
                    'start' => $seance->getDateSeance()->format('c'),
                    'end' => (clone $seance->getDateSeance())->modify('+' . $seance->getDuree() . ' minutes')->format('c'),
                    'backgroundColor' => '#f472b6', /* Rose pour Séance */
                    'borderColor' => '#f472b6',
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'type' => 'seance',
                        'dbId' => $seance->getId(),
                        'statut' => $seance->getStatutSeance(),
                    ]
                ];
            }
        }

        return new JsonResponse($events);
    }

    #[Route('/api/events/move', name: 'api_events_move', methods: ['POST'])]
    public function moveEvent(Request $request, RdvRepository $rdvRepository, SeanceRepository $seanceRepository, EntityManagerInterface $em)
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return new JsonResponse(['error' => 'Invalid data'], 400);

        $idParts = explode('_', $data['id']);
        $type = $idParts[0];
        $id = $idParts[1];
        $newStart = new \DateTime($data['start']);

        if ($type === 'rdv') {
            $rdv = $rdvRepository->find($id);
            if ($rdv) {
                $rdv->setDateHeureRdv($newStart);
                $em->flush();
            }
        } elseif ($type === 'seance') {
            $seance = $seanceRepository->find($id);
            if ($seance) {
                $seance->setDateSeance($newStart);
                $em->flush();
            }
        }

        return new JsonResponse(['status' => 'success']);
    }
}
