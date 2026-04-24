<?php

namespace App\Controller;

use App\Entity\SuiviTotal;
use App\Form\SuiviTotalType;
use App\Repository\SuiviTotalRepository;
use App\Service\NiveauResolverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/suivi/total')]
final class SuiviTotalController extends AbstractController
{
    #[Route(name: 'app_suivi_total_index', methods: ['GET'])]
    public function index(SuiviTotalRepository $suiviTotalRepository): Response
    {
        return $this->render('suivi_total/index.html.twig', [
            'suivi_totals' => $suiviTotalRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_suivi_total_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        NiveauResolverService $niveauResolverService
    ): Response {
        $suiviTotal = new SuiviTotal();
        $form = $this->createForm(SuiviTotalType::class, $suiviTotal);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            // La moyenne est calculée automatiquement dans l'entité
            $suiviTotal->calculerMoyenneGenerale();
            $niveau = $niveauResolverService->findByMoyenne((float) $suiviTotal->getMoyenne());

            if ($niveau !== null) {
                $suiviTotal->setNiveauJeu($niveau);
            } else {
                $this->addFlash('danger', 'Aucun niveau ne correspond à cette moyenne.');
                return $this->render('suivi_total/new.html.twig', [
                    'suivi_total' => $suiviTotal,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($suiviTotal);
            $entityManager->flush();

            $this->addFlash('success', 'Le suivi a été ajouté avec succès.');
            return $this->redirectToRoute('app_suivi_total_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('suivi_total/new.html.twig', [
            'suivi_total' => $suiviTotal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_suivi_total_show', methods: ['GET'])]
    public function show(SuiviTotal $suiviTotal): Response
    {
        return $this->render('suivi_total/show.html.twig', [
            'suivi_total' => $suiviTotal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_suivi_total_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        SuiviTotal $suiviTotal,
        EntityManagerInterface $entityManager,
        NiveauResolverService $niveauResolverService
    ): Response {
        $form = $this->createForm(SuiviTotalType::class, $suiviTotal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $niveau = $niveauResolverService->findByMoyenne((float) $suiviTotal->getMoyenne());

            if ($niveau !== null) {
                $suiviTotal->setNiveauJeu($niveau);
            } else {
                $this->addFlash('danger', 'Aucun niveau ne correspond à cette moyenne.');
                return $this->render('suivi_total/edit.html.twig', [
                    'suivi_total' => $suiviTotal,
                    'form' => $form,
                ]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le suivi a été modifié avec succès.');
            return $this->redirectToRoute('app_suivi_total_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('suivi_total/edit.html.twig', [
            'suivi_total' => $suiviTotal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_suivi_total_delete', methods: ['POST'])]
    public function delete(Request $request, SuiviTotal $suiviTotal, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $suiviTotal->getId(), $request->request->get('_token'))) {
            $entityManager->remove($suiviTotal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_suivi_total_index', [], Response::HTTP_SEE_OTHER);
    }
}