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

   

    #[Route('/cours', name: 'app_cours')]
    public function index(CoursRepository $coursRepository): Response
    {
        $courses = $coursRepository->findAll();

        return $this->render('front/cours/index.html.twig', [
            'courses' => $courses,
        ]);
    }

    #[Route('/cours/{id}', name: 'app_cours_show', requirements: ['id' => '\d+'])]
    public function show(Cours $cours): Response
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

        return $this->render('front/cours/show.html.twig', [
            'cours'          => $cours,
            'motsList'       => $motsList,
            'motsCount'      => $cours->getMotsCount(),
            'motsWithImages' => $motsWithImages,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // ROUTES ADMIN
    // ─────────────────────────────────────────────────────────────────

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

        if (empty($titre) || empty($typeCours) || empty($niveau) || empty($duree) || empty($description)) {
            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires');
            return $this->redirectToRoute('admin_cours_list');
        }

        // ── Traitement des mots ──────────────────────────────────────
        // Le formulaire envoie mots[] (un mot par carte)
        $mots = $request->request->all('mots');
        $motsArray = [];
        if ($mots && is_array($mots)) {
            foreach ($mots as $mot) {
                $mot = strtoupper(trim($mot));
                if (!empty($mot)) {
                    $motsArray[] = $mot;
                }
            }
        }
        $motsString = implode(';', $motsArray);

        // ── Image principale du cours ────────────────────────────────
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

       
        $existingImagesByMot = $cours->getImagesByMot(); // retourne [mot => [img1, img2, ...]]

        $imagesMots = [];

        foreach ($motsArray as $index => $mot) {
            $mot = strtoupper(trim($mot));
            if (empty($mot)) {
                continue;
            }

            $motImages = [];

            // a) Images existantes conservées (envoyées via champs hidden existing_images_<idx>[])
            $keptExisting = $request->request->all('existing_images_' . $index);
            if ($keptExisting && is_array($keptExisting)) {
                foreach ($keptExisting as $img) {
                    $img = trim($img);
                    if (!empty($img)) {
                        $motImages[] = $img;
                    }
                }
            } else {
                // Si aucun champ hidden présent, on garde les images associées au même mot en BDD
                // (compatibilité : cas où le JS n'a pas encore injecté les hidden fields)
                if (isset($existingImagesByMot[$mot])) {
                    $motImages = array_merge($motImages, $existingImagesByMot[$mot]);
                }
            }

            // b) Nouveaux fichiers uploadés
            $files = $request->files->get('images_files_' . $index);
            if ($files && is_array($files)) {
                foreach ($files as $file) {
                    if ($file instanceof UploadedFile) {
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

            // c) Sauvegarder même si vide (pour conserver la liste des mots)
            if (!empty($motImages)) {
                $imagesMots[] = $mot . ':' . implode(',', $motImages);
            }
            // Si pas d'images du tout pour ce mot, on n'ajoute pas de ligne
            // (le mot est quand même sauvegardé dans $motsString)
        }

        $cours->setTitre($titre);
        $cours->setTypeCours($typeCours);
        $cours->setNiveau($niveau);
        $cours->setDuree((int) $duree);
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