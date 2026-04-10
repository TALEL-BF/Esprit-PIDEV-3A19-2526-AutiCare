<?php

namespace App\Controller;

use App\Service\StabilityImageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageGenerationController extends AbstractController
{
    #[Route('/admin/image/generate', name: 'admin_image_generate', methods: ['POST'])]
    public function generateImage(
        Request $request,
        StabilityImageService $imageService,
        SluggerInterface $slugger
    ): JsonResponse {
        $prompt = $request->request->get('prompt');
        
        if (!$prompt || strlen(trim($prompt)) < 3) {
            return $this->json(['error' => 'Le prompt doit contenir au moins 3 caractères'], 400);
        }
        
        $mode = $request->request->get('mode', 'standard');
        
        if ($mode === 'rapide') {
            $base64Image = $imageService->genererImageRapide($prompt);
        } else {
            $base64Image = $imageService->genererImage($prompt);
        }
        
        if (!$base64Image) {
            return $this->json(['error' => 'Erreur lors de la génération de l\'image'], 500);
        }
        
        // Sauvegarder l'image temporairement
        $imageData = base64_decode($base64Image);
        $tempDir = $this->getParameter('kernel.project_dir') . '/public/uploads/temp';
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $filename = 'ia_' . $slugger->slug(substr($prompt, 0, 30)) . '_' . uniqid() . '.png';
        $filepath = $tempDir . '/' . $filename;
        file_put_contents($filepath, $imageData);
        
        return $this->json([
            'success' => true,
            'imageUrl' => '/uploads/temp/' . $filename,
            'filename' => $filename
        ]);
    }
}