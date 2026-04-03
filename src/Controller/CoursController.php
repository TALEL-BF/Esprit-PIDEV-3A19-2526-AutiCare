<?php
// src/Controller/CoursController.php

namespace App\Controller;

use App\Entity\Cours;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class CoursController extends AbstractController
{
    private const UPLOAD_DIR = '/public/uploads/images';
    
    // ─────────────────────────────────────────────────────────────────
    // ROUTES PUBLIQUES (Front-end)
    // ─────────────────────────────────────────────────────────────────
    
    #[Route('/cours', name: 'app_cours')]
    public function index(CoursRepository $coursRepository): Response
    {
        // Récupérer tous les cours depuis la base de données
        $cours = $coursRepository->findAll();
        
        // Transformer les cours en format compatible avec le template
        $courses = [];
        $categories = ['Académique', 'Social', 'Motricité', 'Langage'];
        $colors = ['communication', 'social', 'motricite', 'autonomie', 'sensoriel', 'communication'];
        $emojis = ['📚', '🤝', '🏃', '⭐', '🎨', '💬'];
        
        foreach ($cours as $index => $c) {
            // Déterminer la catégorie en fonction du type de cours
            $category = match($c->getTypeCours()) {
                'Académique' => 'communication',
                'Social' => 'social',
                'Motricité' => 'motricite',
                'Langage' => 'communication',
                default => 'communication'
            };
            
            // Déterminer l'emoji
            $emoji = match($c->getTypeCours()) {
                'Académique' => '📚',
                'Social' => '🤝',
                'Motricité' => '🏃',
                'Langage' => '💬',
                default => '🎓'
            };
            
            // Calculer le nombre de leçons (basé sur les mots)
            $motsCount = $c->getMots() ? count(explode(';', $c->getMots())) : 0;
            $lessons = max(5, ceil($motsCount / 2));
            
            // Calculer la durée en heures
            $hours = round($c->getDuree() / 60, 1);
            
            $courses[] = [
                'id' => $c->getIdCours(),
                'title' => $c->getTitre(),
                'description' => $c->getDescription(),
                'category' => $category,
                'type' => $c->getTypeCours(),
                'level' => $c->getNiveau(),
                'duration' => $c->getDuree(),
                'hours' => $hours,
                'lessons' => $lessons,
                'sessions' => $lessons,
                'emoji' => $emoji,
                'badge' => $c->getNiveau(),
                'price' => 0, // À définir selon votre logique
                'rating' => 4.5, // À définir selon votre logique
                'reviews' => 12, // À définir selon votre logique
                'instructor' => 'AutiCare Team',
                'instructor_title' => 'Équipe pédagogique',
                'image' => $c->getImage()
            ];
        }
        
        // Si aucun cours en base, afficher des exemples
        if (empty($courses)) {
            $courses = [
                [
                    'id' => 1,
                    'title' => 'Communication et Langage',
                    'description' => 'Apprenez à développer les compétences de communication chez les enfants TSA à travers des activités ludiques et adaptées.',
                    'category' => 'communication',
                    'type' => 'Langage',
                    'level' => 'Débutant',
                    'duration' => 180,
                    'hours' => 3,
                    'lessons' => 8,
                    'sessions' => 8,
                    'emoji' => '💬',
                    'badge' => 'Populaire',
                    'price' => 49,
                    'rating' => 4.8,
                    'reviews' => 156,
                    'instructor' => 'Dr. Sarah Martin',
                    'instructor_title' => 'Orthophoniste',
                    'image' => null
                ],
                [
                    'id' => 2,
                    'title' => 'Motricité Fine',
                    'description' => 'Développement des habiletés motrices fines à travers des exercices adaptés aux besoins spécifiques.',
                    'category' => 'motricite',
                    'type' => 'Motricité',
                    'level' => 'Intermédiaire',
                    'duration' => 240,
                    'hours' => 4,
                    'lessons' => 10,
                    'sessions' => 10,
                    'emoji' => '✍️',
                    'badge' => 'Recommandé',
                    'price' => 59,
                    'rating' => 4.7,
                    'reviews' => 98,
                    'instructor' => 'Thomas Dubois',
                    'instructor_title' => 'Ergothérapeute',
                    'image' => null
                ],
                [
                    'id' => 3,
                    'title' => 'Socialisation',
                    'description' => 'Apprenez à interagir avec les autres, comprendre les émotions et développer des amitiés.',
                    'category' => 'social',
                    'type' => 'Social',
                    'level' => 'Débutant',
                    'duration' => 300,
                    'hours' => 5,
                    'lessons' => 12,
                    'sessions' => 12,
                    'emoji' => '🤝',
                    'badge' => 'Nouveau',
                    'price' => 69,
                    'rating' => 4.9,
                    'reviews' => 203,
                    'instructor' => 'Julie Moreau',
                    'instructor_title' => 'Éducatrice spécialisée',
                    'image' => null
                ],
                [
                    'id' => 4,
                    'title' => 'Autonomie Quotidienne',
                    'description' => 'Acquérez les compétences nécessaires pour le quotidien : s\'habiller, manger, se laver.',
                    'category' => 'autonomie',
                    'type' => 'Académique',
                    'level' => 'Intermédiaire',
                    'duration' => 360,
                    'hours' => 6,
                    'lessons' => 15,
                    'sessions' => 15,
                    'emoji' => '⭐',
                    'badge' => 'Top vente',
                    'price' => 79,
                    'rating' => 4.8,
                    'reviews' => 312,
                    'instructor' => 'Dr. Pierre Richard',
                    'instructor_title' => 'Psychologue',
                    'image' => null
                ],
                [
                    'id' => 5,
                    'title' => 'Exploration Sensorielle',
                    'description' => 'Découvrez et apprivoisez vos sens à travers des activités apaisantes et structurées.',
                    'category' => 'sensoriel',
                    'type' => 'Académique',
                    'level' => 'Avancé',
                    'duration' => 240,
                    'hours' => 4,
                    'lessons' => 9,
                    'sessions' => 9,
                    'emoji' => '🎨',
                    'badge' => 'Épuisé',
                    'price' => 89,
                    'rating' => 4.6,
                    'reviews' => 67,
                    'instructor' => 'Sophie Lambert',
                    'instructor_title' => 'Art-thérapeute',
                    'image' => null
                ],
                [
                    'id' => 6,
                    'title' => 'Gestion des Émotions',
                    'description' => 'Apprenez à reconnaître, comprendre et gérer vos émotions au quotidien.',
                    'category' => 'communication',
                    'type' => 'Langage',
                    'level' => 'Débutant',
                    'duration' => 200,
                    'hours' => 3.5,
                    'lessons' => 8,
                    'sessions' => 8,
                    'emoji' => '😊',
                    'badge' => '',
                    'price' => 55,
                    'rating' => 4.7,
                    'reviews' => 145,
                    'instructor' => 'Laura Bernard',
                    'instructor_title' => 'Psychomotricienne',
                    'image' => null
                ]
            ];
        }
        
        return $this->render('front/cours/index.html.twig', [
            'courses' => $courses
        ]);
    }
    
    // ─────────────────────────────────────────────────────────────────
    // ROUTES ADMINISTRATION
    // ─────────────────────────────────────────────────────────────────
    
    #[Route('/admin/cours', name: 'admin_cours_list')]
    public function list(CoursRepository $coursRepository): Response
    {
        // Récupérer tous les cours
        $cours = $coursRepository->findAll();
        
        // Calcul du nombre total de mots
        $totalMots = 0;
        foreach ($cours as $c) {
            $mots = $c->getMots();
            if ($mots) {
                $totalMots += count(explode(';', $mots));
            }
        }
        
        // Retourner le template avec les variables
        return $this->render('admin/pages/cours.html.twig', [
            'cours_list' => $cours,
            'totalCourses' => count($cours),
            'publishedCount' => count($cours),
            'draftCount' => 0,
            'totalMots' => $totalMots
        ]);
    }
    
    #[Route('/admin/cours/save', name: 'admin_cours_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, CoursRepository $coursRepository): Response
    {
        $idCours = $request->request->get('id_cours');
        
        if ($idCours && !empty($idCours)) {
            $cours = $coursRepository->find($idCours);
            if (!$cours) {
                $this->addFlash('error', 'Cours non trouvé');
                return $this->redirectToRoute('admin_cours_list');
            }
        } else {
            $cours = new Cours();
        }
        
        // Créer le dossier d'upload s'il n'existe pas
        $uploadDir = $this->getParameter('kernel.project_dir') . self::UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Récupération des données
        $titre = $request->request->get('titre');
        $typeCours = $request->request->get('type_cours');
        $niveau = $request->request->get('niveau');
        $duree = $request->request->get('duree');
        $description = $request->request->get('description');
        $mots = $request->request->all('mots');
        
        // Validation des champs requis
        if (empty($titre) || empty($typeCours) || empty($niveau) || empty($duree) || empty($description)) {
            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires');
            return $this->redirectToRoute('admin_cours_list');
        }
        
        // Traitement des mots
        $motsArray = [];
        if ($mots && is_array($mots)) {
            foreach ($mots as $motGroup) {
                if (!empty($motGroup)) {
                    if (strpos($motGroup, ';') !== false) {
                        $splitMots = array_map('trim', explode(';', $motGroup));
                        $motsArray = array_merge($motsArray, $splitMots);
                    } else {
                        $motsArray[] = trim($motGroup);
                    }
                }
            }
        }
        $motsString = implode(';', array_filter($motsArray));
        
  
        $imageFile = $request->files->get('image_file');
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = 'cours_' . $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
            
            try {
                $imageFile->move($uploadDir, $newFilename);
                $cours->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image du cours');
            }
        }
        
        
        $imagesMots = [];
        $imgMots = $request->request->all('img_mots');
        
        if ($imgMots && is_array($imgMots)) {
            foreach ($imgMots as $index => $mot) {
                $mot = trim($mot);
                if (!empty($mot)) {
                    $files = $request->files->get('images_files_' . $index);
                    if ($files && is_array($files)) {
                        $motImages = [];
                        foreach ($files as $file) {
                            if ($file instanceof UploadedFile) {
                                $filename = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                                $newFilename = 'mot_' . $slugger->slug($mot) . '_' . $filename . '-' . uniqid() . '.' . $file->guessExtension();
                                try {
                                    $file->move($uploadDir, $newFilename);
                                    $motImages[] = $newFilename;
                                } catch (FileException $e) {
                                    $this->addFlash('error', 'Erreur lors de l\'upload d\'une image pour le mot: ' . $mot);
                                }
                            }
                        }
                        if (!empty($motImages)) {
                            $imagesMots[] = $mot . ':' . implode(',', $motImages);
                        }
                    }
                }
            }
        }
        
        $cours->setTitre($titre);
        $cours->setTypeCours($typeCours);
        $cours->setNiveau($niveau);
        $cours->setDuree((int)$duree);
        $cours->setDescription($description);
        $cours->setMots($motsString);
        $cours->setImagesMots(implode(';', $imagesMots));
        
        $em->persist($cours);
        $em->flush();
        
        $this->addFlash('success', 'Cours enregistré avec succès');
        return $this->redirectToRoute('admin_cours_list');
    }
    
    #[Route('/admin/cours/{id}/edit', name: 'admin_cours_edit_json', methods: ['GET'])]
    public function editJson(Cours $cours): JsonResponse
    {
        return $this->json([
            'idCours' => $cours->getIdCours(),
            'titre' => $cours->getTitre(),
            'typeCours' => $cours->getTypeCours(),
            'niveau' => $cours->getNiveau(),
            'duree' => $cours->getDuree(),
            'description' => $cours->getDescription(),
            'image' => $cours->getImage(),
            'mots' => $cours->getMots(),
            'imagesMots' => $cours->getImagesMots()
        ]);
    }
    
    #[Route('/admin/cours/{id}/delete', name: 'admin_cours_delete', methods: ['DELETE'])]
    public function delete(Cours $cours, EntityManagerInterface $em): JsonResponse
    {
        try {
           
            $uploadDir = $this->getParameter('kernel.project_dir') . self::UPLOAD_DIR;
            
          
            if ($cours->getImage()) {
                $imagePath = $uploadDir . '/' . $cours->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            
            if ($cours->getImagesMots()) {
                $imagesMots = explode(';', $cours->getImagesMots());
                foreach ($imagesMots as $imageMot) {
                    $parts = explode(':', $imageMot);
                    if (isset($parts[1])) {
                        $images = explode(',', $parts[1]);
                        foreach ($images as $image) {
                            $imagePath = $uploadDir . '/' . $image;
                            if (file_exists($imagePath)) {
                                unlink($imagePath);
                            }
                        }
                    }
                }
            }
            
            $em->remove($cours);
            $em->flush();
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}