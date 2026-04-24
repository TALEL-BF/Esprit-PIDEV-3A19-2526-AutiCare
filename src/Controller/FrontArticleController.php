<?php

namespace App\Controller;

use App\Repository\ArticleEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontArticleController extends AbstractController
{
    // ── PAGE PRINCIPALE ─────────────────────────────────────────────────
    #[Route('/articles', name: 'app_articles')]
    public function index(ArticleEntityRepository $repo, Request $request): Response
    {
        $articles = $repo->findBy([], ['dateCreation' => 'DESC']);

        // Articles en co-tendance = tous ceux ayant le score max de likes
        $trendingList = [];
        if ($articles) {
            $maxLikes = max(array_map(fn($a) => $a->getLikesCount(), $articles));
            if ($maxLikes > 0) {
                $trendingList = array_values(array_filter(
                    $articles,
                    fn($a) => $a->getLikesCount() === $maxLikes
                ));
                // Tri par date décroissante pour un ordre stable
                usort($trendingList, fn($a, $b) =>
                    ($b->getDateCreation()?->getTimestamp() ?? 0) <=>
                    ($a->getDateCreation()?->getTimestamp() ?? 0)
                );
            }
        }

        // Catégories uniques
        $categories = array_unique(array_map(fn($a) => $a->getCategorie(), $articles));
        sort($categories);

        // Total likes
        $totalLikes = array_sum(array_map(fn($a) => $a->getLikesCount(), $articles));

        // Articles déjà likés par la session
        $likedIds = $request->getSession()->get('liked_articles', []);

        return $this->render('front/article/index.html.twig', [
            'articles'   => $articles,
            'trendingList' => $trendingList,
            'categories' => $categories,
            'totalLikes' => $totalLikes,
            'likedIds'   => $likedIds,
        ]);
    }

    // ── LIKE TOGGLE ────────────────────────────────────────────────────
    #[Route('/article/{id}/like', name: 'app_article_like', methods: ['POST'])]
    public function like(int $id, ArticleEntityRepository $repo,
                         EntityManagerInterface $em, Request $request): JsonResponse
    {
        $article = $repo->find($id);
        if (!$article) {
            return $this->json(['success' => false], 404);
        }

        $session   = $request->getSession();
        $likedIds  = $session->get('liked_articles', []);
        $isLiked   = isset($likedIds[$id]);

        if ($isLiked) {
            // Unlike
            $article->setLikesCount(max(0, $article->getLikesCount() - 1));
            unset($likedIds[$id]);
        } else {
            // Like
            $article->setLikesCount($article->getLikesCount() + 1);
            $likedIds[$id] = true;
        }

        $session->set('liked_articles', $likedIds);
        $em->flush();

        return $this->json([
            'success' => true,
            'liked'   => !$isLiked,
            'likes'   => $article->getLikesCount(),
        ]);
    }
}
