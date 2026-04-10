<?php

namespace App\Controller;

use App\Entity\ArticleEntity;
use App\Repository\ArticleEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/article')]
final class ArticleController extends AbstractController
{
    
    #[Route('', name: 'admin_article', methods: ['GET'])]
    public function index(ArticleEntityRepository $repo): Response
    {
        $articles = $repo->findAll();
        $total    = count($articles);

        $cats = [];
        $totalLikes = 0;
        foreach ($articles as $a) {
            $cat = $a->getCategorie() ?? 'Autre';
            $cats[$cat] = ($cats[$cat] ?? 0) + 1;
            $totalLikes += (int) $a->getLikesCount();
        }
        arsort($cats);

       
        $auteurs = array_unique(array_map(fn($a) => $a->getAuteur(), $articles));

        return $this->render('admin/pages/article.html.twig', [
            'articles'    => $articles,
            'total'       => $total,
            'totalLikes'  => $totalLikes,
            'cats'        => $cats,
            'nbAuteurs'   => count($auteurs),
            'topCat'      => array_key_first($cats) ?? '—',
        ]);
    }

    
    #[Route('/new', name: 'admin_article_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $a = new ArticleEntity();
            $this->hydrate($a, $data);
            $a->setDateCreation(new \DateTime());
            $a->setLikesCount(0);
            $em->persist($a);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Article créé avec succès', 'id' => $a->getId()]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // ── SHOW ─────────────────────────────────────────────────────────────
    #[Route('/show/{id}', name: 'admin_article_show', methods: ['GET'])]
    public function show(ArticleEntity $a): JsonResponse
    {
        return $this->json($this->serialize($a));
    }

    // ── EDIT ─────────────────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'admin_article_edit', methods: ['POST'])]
    public function edit(Request $request, ArticleEntity $a, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $this->hydrate($a, $data);
            $em->flush();
            return $this->json(['success' => true, 'message' => 'Article modifié avec succès']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

   
    #[Route('/{id}/delete', name: 'admin_article_delete', methods: ['POST'])]
    public function delete(Request $request, ArticleEntity $a, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('delete' . $a->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'message' => 'Token CSRF invalide'], 403);
        }
        $em->remove($a);
        $em->flush();
        return $this->json(['success' => true, 'message' => 'Article supprimé']);
    }

    // ── HELPERS ──────────────────────────────────────────────────────────
    private function hydrate(ArticleEntity $a, array $d): void
    {
        if (isset($d['titre']))     $a->setTitre(trim($d['titre']));
        if (isset($d['contenu']))   $a->setContenu(trim($d['contenu']));
        if (isset($d['categorie'])) $a->setCategorie(trim($d['categorie']));
        if (isset($d['auteur']))    $a->setAuteur(trim($d['auteur']));

        if (isset($d['auteurImage']) && $d['auteurImage']) {
            $img = $d['auteurImage'];
            // Si c'est une image base64, on la sauvegarde dans /public/uploads/auteurs/
            if (str_starts_with($img, 'data:image')) {
                $img = $this->saveBase64Image($img);
            }
            $a->setAuteurImage($img ?: null);
        }
    }

    private function saveBase64Image(string $base64): ?string
    {
        try {
            // Extraire le type MIME et les données binaires
            $parts = explode(',', $base64, 2);
            if (count($parts) !== 2) return null;

            $header = $parts[0]; // ex: "data:image/jpeg;base64"
            $data   = base64_decode($parts[1]);
            if (!$data) return null;

            // Extraire l'extension depuis le header
            $ext = 'jpg';
            if (str_contains($header, 'image/png'))  $ext = 'png';
            if (str_contains($header, 'image/gif'))  $ext = 'gif';
            if (str_contains($header, 'image/webp')) $ext = 'webp';

            // Dossier de destination
            $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/auteurs/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Nom de fichier unique
            $filename = uniqid('auteur_') . '.' . $ext;
            file_put_contents($dir . $filename, $data);

            return '/uploads/auteurs/' . $filename;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function serialize(ArticleEntity $a): array
    {
        return [
            'id'          => $a->getId(),
            'titre'       => $a->getTitre(),
            'contenu'     => $a->getContenu(),
            'categorie'   => $a->getCategorie(),
            'auteur'      => $a->getAuteur(),
            'auteurImage' => $a->getAuteurImage(),
            'likesCount'  => $a->getLikesCount(),
            'dateCreation'=> $a->getDateCreation()?->format('d/m/Y H:i'),
        ];
    }
}
