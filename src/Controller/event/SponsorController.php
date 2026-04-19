<?php

namespace App\Controller\event;

use App\Entity\Sponsor;
use App\Entity\Event;
 use League\Csv\Writer;
 use Nucleos\DompdfBundle\Factory\DompdfFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\SponsorType;
use Dompdf\Options;
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
        
        return $this->render('admin/pages/event/sponsors.html.twig', [
            'sponsors' => $sponsors,
            'events' => $events,
            'totalSponsors' => $totalSponsors,
            'totalBudget' => round($totalBudget / 1000, 1),
            'averageAmount' => $averageAmount,
            'typeCount' => count($typeStats),
            'typeStats' => $typeStats,
        ]);
    }
   

#[Route('/admin/sponsors/export/csv', name: 'admin_sponsors_export_csv', methods: ['GET'])]
public function exportSponsorsCsv(
    Request $request, 
    SponsorRepository $sponsorRepository
): Response {
    $searchTerm = $request->query->get('search', '');
    $type = $request->query->get('type', 'tous');
    $sortBy = $request->query->get('sortBy', 'montant_desc');
    
    $sponsors = $sponsorRepository->findByFiltersForExport($searchTerm, $type, $sortBy);
    
    $csv = Writer::createFromString('');
    $csv->setDelimiter(';');
    $csv->setEnclosure('"');
    
    // ✅ Utiliser les bons caractères UTF-8
    $csv->insertOne(['Nom', 'Type', 'Email', 'Téléphone', 'Montant (TND)', 'Description']);
    
    foreach ($sponsors as $sponsor) {
        $csv->insertOne([
            $sponsor->getNom(),
            $sponsor->getTypeSponsor() ?? 'Non défini',
            $sponsor->getEmail() ?? '-',
            $sponsor->getTelephone() ?? '-',
            number_format($sponsor->getMontant(), 2, ',', ' '),
            $sponsor->getDescription()
        ]);
    }
    
    
    $bom = "\xEF\xBB\xBF";
    $content = $bom . $csv->toString();
    
    $response = new Response($content);
    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="sponsors_' . date('Y-m-d_H-i-s') . '.csv"');
    
    return $response;
}

    #[Route('/admin/sponsors/add', name: 'admin_sponsors_add', methods: ['POST'])]
    public function addSponsor(
        Request $request, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger,
        ValidatorInterface $validator
    ): Response {
       
        $isAjax = $request->isXmlHttpRequest();
        
        $sponsor = new Sponsor();
        
        $sponsor->setNom($request->request->get('sponsorName'));
        $sponsor->setEmail($request->request->get('sponsorEmail'));
        $sponsor->setTelephone($request->request->get('sponsorPhone'));
        $sponsor->setTypeSponsor($request->request->get('sponsorType'));
        $sponsor->setDescription($request->request->get('sponsorDescription'));
        $sponsor->setMontant($request->request->get('sponsorAmount'));
        
       
        $errors = $validator->validate($sponsor);
        
        if (count($errors) > 0) {
          
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
           
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'errors' => $errorMessages,
                    'fieldErrors' => $this->getFieldErrors($errors)
                ], 400);
            }
            
           
            $this->addFlash('error', 'Erreurs de validation : ' . implode(', ', $errorMessages));
            
           
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
            
            return $this->render('admin/pages/event/sponsors.html.twig', [
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
        
      
        $errors = $validator->validate($sponsor);
        
        if (count($errors) > 0) {
           
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
           
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'errors' => $errorMessages,
                    'fieldErrors' => $this->getFieldErrors($errors)
                ], 400);
            }
            
           
            $this->addFlash('error', 'Erreurs de validation : ' . implode(', ', $errorMessages));
            
           
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
            
            return $this->render('admin/pages/event/sponsors.html.twig', [
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
        
       
        $imageFile = $request->files->get('sponsorImage');
        if ($imageFile && $imageFile->getSize() > 0) {
           
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
        
        
       
        foreach ($sponsor->getEvents() as $existingEvent) {
            $sponsor->removeEvent($existingEvent);
        }
        
      
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
        
       
        if (!empty($searchTerm)) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('s.nom', ':search'),
                $qb->expr()->like('s.description', ':search'),
                $qb->expr()->like('s.email', ':search'),
                $qb->expr()->like('s.telephone', ':search'),
                $qb->expr()->like('s.TypeSponsor', ':search')
            ))->setParameter('search', '%' . $searchTerm . '%');
            
          
            if (is_numeric($searchTerm)) {
                $qb->orWhere($qb->expr()->eq('s.montant', ':montant'))
                   ->setParameter('montant', (int)$searchTerm);
            }
        }
        
      
        if (!empty($type) && $type !== 'tous') {
            $qb->andWhere('s.TypeSponsor = :type')
               ->setParameter('type', $type);
        }
        
       
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

