<?php

namespace App\Controller;

use App\Entity\Sponsor;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\SponsorType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SponsorController extends AbstractController
{
    #[Route('/admin/sponsors', name: 'admin_sponsors')]
    public function index(EntityManagerInterface $em): Response
    {
        $sponsors = $em->getRepository(Sponsor::class)->findAll();
        $events = $em->getRepository(Event::class)->findAll();
        
        $totalSponsors = count($sponsors);
        $totalBudget = 0;
        $typeStats = [];
        
        foreach ($sponsors as $sponsor) {
            $totalBudget += $sponsor->getMontant();
            $type = $sponsor->getTypeSponsor();
            if ($type) {
                if (!isset($typeStats[$type])) {
                    $typeStats[$type] = 0;
                }
                $typeStats[$type]++;
            }
        }
        
        $averageAmount = $totalSponsors > 0 ? round($totalBudget / $totalSponsors / 1000, 1) : 0;
        
        return $this->render('admin/pages/sponsors.html.twig', [
            'sponsors' => $sponsors,
            'events' => $events,
            'totalSponsors' => $totalSponsors,
            'totalBudget' => round($totalBudget / 1000, 1),
            'averageAmount' => $averageAmount,
            'typeCount' => count($typeStats),
            'typeStats' => $typeStats,
        ]);
    }

    #[Route('/admin/sponsors/add', name: 'admin_sponsors_add', methods: ['POST'])]
    public function addSponsor(
        Request $request, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger,
        ValidatorInterface $validator
    ): Response {
        // Check if AJAX request for real-time validation
        $isAjax = $request->isXmlHttpRequest();
        
        $sponsor = new Sponsor();
        
        $sponsor->setNom($request->request->get('sponsorName'));
        $sponsor->setEmail($request->request->get('sponsorEmail'));
        $sponsor->setTelephone($request->request->get('sponsorPhone'));
        $sponsor->setTypeSponsor($request->request->get('sponsorType'));
        $sponsor->setDescription($request->request->get('sponsorDescription'));
        $sponsor->setMontant($request->request->get('sponsorAmount'));
        
        // VALIDATION: Test de la validité (like Figure 3 in workshop)
        $errors = $validator->validate($sponsor);
        
        if (count($errors) > 0) {
            // Collect all error messages
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
            // For AJAX requests (real-time validation), return JSON
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'errors' => $errorMessages,
                    'fieldErrors' => $this->getFieldErrors($errors)
                ], 400);
            }
            
            // For normal form submission
            $this->addFlash('error', 'Erreurs de validation : ' . implode(', ', $errorMessages));
            
            // Return to form with errors (preserve submitted data)
            $events = $em->getRepository(Event::class)->findAll();
            $sponsors = $em->getRepository(Sponsor::class)->findAll();
            $totalSponsors = count($sponsors);
            $totalBudget = 0;
            $typeStats = [];
            
            foreach ($sponsors as $s) {
                $totalBudget += $s->getMontant();
                $type = $s->getTypeSponsor();
                if ($type) {
                    if (!isset($typeStats[$type])) {
                        $typeStats[$type] = 0;
                    }
                    $typeStats[$type]++;
                }
            }
            $averageAmount = $totalSponsors > 0 ? round($totalBudget / $totalSponsors / 1000, 1) : 0;
            
            return $this->render('admin/pages/sponsors.html.twig', [
                'sponsors' => $sponsors,
                'events' => $events,
                'totalSponsors' => $totalSponsors,
                'totalBudget' => round($totalBudget / 1000, 1),
                'averageAmount' => $averageAmount,
                'typeCount' => count($typeStats),
                'typeStats' => $typeStats,
                'formErrors' => $errorMessages,
                'submittedData' => $request->request->all(),
            ]);
        }
        
        // Gérer l'upload de l'image
        $imageFile = $request->files->get('sponsorImage');
        if ($imageFile && $imageFile->getSize() > 0) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->getClientOriginalExtension();
            
            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/sponsors',
                    $newFilename
                );
                $sponsor->setImage('/uploads/sponsors/' . $newFilename);
            } catch (FileException $e) {
                if ($isAjax) {
                    return $this->json(['success' => false, 'errors' => ['Erreur lors de l\'upload de l\'image']], 400);
                }
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
            }
        }
        
        // Gestion des événements
        $eventIds = $request->request->all('sponsorEvents');
        if ($eventIds) {
            foreach ($eventIds as $eventId) {
                $event = $em->getRepository(Event::class)->find($eventId);
                if ($event) {
                    $sponsor->addEvent($event);
                }
            }
        }
        
        $em->persist($sponsor);
        $em->flush();
        
        if ($isAjax) {
            return $this->json([
                'success' => true,
                'message' => 'Sponsor ajouté avec succès !',
                'sponsor' => [
                    'id' => $sponsor->getIdSponsor(),
                    'nom' => $sponsor->getNom()
                ]
            ]);
        }
        
        $this->addFlash('success', 'Sponsor ajouté avec succès !');
        return $this->redirectToRoute('admin_sponsors');
    }

    #[Route('/admin/sponsors/validate', name: 'admin_sponsors_validate', methods: ['POST'])]
    public function validateSponsor(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $sponsor = new Sponsor();
        
        // Set only the field being validated
        $field = $request->request->get('field');
        $value = $request->request->get('value');
        
        switch ($field) {
            case 'sponsorName':
                $sponsor->setNom($value);
                break;
            case 'sponsorEmail':
                $sponsor->setEmail($value);
                break;
            case 'sponsorPhone':
                $sponsor->setTelephone($value);
                break;
            case 'sponsorType':
                $sponsor->setTypeSponsor($value);
                break;
            case 'sponsorDescription':
                $sponsor->setDescription($value);
                break;
            case 'sponsorAmount':
                $sponsor->setMontant($value);
                break;
        }
        
        $errors = $validator->validate($sponsor);
        
        $fieldErrors = [];
        foreach ($errors as $error) {
            $propertyPath = $error->getPropertyPath();
            $fieldErrors[$propertyPath] = $error->getMessage();
        }
        
        return $this->json([
            'valid' => count($errors) === 0,
            'errors' => $fieldErrors
        ]);
    }

    #[Route('/admin/sponsors/get/{id}', name: 'admin_sponsors_get')]
    public function getSponsor(int $id, EntityManagerInterface $em): JsonResponse
    {
        $sponsor = $em->getRepository(Sponsor::class)->find($id);
        
        if (!$sponsor) {
            return $this->json(['error' => 'Sponsor non trouvé'], 404);
        }
        
        $eventIds = [];
        foreach ($sponsor->getEvents() as $event) {
            $eventIds[] = $event->getIdEvent();
        }
        
        return $this->json([
            'idSponsor' => $sponsor->getIdSponsor(),
            'nom' => $sponsor->getNom(),
            'email' => $sponsor->getEmail(),
            'telephone' => $sponsor->getTelephone(),
            'TypeSponsor' => $sponsor->getTypeSponsor(),
            'description' => $sponsor->getDescription(),
            'montant' => $sponsor->getMontant(),
            'image' => $sponsor->getImage(),
            'eventIds' => $eventIds,
        ]);
    }

    #[Route('/admin/sponsors/update', name: 'admin_sponsors_update', methods: ['POST'])]
    public function updateSponsor(
        Request $request, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger,
        ValidatorInterface $validator
    ): Response {
        // Check if AJAX request for real-time validation
        $isAjax = $request->isXmlHttpRequest();
        
        $id = $request->request->get('sponsorId');
        $sponsor = $em->getRepository(Sponsor::class)->find($id);
        
        if (!$sponsor) {
            if ($isAjax) {
                return $this->json(['success' => false, 'errors' => ['Sponsor non trouvé']], 404);
            }
            $this->addFlash('error', 'Sponsor non trouvé');
            return $this->redirectToRoute('admin_sponsors');
        }
        
        $sponsor->setNom($request->request->get('sponsorName'));
        $sponsor->setEmail($request->request->get('sponsorEmail'));
        $sponsor->setTelephone($request->request->get('sponsorPhone'));
        $sponsor->setTypeSponsor($request->request->get('sponsorType'));
        $sponsor->setDescription($request->request->get('sponsorDescription'));
        $sponsor->setMontant($request->request->get('sponsorAmount'));
        
        // VALIDATION: Test de la validité (like Figure 3 in workshop)
        $errors = $validator->validate($sponsor);
        
        if (count($errors) > 0) {
            // Collect all error messages
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
            // For AJAX requests (real-time validation), return JSON
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'errors' => $errorMessages,
                    'fieldErrors' => $this->getFieldErrors($errors)
                ], 400);
            }
            
            // For normal form submission
            $this->addFlash('error', 'Erreurs de validation : ' . implode(', ', $errorMessages));
            
            // Return to form with errors
            $events = $em->getRepository(Event::class)->findAll();
            $sponsors = $em->getRepository(Sponsor::class)->findAll();
            $totalSponsors = count($sponsors);
            $totalBudget = 0;
            $typeStats = [];
            
            foreach ($sponsors as $s) {
                $totalBudget += $s->getMontant();
                $type = $s->getTypeSponsor();
                if ($type) {
                    if (!isset($typeStats[$type])) {
                        $typeStats[$type] = 0;
                    }
                    $typeStats[$type]++;
                }
            }
            $averageAmount = $totalSponsors > 0 ? round($totalBudget / $totalSponsors / 1000, 1) : 0;
            
            return $this->render('admin/pages/sponsors.html.twig', [
                'sponsors' => $sponsors,
                'events' => $events,
                'totalSponsors' => $totalSponsors,
                'totalBudget' => round($totalBudget / 1000, 1),
                'averageAmount' => $averageAmount,
                'typeCount' => count($typeStats),
                'typeStats' => $typeStats,
                'formErrors' => $errorMessages,
                'submittedData' => $request->request->all(),
                'editId' => $id
            ]);
        }
        
        // Gérer la nouvelle image (si uploadée)
        $imageFile = $request->files->get('sponsorImage');
        if ($imageFile && $imageFile->getSize() > 0) {
            // Supprimer l'ancienne image si elle existe
            if ($sponsor->getImage()) {
                $oldImagePath = $this->getParameter('kernel.project_dir') . '/public' . $sponsor->getImage();
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->getClientOriginalExtension();
            
            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/sponsors',
                    $newFilename
                );
                $sponsor->setImage('/uploads/sponsors/' . $newFilename);
            } catch (FileException $e) {
                if ($isAjax) {
                    return $this->json(['success' => false, 'errors' => ['Erreur lors de l\'upload de l\'image']], 400);
                }
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
            }
        }
        
        // Mise à jour des événements
        // 1. Supprimer tous les événements existants
        foreach ($sponsor->getEvents() as $existingEvent) {
            $sponsor->removeEvent($existingEvent);
        }
        
        // 2. Ajouter les nouveaux événements
        $eventIds = $request->request->all('sponsorEvents');
        if ($eventIds) {
            foreach ($eventIds as $eventId) {
                $event = $em->getRepository(Event::class)->find($eventId);
                if ($event) {
                    $sponsor->addEvent($event);
                }
            }
        }
        
        $em->flush();
        
        if ($isAjax) {
            return $this->json([
                'success' => true,
                'message' => 'Sponsor modifié avec succès !'
            ]);
        }
        
        $this->addFlash('success', 'Sponsor modifié avec succès !');
        return $this->redirectToRoute('admin_sponsors');
    }

    #[Route('/admin/sponsors/delete/{id}', name: 'admin_sponsors_delete', methods: ['DELETE'])]
    public function deleteSponsor(int $id, EntityManagerInterface $em): JsonResponse
    {
        $sponsor = $em->getRepository(Sponsor::class)->find($id);
        
        if (!$sponsor) {
            return $this->json(['success' => false, 'error' => 'Sponsor non trouvé'], 404);
        }
        
        try {
            if ($sponsor->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public' . $sponsor->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $em->remove($sponsor);
            $em->flush();
            
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/admin/sponsors/search', name: 'admin_sponsors_search', methods: ['POST'])]
    public function searchSponsors(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $searchTerm = $request->request->get('search', '');
        $type = $request->request->get('type', 'tous');
        $sortBy = $request->request->get('sortBy', 'montant_desc');
        
        $qb = $em->createQueryBuilder();
        $qb->select('s')
           ->from(Sponsor::class, 's');
        
        // 1. Recherche intelligente
        if (!empty($searchTerm)) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('s.nom', ':search'),
                $qb->expr()->like('s.description', ':search'),
                $qb->expr()->like('s.email', ':search'),
                $qb->expr()->like('s.telephone', ':search'),
                $qb->expr()->like('s.TypeSponsor', ':search')
            ))->setParameter('search', '%' . $searchTerm . '%');
            
            // Recherche numérique pour le montant
            if (is_numeric($searchTerm)) {
                $qb->orWhere($qb->expr()->eq('s.montant', ':montant'))
                   ->setParameter('montant', (int)$searchTerm);
            }
        }
        
        // 2. Filtre par type de sponsor
        if (!empty($type) && $type !== 'tous') {
            $qb->andWhere('s.TypeSponsor = :type')
               ->setParameter('type', $type);
        }
        
        // 3. Tri
        switch ($sortBy) {
            case 'nom_asc':
                $qb->orderBy('s.nom', 'ASC');
                break;
            case 'nom_desc':
                $qb->orderBy('s.nom', 'DESC');
                break;
            case 'montant_asc':
                $qb->orderBy('s.montant', 'ASC');
                break;
            case 'type_asc':
                $qb->orderBy('s.TypeSponsor', 'ASC');
                break;
            case 'montant_desc':
            default:
                $qb->orderBy('s.montant', 'DESC');
                break;
        }
        
        $sponsors = $qb->getQuery()->getResult();
        
        $data = [];
        foreach ($sponsors as $sponsor) {
            $eventIds = [];
            foreach ($sponsor->getEvents() as $event) {
                $eventIds[] = $event->getIdEvent();
            }
            
            $data[] = [
                'idSponsor' => $sponsor->getIdSponsor(),
                'nom' => $sponsor->getNom(),
                'image' => $sponsor->getImage(),
                'TypeSponsor' => $sponsor->getTypeSponsor(),
                'email' => $sponsor->getEmail(),
                'telephone' => $sponsor->getTelephone(),
                'montant' => $sponsor->getMontant(),
                'description' => $sponsor->getDescription(),
                'eventsCount' => count($eventIds)
            ];
        }
        
        return $this->json([
            'total' => count($data),
            'sponsors' => $data
        ]);
    }

    // Helper function to get field-specific errors
    private function getFieldErrors($errors): array
    {
        $fieldErrors = [];
        foreach ($errors as $error) {
            $propertyPath = $error->getPropertyPath();
            switch ($propertyPath) {
                case 'nom':
                    $fieldErrors['sponsorName'] = $error->getMessage();
                    break;
                case 'email':
                    $fieldErrors['sponsorEmail'] = $error->getMessage();
                    break;
                case 'telephone':
                    $fieldErrors['sponsorPhone'] = $error->getMessage();
                    break;
                case 'TypeSponsor':
                    $fieldErrors['sponsorType'] = $error->getMessage();
                    break;
                case 'description':
                    $fieldErrors['sponsorDescription'] = $error->getMessage();
                    break;
                case 'montant':
                    $fieldErrors['sponsorAmount'] = $error->getMessage();
                    break;
            }
        }
        return $fieldErrors;
    }
    #[Route('/admin/sponsors/validate-field', name: 'admin_sponsors_validate_field', methods: ['POST'])]
