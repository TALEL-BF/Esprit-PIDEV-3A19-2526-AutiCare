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

    /**
     * Liste tous les cours (front)
     */
    #[Route('/cours', name: 'app_cours')]
    public function index(CoursRepository $coursRepository): Response
    {
        $courses = $coursRepository->findAll();

        return $this->render('front/cours/index.html.twig', [
            'courses' => $courses,
        ]);
    }

    /**
     * Affiche le détail d'un cours avec ses mots et images (front)
     */
    #[Route('/cours/{id}', name: 'app_cours_show', requirements: ['id' => '\d+'])]
    public function show(Cours $cours): Response
    {
        // Préparer les données pour le template
        $motsList = $cours->getMotsArray();
        $imagesByMot = $cours->getImagesByMot();
        
        // Construire un tableau complet pour chaque mot avec son image
        $motsWithImages = [];
        foreach ($motsList as $mot) {
            $motsWithImages[] = [
                'nom' => $mot,
                'image' => $imagesByMot[$mot][0] ?? null, // Première image associée au mot
                'toutes_images' => $imagesByMot[$mot] ?? [],
            ];
        }
        
        return $this->render('front/cours/show.html.twig', [
            'cours' => $cours,
            'motsList' => $motsList,
            'motsCount' => $cours->getMotsCount(),
            'motsWithImages' => $motsWithImages,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // ROUTES ADMIN (reste identique)
    // ─────────────────────────────────────────────────────────────────

    #[Route('/admin/cours', name: 'admin_cours_list')]
    public function adminIndex(CoursRepository $coursRepository): Response
    {
        $cours = $coursRepository->findAll();

        $totalMots = 0;
        foreach ($cours as $c) {
            if ($c->getMots()) {
                $totalMots += count(explode(';', $c->getMots()));
            }
        }

        return $this->render('admin/pages/cours.html.twig', [
            'cours_list'      => $cours,
            'totalCourses'    => count($cours),
            'publishedCount'  => count($cours),
            'draftCount'      => 0,
            'totalMots'       => $totalMots,
        ]);
    }

    #[Route('/admin/cours/save', name: 'admin_cours_save', methods: ['POST'])]
    public function save(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        CoursRepository $coursRepository
    ): Response {
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

        $uploadDir = $this->getParameter('kernel.project_dir') . self::UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $titre       = $request->request->get('titre');
        $typeCours   = $request->request->get('type_cours');
        $niveau      = $request->request->get('niveau');
        $duree       = $request->request->get('duree');
        $description = $request->request->get('description');
        $mots        = $request->request->all('mots');

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

        // Image du cours
        $imageFile = $request->files->get('image_file');
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename     = $slugger->slug($originalFilename);
            $newFilename      = 'cours_' . $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
            try {
                $imageFile->move($uploadDir, $newFilename);
                $cours->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image du cours');
            }
        }

        // Images des mots
        $imagesMots = [];
        $imgMots    = $request->request->all('img_mots');

        if ($imgMots && is_array($imgMots)) {
            foreach ($imgMots as $index => $mot) {
                $mot = trim($mot);
                if (!empty($mot)) {
                    $files = $request->files->get('images_files_' . $index);
                    if ($files && is_array($files)) {
                        $motImages = [];
                        foreach ($files as $file) {
                            if ($file instanceof UploadedFile) {
                                $filename    = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
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
            'idCours'    => $cours->getIdCours(),
            'titre'      => $cours->getTitre(),
            'typeCours'  => $cours->getTypeCours(),
            'niveau'     => $cours->getNiveau(),
            'duree'      => $cours->getDuree(),
            'description'=> $cours->getDescription(),
            'image'      => $cours->getImage(),
            'mots'       => $cours->getMots(),
            'imagesMots' => $cours->getImagesMots(),
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
                foreach (explode(';', $cours->getImagesMots()) as $imageMot) {
                    $parts = explode(':', $imageMot);
                    if (isset($parts[1])) {
                        foreach (explode(',', $parts[1]) as $image) {
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