#[Route('/admin/sponsors/export/pdf', name: 'admin_sponsors_export_pdf', methods: ['GET'])]
public function exportPdf(
    Request $request,
    SponsorRepository $sponsorRepository,
    DompdfFactoryInterface $dompdfFactory
): Response {
    $searchTerm = $request->query->get('search', '');
    $type = $request->query->get('type', 'tous');
    $sortBy = $request->query->get('sortBy', 'montant_desc');
    
    $sponsors = $sponsorRepository->findByFiltersForExport($searchTerm, $type, $sortBy);
    
    // 🔧 NETTOYAGE RADICAL
    $cleanSponsors = [];
    $total_montant = 0;
    
    foreach ($sponsors as $sponsor) {
        $total_montant += $sponsor->getMontant();
        
        $cleanSponsors[] = [
            'nom' => $this->cleanForDompdf($sponsor->getNom()),
            'description' => $this->cleanForDompdf(substr($sponsor->getDescription() ?? '', 0, 80)),
            'TypeSponsor' => $this->cleanForDompdf($sponsor->getTypeSponsor() ?? 'Non defini'),
            'email' => $this->cleanForDompdf($sponsor->getEmail() ?? '-'),
            'telephone' => $this->cleanForDompdf($sponsor->getTelephone() ?? '-'),
            'montant' => $sponsor->getMontant(),
        ];
    }
    
    // Logo - avec chemin Windows corrigé
    $projectDir = str_replace('\\', '/', $this->getParameter('kernel.project_dir'));
    $logoFile = $projectDir . '/public/uploads/images/logo.png';
    $logoPath = file_exists($logoFile) ? 'file://' . $logoFile : null;
    
    // Template
    $html = $this->renderView('admin/pages/event/sponsors_pdf_export.html.twig', [
        'sponsors' => $cleanSponsors,
        'total_montant' => $total_montant,
        'logo_path' => $logoPath,
    ]);
    
    // Options Dompdf
    $options = new Options();
    $options->set('defaultFont', 'Helvetica');
    $options->set('isHtml5ParserEnabled', false);
    $options->set('isRemoteEnabled', true);
    
    $dompdf = $dompdfFactory->create();
    $dompdf->setOptions($options);
    $dompdf->loadHtml('<meta charset="UTF-8">' . $html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    return new Response($dompdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="sponsors_' . date('Y-m-d_H-i-s') . '.pdf"',
    ]);
}
/**
 * Nettoie une chaîne pour éviter les erreurs iconv
 */
private function cleanString(string $string): string
{
    if (empty($string)) {
        return '';
    }
    
    // Supprimer les caractères invalides UTF-8
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    
    // Remplacer les caractères problématiques
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);
    
    // Retourner la chaîne nettoyée
    return $string;
}
/**
 * Nettoie complètement une chaîne pour Dompdf
 * Remplace tous les caractères spéciaux par leur équivalent simple
 */
/**
 * Nettoie radicalement une chaîne pour Dompdf
 */
private function cleanForDompdf(string $text): string
{
    if (empty($text)) {
        return '';
    }
    
    // Convertir en UTF-8 et supprimer les caractères invalides
    $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
    if ($text === false) {
        $text = '';
    }
    
    // Remplacer tous les accents et caractères spéciaux
    $replacements = [
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a',
        'ô' => 'o', 'ö' => 'o', 'ò' => 'o', 'ó' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ç' => 'c',
        'î' => 'i', 'ï' => 'i', 'í' => 'i',
        'ÿ' => 'y',
        '€' => 'EUR',
        '…' => '...',
        '"' => '"', '"' => '"', '«' => '"', '»' => '"',
        '‘' => "'", '’' => "'", '“' => '"', '”' => '"',
    ];
    $text = strtr($text, $replacements);
    
    // Garder uniquement les caractères ASCII imprimables
    $text = preg_replace('/[^\x20-\x7E]/', '', $text);
    
    return trim($text);
}
    
}