public function validateField(Request $request, ValidatorInterface $validator): JsonResponse
{
    $field = $request->request->get('field');
    $value = $request->request->get('value');
    
    $sponsor = new Sponsor();
    $errors = [];
    
    switch ($field) {
        case 'sponsorName':
            $sponsor->setNom($value);
            $propertyErrors = $validator->validateProperty($sponsor, 'nom');
            break;
        case 'sponsorEmail':
            $sponsor->setEmail($value);
            $propertyErrors = $validator->validateProperty($sponsor, 'email');
            break;
        case 'sponsorPhone':
            $sponsor->setTelephone($value);
            $propertyErrors = $validator->validateProperty($sponsor, 'telephone');
            break;
        case 'sponsorType':
            $sponsor->setTypeSponsor($value);
            $propertyErrors = $validator->validateProperty($sponsor, 'TypeSponsor');
            break;
        case 'sponsorDescription':
            $sponsor->setDescription($value);
            $propertyErrors = $validator->validateProperty($sponsor, 'description');
            break;
        case 'sponsorAmount':
            $sponsor->setMontant($value);
            $propertyErrors = $validator->validateProperty($sponsor, 'montant');
            break;
        default:
            return $this->json(['valid' => true, 'errors' => []]);
    }
    
    if (count($propertyErrors) > 0) {
        foreach ($propertyErrors as $error) {
            $errors[$field] = $error->getMessage();
        }
    }
    
    return $this->json([
        'valid' => empty($errors),
        'errors' => $errors
    ]);
}
    
}