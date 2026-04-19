<?php

namespace App\Controller;

use App\Entity\ForumPost;
use App\Entity\ForumReponse;
use App\Repository\ForumPostRepository;
use App\Repository\ForumReponseRepository;
use App\Services\BadWordsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ForumController extends AbstractController
{
    public function __construct(
        private BadWordsService      $badWords,
        private EntityManagerInterface $em
    ) {}

    // ══════════════════════════════════════════════════════════════════════
    // FRONT-OFFICE — Parents
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Page forum front (dans suivi thérapeutique)
     * GET /forum
     */
    #[Route('/forum', name: 'front_forum', methods: ['GET'])]
    public function index(ForumPostRepository $repo): Response
    {
        $posts      = $repo->findActifs();
        $categories = ['Conseils', 'Questions', 'Témoignages', 'Activités', 'Général'];

        return $this->render('front/forum/index.html.twig', [
            'posts'      => $posts,
            'categories' => $categories,
            'totalPosts' => count($posts),
        ]);
    }

    /**
     * Créer un nouveau post (parent)
     * POST /forum/post
     */
    #[Route('/forum/post', name: 'forum_create_post', methods: ['POST'])]
    public function createPost(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $contenuBrut = trim($data['contenu']    ?? '');
        $auteur      = trim($data['auteur']      ?? 'Parent anonyme');
        $nomEnfant   = trim($data['nomEnfant']   ?? '') ?: null;
        $categorie   = trim($data['categorie']   ?? 'Général');

        if (empty($contenuBrut)) {
            return $this->json(['success' => false, 'message' => 'Le message ne peut pas être vide.'], 400);
        }

        if (mb_strlen($contenuBrut) > 1000) {
            return $this->json(['success' => false, 'message' => 'Message trop long (max 1000 caractères).'], 400);
        }

        // ── Filtrage bad words ─────────────────────────────────────────
        $filterResult   = $this->badWords->filter($contenuBrut);
        $contenuFiltre  = $filterResult['contenu'];
        $nbCensures     = $filterResult['nbMotsCensures'];

        $post = new ForumPost();
        $post->setAuteur($auteur);
        $post->setAuteurType('parent');
        $post->setNomEnfant($nomEnfant);
        $post->setContenuOriginal($contenuBrut);
        $post->setContenu($contenuFiltre);
        $post->setNbMotsCensures($nbCensures);
        $post->setCategorie($categorie);
        $post->setStatut('actif');

        $this->em->persist($post);
        $this->em->flush();

        $message = 'Message publié avec succès !';
        if ($nbCensures > 0) {
            $message = "Message publié. {$nbCensures} mot(s) inapproprié(s) ont été masqués.";
        }

        return $this->json([
            'success'        => true,
            'message'        => $message,
            'nbMotsCensures' => $nbCensures,
            'post'           => $this->serializePost($post),
        ]);
    }

    /**
     * Répondre à un post (parent)
     * POST /forum/post/{id}/reponse
     */
    #[Route('/forum/post/{id}/reponse', name: 'forum_create_reponse', methods: ['POST'])]
    public function createReponse(int $id, Request $request, ForumPostRepository $repo): JsonResponse
    {
        $post = $repo->find($id);
        if (!$post || $post->getStatut() !== 'actif') {
            return $this->json(['success' => false, 'message' => 'Post introuvable.'], 404);
        }

        $data        = json_decode($request->getContent(), true);
        $contenuBrut = trim($data['contenu'] ?? '');
        $auteur      = trim($data['auteur']  ?? 'Parent anonyme');
        $auteurType  = $data['auteurType']   ?? 'parent';

        if (empty($contenuBrut)) {
            return $this->json(['success' => false, 'message' => 'La réponse ne peut pas être vide.'], 400);
        }

        // ── Filtrage bad words ─────────────────────────────────────────
        $filterResult  = $this->badWords->filter($contenuBrut);
        $contenuFiltre = $filterResult['contenu'];
        $nbCensures    = $filterResult['nbMotsCensures'];

        $reponse = new ForumReponse();
        $reponse->setPost($post);
        $reponse->setAuteur($auteur);
        $reponse->setAuteurType($auteurType);
        $reponse->setContenuOriginal($contenuBrut);
        $reponse->setContenu($contenuFiltre);
        $reponse->setNbMotsCensures($nbCensures);

        $this->em->persist($reponse);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Réponse publiée !',
            'reponse' => $this->serializeReponse($reponse),
        ]);
    }

    /**
     * Charger les posts (AJAX)
     * GET /forum/posts
     */
    #[Route('/forum/posts', name: 'forum_get_posts', methods: ['GET'])]
    public function getPosts(Request $request, ForumPostRepository $repo): JsonResponse
    {
        $categorie = $request->query->get('categorie');

        $posts = $categorie && $categorie !== 'tous'
            ? $repo->findByCategorie($categorie)
            : $repo->findActifs();

        return $this->json([
            'success' => true,
            'posts'   => array_map(fn($p) => $this->serializePost($p), $posts),
        ]);
    }

    /**
     * Charger les réponses d'un post
     * GET /forum/post/{id}/reponses
     */
    #[Route('/forum/post/{id}/reponses', name: 'forum_get_reponses', methods: ['GET'])]
    public function getReponses(int $id, ForumPostRepository $repo): JsonResponse
    {
        $post = $repo->find($id);
        if (!$post) {
            return $this->json(['success' => false], 404);
        }

        $reponses = array_map(fn($r) => $this->serializeReponse($r), $post->getReponses()->toArray());

        return $this->json(['success' => true, 'reponses' => $reponses]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // BACKOFFICE — Psy
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Page forum admin (côté séance)
     * GET /admin/forum
     */
    #[Route('/admin/forum', name: 'admin_forum', methods: ['GET'])]
    public function adminIndex(
        ForumPostRepository    $postRepo,
        ForumReponseRepository $reponseRepo
    ): Response {
        $posts = $postRepo->findAll();

        return $this->render('admin/pages/forum.html.twig', [
            'posts'          => $posts,
            'totalPosts'     => count($posts),
            'totalReponses'  => $reponseRepo->countTotal(),
            'totalSignales'  => $postRepo->countSignales(),
            'totalActifs'    => $postRepo->countActifs(),
        ]);
    }

    /**
     * Le psy répond à un post
     * POST /admin/forum/post/{id}/reponse
     */
    #[Route('/admin/forum/post/{id}/reponse', name: 'admin_forum_repondre', methods: ['POST'])]
    public function psyRepondre(int $id, Request $request, ForumPostRepository $repo): JsonResponse
    {
        $post = $repo->find($id);
        if (!$post) {
            return $this->json(['success' => false, 'message' => 'Post introuvable.'], 404);
        }

        $data        = json_decode($request->getContent(), true);
        $contenuBrut = trim($data['contenu'] ?? '');
        $auteur      = trim($data['auteur']  ?? 'Équipe AutiCare');

        if (empty($contenuBrut)) {
            return $this->json(['success' => false, 'message' => 'La réponse ne peut pas être vide.'], 400);
        }

        $reponse = new ForumReponse();
        $reponse->setPost($post);
        $reponse->setAuteur($auteur);
        $reponse->setAuteurType('psy');
        $reponse->setContenuOriginal($contenuBrut);
        $reponse->setContenu($contenuBrut); // Pas de filtre pour le psy
        $reponse->setNbMotsCensures(0);

        $this->em->persist($reponse);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Réponse publiée avec succès.',
            'reponse' => $this->serializeReponse($reponse),
        ]);
    }

    /**
     * Masquer / Restaurer un post (psy)
     * POST /admin/forum/post/{id}/toggle-statut
     */
    #[Route('/admin/forum/post/{id}/toggle-statut', name: 'admin_forum_toggle', methods: ['POST'])]
    public function toggleStatut(int $id, ForumPostRepository $repo): JsonResponse
    {
        $post = $repo->find($id);
        if (!$post) {
            return $this->json(['success' => false, 'message' => 'Post introuvable.'], 404);
        }

        $newStatut = $post->getStatut() === 'actif' ? 'masque' : 'actif';
        $post->setStatut($newStatut);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'statut'  => $newStatut,
            'message' => $newStatut === 'masque' ? 'Post masqué.' : 'Post restauré.',
        ]);
    }

    /**
     * Supprimer un post (psy)
     * POST /admin/forum/post/{id}/delete
     */
    #[Route('/admin/forum/post/{id}/delete', name: 'admin_forum_delete', methods: ['POST'])]
    public function deletePost(int $id, ForumPostRepository $repo): JsonResponse
    {
        $post = $repo->find($id);
        if (!$post) {
            return $this->json(['success' => false, 'message' => 'Post introuvable.'], 404);
        }

        $this->em->remove($post);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Post supprimé.']);
    }

    /**
     * Supprimer une réponse (psy)
     * POST /admin/forum/reponse/{id}/delete
     */
    #[Route('/admin/forum/reponse/{id}/delete', name: 'admin_forum_delete_reponse', methods: ['POST'])]
    public function deleteReponse(int $id, ForumReponseRepository $repo): JsonResponse
    {
        $reponse = $repo->find($id);
        if (!$reponse) {
            return $this->json(['success' => false, 'message' => 'Réponse introuvable.'], 404);
        }

        $this->em->remove($reponse);
        $this->em->flush();

        return $this->json(['success' => true, 'message' => 'Réponse supprimée.']);
    }

    // ── Serializers ───────────────────────────────────────────────────────

    private function serializePost(ForumPost $p): array
    {
        return [
            'id'             => $p->getId(),
            'auteur'         => $p->getAuteur(),
            'auteurType'     => $p->getAuteurType(),
            'nomEnfant'      => $p->getNomEnfant(),
            'contenu'        => $p->getContenu(),
            'nbMotsCensures' => $p->getNbMotsCensures(),
            'statut'         => $p->getStatut(),
            'categorie'      => $p->getCategorie() ?? 'Général',
            'createdAt'      => $p->getCreatedAt()->format('d/m/Y à H:i'),
            'nbReponses'     => $p->getReponses()->count(),
        ];
    }

    private function serializeReponse(ForumReponse $r): array
    {
        return [
            'id'         => $r->getId(),
            'auteur'     => $r->getAuteur(),
            'auteurType' => $r->getAuteurType(),
            'contenu'    => $r->getContenu(),
            'createdAt'  => $r->getCreatedAt()->format('d/m/Y à H:i'),
        ];
    }
}
