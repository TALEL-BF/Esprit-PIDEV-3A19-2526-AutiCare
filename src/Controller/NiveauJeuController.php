<?php

namespace App\Controller;

use App\Entity\NiveauJeu;
use App\Form\NiveauJeuType;
use App\Repository\NiveauJeuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/niveau/jeu')]
final class NiveauJeuController extends AbstractController
{
    #[Route(name: 'app_niveau_jeu_index', methods: ['GET'])]
    public function index(NiveauJeuRepository $niveauJeuRepository): Response
    {
        return $this->render('niveau_jeu/index.html.twig', [
            'niveau_jeus' => $niveauJeuRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_niveau_jeu_new', methods: ['GET', 'POST'])]
    #[Route('/new', name: 'app_niveau_jeu_new', methods: ['GET', 'POST'])]
public function new(Request $request, NiveauJeuRepository $repo): Response
{
    $niveauJeu = new NiveauJeu();
    $form = $this->createForm(NiveauJeuType::class, $niveauJeu);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $repo->save($niveauJeu, true);

        return $this->redirectToRoute('app_niveau_jeu_index');
    }

    return $this->render('niveau_jeu/new.html.twig', [
        'form' => $form->createView(),
        'niveau_jeus' => $repo->findAll(), // ✅ AJOUTE ÇA
    ]);
}

    #[Route('/{id}', name: 'app_niveau_jeu_show', methods: ['GET'])]
    public function show(NiveauJeu $niveauJeu): Response
    {
        return $this->render('niveau_jeu/show.html.twig', [
            'niveau_jeu' => $niveauJeu,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_niveau_jeu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, NiveauJeu $niveauJeu, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NiveauJeuType::class, $niveauJeu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_niveau_jeu_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('niveau_jeu/edit.html.twig', [
            'niveau_jeu' => $niveauJeu,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_niveau_jeu_delete', methods: ['POST'])]
    public function delete(Request $request, NiveauJeu $niveauJeu, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$niveauJeu->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($niveauJeu);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_niveau_jeu_index', [], Response::HTTP_SEE_OTHER);
    }
}
