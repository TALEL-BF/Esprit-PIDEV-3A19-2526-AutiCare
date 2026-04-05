<?php
// src/Controller/CoursController.php

namespace App\Controller;

use App\Entity\Cours;
use App\Repository\CoursRepository;
use App\Repository\EvaluationRepository;
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

    #[Route('/cours', name: 'app_cours')]
    public function index(CoursRepository $coursRepository): Response
    {
        $courses = $coursRepository->findAll();

        return $this->render('front/cours/index.html.twig', [
            'courses' => $courses,
        ]);
    }

    #[Route('/cours/{id}', name: 'app_cours_show', requirements: ['id' => '\d+'])]
    public function show(Cours $cours, EvaluationRepository $evaluationRepository): Response
    {
        $motsList    = $cours->getMotsArray();
        $imagesByMot = $cours->getImagesByMot();

        $motsWithImages = [];
        foreach ($motsList as $mot) {
            $mot = strtoupper(trim($mot));
            if ($mot === '') continue;
            $toutesImages = array_values($imagesByMot[$mot] ?? []);
            $motsWithImages[] = [
                'nom'           => $mot,
                'image'         => $toutesImages[0] ?? null,
                'toutes_images' => $toutesImages,
            ];
        }

        $evaluations = $evaluationRepository->findByCoursId($cours->getIdCours());

        return $this->render('front/cours/show.html.twig', [
            'cours'          => $cours,
            'motsList'       => $motsList,
            'motsCount'      => $cours->getMotsCount(),
            'motsWithImages' => $motsWithImages,
            'evaluations'    => $evaluations,
            'hasQuiz'        => count($evaluations) > 0,
        ]);
    }

    #[Route('/cours/{id}/quiz', name: 'app_cours_quiz', requirements: ['id' => '\d+'])]
    public function quiz(Cours $cours, EvaluationRepository $evaluationRepository): Response
    {
        $evaluations = $evaluationRepository->findByCoursId($cours->getIdCours());

        if (empty($evaluations)) {
            $this->addFlash('info', 'Aucune question disponible pour ce cours.');
            return $this->redirectToRoute('app_cours_show', ['id' => $cours->getIdCours()]);
        }

        return $this->render('front/cours/quiz.html.twig', [
            'cours'       => $cours,
            'evaluations' => $evaluations,
        ]);
    }

    #[Route('/admin/cours', name: 'admin_cours_list')]
    public function adminIndex(CoursRepository $coursRepository): Response
    {
        $cours = $coursRepository->findAll();

        $totalMots = 0;
        foreach ($cours as $c) {
            if ($c->getMots()) {
                $totalMots += count(array_filter(explode(';', $c->getMots())));
            }
        }

        return $this->render('admin/pages/cours.html.twig', [
            'cours_list'     => $cours,
            'totalCourses'   => count($cours),
            'publishedCount' => count($cours),
            'draftCount'     => 0,
            'totalMots'      => $totalMots,
        ]);
    }

    #[Route('/admin/cours/save', name: 'admin_cours_save', methods: ['POST'])]
    public function save(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        CoursRepository $coursRepository
    ): Response {

        // Récupérer ou créer l'entité
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

        // ── Lecture des champs depuis la requête HTML ──
        $data        = $request->request->all('cours') ?: [];
        $titre       = trim($data['titre'] ?? '');
        $typeCours   = trim($data['typeCours'] ?? '');
        $niveau      = trim($data['niveau'] ?? '');
        $duree       = (int) ($data['duree'] ?? 0);
        $description = trim($data['description'] ?? '');

        // ── Validation manuelle ──
        $errors = [];
        if (strlen($titre) < 3) {
            $errors[] = 'Le titre est requis (minimum 3 caractères)';
        }
        if (!in_array($typeCours, ['Académique', 'Social', 'Motricité', 'Langage'], true)) {
            $errors[] = 'Veuillez sélectionner un type de cours valide';
        }
        if (!in_array($niveau, ['Débutant', 'Intermédiaire', 'Avancé'], true)) {
            $errors[] = 'Veuillez sélectionner un niveau valide';
        }
        if ($duree < 1 || $duree > 300) {
            $errors[] = 'La durée doit être comprise entre 1 et 300 minutes';
        }
        if (strlen($description) < 10) {
            $errors[] = 'La description est requise (minimum 10 caractères)';
        }

        // ── Validation des mots ──
        $mots      = $request->request->all('mots');
        $motsArray = [];
        if (is_array($mots)) {
            foreach ($mots as $mot) {
                $mot = strtoupper(trim($mot));
                if (!empty($mot)) {
                    $motsArray[] = $mot;
                }
            }
        }
        if (empty($motsArray)) {
            $errors[] = 'Ajoutez au moins un mot';
        }

        // ── Si erreurs : recharger la page avec les messages flash ──
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('admin_cours_list');
        }

        // ── Appliquer les valeurs sur l'entité ──
        $cours->setTitre($titre);
        $cours->setTypeCours($typeCours);
        $cours->setNiveau($niveau);
        $cours->setDuree($duree);
        $cours->setDescription($description);

        $uploadDir = $this->getParameter('kernel.project_dir') . self::UPLOAD_DIR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // ── Gestion de l'image du cours ──
        $removeImage = $request->request->get('remove_image') === '1';
        if ($removeImage && $cours->getImage() && $cours->getImage() !== 'default-course.jpg') {
            $oldImagePath = $uploadDir . '/' . $cours->getImage();
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
            $cours->setImage(null);
        }

        $filesData = $request->files->all('cours') ?: [];
        $imageFile = $filesData['image'] ?? null;

        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            if ($cours->getImage() && $cours->getImage() !== 'default-course.jpg') {
                $oldImagePath = $uploadDir . '/' . $cours->getImage();
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $safeFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
            $newFilename  = 'cours_' . $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
            try {
                $imageFile->move($uploadDir, $newFilename);
                $cours->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image du cours');
            }
        } elseif (!$cours->getImage()) {
            $cours->setImage('default-course.jpg');
        }

        // ── Gestion des images par mot ──
        $existingImagesByMot = $cours->getImagesByMot();
        $imagesMots          = [];

        foreach ($motsArray as $index => $mot) {
            $mot = strtoupper(trim($mot));
            if (empty($mot)) continue;

            $motImages = [];

            // Images existantes conservées
            $keptExisting = $request->request->all('existing_images_' . $index);
            if ($keptExisting && is_array($keptExisting)) {
                foreach ($keptExisting as $img) {
                    $img = trim($img);
                    if (!empty($img)) {
                        $motImages[] = $img;
                    }
                }
            } else {
                if (isset($existingImagesByMot[$mot])) {
                    $motImages = array_merge($motImages, $existingImagesByMot[$mot]);
                }
            }

            // Nouvelles images uploadées
            $files = $request->files->get('images_files_' . $index);
            if ($files && is_array($files)) {
                foreach ($files as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $filename    = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                        $newFilename = 'mot_' . $slugger->slug($mot) . '_' . $filename . '-' . uniqid() . '.' . $file->guessExtension();
                        try {
                            $file->move($uploadDir, $newFilename);
                            $motImages[] = $newFilename;
                        } catch (FileException $e) {
                            $this->addFlash('error', 'Erreur lors de l\'upload d\'une image pour le mot : ' . $mot);
                        }
                    }
                }
            }

            if (!empty($motImages)) {
                $imagesMots[] = $mot . ':' . implode(',', $motImages);
            }
        }

        $cours->setMots(implode(';', $motsArray));
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
            'idCours'     => $cours->getIdCours(),
            'titre'       => $cours->getTitre(),
            'typeCours'   => $cours->getTypeCours(),
            'niveau'      => $cours->getNiveau(),
            'duree'       => $cours->getDuree(),
            'description' => $cours->getDescription(),
            'image'       => $cours->getImage(),
            'mots'        => $cours->getMots(),
            'imagesMots'  => $cours->getImagesMots(),
        ]);
    }

    #[Route('/admin/cours/{id}/delete', name: 'admin_cours_delete', methods: ['DELETE'])]
    public function delete(Cours $cours, EntityManagerInterface $em): JsonResponse
    {
        try {
            $uploadDir = $this->getParameter('kernel.project_dir') . self::UPLOAD_DIR;

            if ($cours->getImage() && $cours->getImage() !== 'default-course.jpg') {
                $imagePath = $uploadDir . '/' . $cours->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            if ($cours->getImagesMots()) {
                foreach (explode(';', $cours->getImagesMots()) as $imageMot) {
                    $parts = explode(':', $imageMot, 2);
                    if (isset($parts[1])) {
                        foreach (explode(',', $parts[1]) as $image) {
                            $imagePath = $uploadDir . '/' . trim($image);
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