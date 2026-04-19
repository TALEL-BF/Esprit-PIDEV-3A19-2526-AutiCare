<?php

namespace App\Controller;

use App\Repository\ParentUploadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/parent-uploads')]
class ParentUploadController extends AbstractController
{
    // ── Lister toutes les notifications (JSON pour la bell) ──────────────
    #[Route('/notifications', name: 'admin_parent_uploads_notifications', methods: ['GET'])]
    public function notifications(ParentUploadRepository $repo): JsonResponse
    {
        $uploads      = $repo->findUnseenRecent(10);
        $unseenCount  = $repo->countUnseen();

        $data = [];
        foreach ($uploads as $u) {
            $data[] = [
                'id'          => $u->getId(),
                'nomEnfant'   => $u->getNomEnfant(),
                'emailParent' => $u->getEmailParent(),
                'subject'     => $u->getSubject(),
                'fileName'    => $u->getFileName(),
                'uploadedAt'  => $u->getUploadedAt()?->format('d/m/Y H:i'),
                'seen'        => $u->isSeen(),
                'downloadUrl' => '/admin/parent-uploads/' . $u->getId() . '/download',
            ];
        }

        return $this->json([
            'count'   => $unseenCount,
            'uploads' => $data,
        ]);
    }

    // ── Marquer comme vu ─────────────────────────────────────────────────
    #[Route('/{id}/seen', name: 'admin_parent_uploads_seen', methods: ['POST'])]
    public function markSeen(
        int                    $id,
        ParentUploadRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $upload = $repo->find($id);
        if (!$upload) {
            return $this->json(['success' => false], 404);
        }
        $upload->setSeen(true);
        $em->flush();
        return $this->json(['success' => true]);
    }

    // ── Marquer tout comme vu ────────────────────────────────────────────
    #[Route('/mark-all-seen', name: 'admin_parent_uploads_mark_all_seen', methods: ['POST'])]
    public function markAllSeen(
        ParentUploadRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $unseen = $repo->findBy(['seen' => false]);
        foreach ($unseen as $u) {
            $u->setSeen(true);
        }
        $em->flush();
        return $this->json(['success' => true]);
    }

    // ── Télécharger le fichier ───────────────────────────────────────────
    #[Route('/{id}/download', name: 'admin_parent_uploads_download', methods: ['GET'])]
    public function download(
        int                    $id,
        ParentUploadRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $upload = $repo->find($id);
        if (!$upload) {
            throw $this->createNotFoundException('Document introuvable');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public' . $upload->getFilePath();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable sur le serveur');
        }

        // Marquer comme vu lors du téléchargement
        $upload->setSeen(true);
        $em->flush();

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $upload->getFileName()
        );

        return $response;
    }
}