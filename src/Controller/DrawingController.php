<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DrawingController extends AbstractController
{
    #[Route('/espace-dessin', name: 'app_drawing')]
    public function index(): Response
    {
        return $this->render('front/cours/drawing.html.twig');
    }
    
    #[Route('/espace-dessin/save', name: 'app_drawing_save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $imageData = $data['image'] ?? null;
        
        if ($imageData) {
            // Enlever le préfixe base64
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageData = base64_decode($imageData);
            
            if ($imageData === false) {
                return $this->json(['success' => false, 'error' => 'Invalid image data'], 400);
            }
            
            $filename = 'drawing_' . uniqid() . '.png';
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/drawings';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filepath = $uploadDir . '/' . $filename;
            file_put_contents($filepath, $imageData);
            
            return $this->json([
                'success' => true, 
                'filename' => $filename,
                'url' => '/uploads/drawings/' . $filename
            ]);
        }
        
        return $this->json(['success' => false, 'error' => 'No image data received'], 400);
    }
    
    #[Route('/espace-dessin/load/{filename}', name: 'app_drawing_load', methods: ['GET'])]
    public function load(string $filename): Response
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/drawings';
        $filepath = $uploadDir . '/' . $filename;
        
        if (file_exists($filepath)) {
            $imageData = file_get_contents($filepath);
            $base64 = base64_encode($imageData);
            return $this->json([
                'success' => true,
                'image' => 'data:image/png;base64,' . $base64
            ]);
        }
        
        return $this->json(['success' => false, 'error' => 'File not found'], 404);
    }
    
    #[Route('/espace-dessin/list', name: 'app_drawing_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/drawings';
        $drawings = [];
        
        if (is_dir($uploadDir)) {
            $files = scandir($uploadDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'png') {
                    $drawings[] = [
                        'filename' => $file,
                        'url' => '/uploads/drawings/' . $file,
                        'date' => date('Y-m-d H:i:s', filemtime($uploadDir . '/' . $file))
                    ];
                }
            }
            // Trier par date décroissante
            usort($drawings, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
        }
        
        return $this->json(['success' => true, 'drawings' => $drawings]);
    }
    
    #[Route('/espace-dessin/delete/{filename}', name: 'app_drawing_delete', methods: ['DELETE'])]
    public function delete(string $filename): JsonResponse
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/drawings';
        $filepath = $uploadDir . '/' . $filename;
        
        // Sécurité : empêcher la suppression de fichiers en dehors du dossier
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            return $this->json(['success' => false, 'error' => 'Invalid filename'], 400);
        }
        
        if (file_exists($filepath)) {
            unlink($filepath);
            return $this->json(['success' => true]);
        }
        
        return $this->json(['success' => false, 'error' => 'File not found'], 404);
    }
}