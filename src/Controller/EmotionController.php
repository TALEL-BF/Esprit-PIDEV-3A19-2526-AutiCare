<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmotionController extends AbstractController
{
    #[Route('/emotion/analysis', name: 'emotion_analysis')]
    public function analysis(): Response
    {
        return $this->render('emotion/analysis.html.twig');
    }
}