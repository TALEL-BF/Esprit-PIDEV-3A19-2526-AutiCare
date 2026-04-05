<?php

namespace App\Controller\event;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'app_events')]
    #[Route('/evenements', name: 'app_evenements')]
    public function index(Request $request, EventRepository $eventRepository): Response
    {
        // Récupérer les paramètres de recherche
        $searchTerm = $request->query->get('search');
        $type = $request->query->get('type');
        $status = $request->query->get('status');
        $period = $request->query->get('period');
        $sortBy = $request->query->get('sort', 'date_desc');
        $page = $request->query->getInt('page', 1);
        
        // Si 'type' vaut 'tous', on le met à null
        if ($type === 'tous') {
            $type = null;
        }
        if ($status === 'tous') {
            $status = null;
        }
        if ($period === 'toutes') {
            $period = null;
        }
        
        // Nombre d'événements par page
        $limit = 6;
        
        // Compter le total d'événements avec les filtres
        $totalEvents = $eventRepository->countWithFilters(
            searchTerm: $searchTerm,
            type: $type,
            status: $status,
            period: $period
        );
        
        // Calculer le nombre total de pages
        $totalPages = ceil($totalEvents / $limit);
        
        // S'assurer que la page est valide
        if ($page < 1) {
            $page = 1;
        }
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }
        
        // Récupérer les événements de la page courante
        $queryBuilder = $eventRepository->createFilteredQueryBuilder(
            searchTerm: $searchTerm,
            type: $type,
            status: $status,
            period: $period,
            sortBy: $sortBy
        );
        
        $events = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        
        return $this->render('front/events/event.html.twig', [
            'events' => $events,
            'searchTerm' => $searchTerm,
            'selectedType' => $type,
            'selectedStatus' => $status,
            'selectedPeriod' => $period,
            'selectedSort' => $sortBy,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEvents' => $totalEvents,
            'limit' => $limit
        ]);
    }
    
    /**
     * ROUTE API pour la recherche AJAX (avec pagination)
     */
    #[Route('/api/events/search', name: 'api_events_search')]
    public function searchEvents(Request $request, EventRepository $eventRepository): Response
    {
        // Récupérer les paramètres de la requête AJAX
        $searchTerm = $request->query->get('q');
        $type = $request->query->get('type');
        $page = $request->query->getInt('page', 1);
        
        // Si type = 'all' ou vide, on met à null
        if ($type === 'all' || $type === '' || $type === 'tous') {
            $type = null;
        }
        
        $limit = 6;
        
        // Compter le total
        $totalEvents = $eventRepository->countWithFilters(
            searchTerm: $searchTerm,
            type: $type,
            status: null,
            period: null
        );
        
        $totalPages = ceil($totalEvents / $limit);
        
        // Récupérer les événements paginés
        $queryBuilder = $eventRepository->createFilteredQueryBuilder(
            searchTerm: $searchTerm,
            type: $type,
            status: null,
            period: null,
            sortBy: 'date_desc'
        );
        
        $events = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        
        // Formater les données pour le JSON
        $data = [];
        foreach ($events as $event) {
            // Calculer l'heure de début seulement
            $heureDebut = $event->getHeureDebut() ? $event->getHeureDebut()->format('H:i') : null;
            
            $data[] = [
                'id' => $event->getIdEvent(),
                'titre' => $event->getTitre(),
                'description' => $event->getDescription(),
                'typeEvent' => $event->getTypeEvent(),
                'lieu' => $event->getLieu(),
                'status' => $event->getStatus(),
                'dateDebut' => $event->getDateDebut()->format('d/m/Y'),
                'heureDebut' => $heureDebut,
                'maxParticipant' => $event->getMaxParticipant(),
                'participantsCount' => 0,
                'image' => $event->getImage(),
            ];
        }
        
        return $this->json([
            'events' => $data,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEvents' => $totalEvents
        ]);
    }
   #[Route('/event/{id}', name: 'app_event_show')]
public function show(int $id, EntityManagerInterface $entityManager): Response
{
    $event = $entityManager->getRepository(Event::class)->find($id);
    
    if (!$event) {
        throw $this->createNotFoundException('Événement non trouvé');
    }
    
    // 🔥 CORRECTION : Ajouter le slash au début si nécessaire
    $imagePath = null;
    $imageName = $event->getImage();
    
    if ($imageName) {
        // Si le chemin ne commence pas par /, on l'ajoute
        if (strpos($imageName, '/') === 0) {
            $imagePath = $imageName;
        } else {
            $imagePath = '/' . $imageName;  // ← AJOUTE LE SLASH AU DÉBUT
        }
    }
    
    $sponsors = $event->getSponsors();
    
    $similarEvents = $entityManager->getRepository(Event::class)
        ->createQueryBuilder('e')
        ->where('e.typeEvent = :type')
        ->andWhere('e.idEvent != :id')
        ->setParameter('type', $event->getTypeEvent())
        ->setParameter('id', $id)
        ->setMaxResults(3)
        ->getQuery()
        ->getResult();
    
    return $this->render('front/events/show.html.twig', [
        'event' => $event,
        'imagePath' => $imagePath,  // ← ENVOIE LE CHEMIN CORRIGÉ
        'sponsors' => $sponsors,
        'similarEvents' => $similarEvents
    ]);
}
    
}