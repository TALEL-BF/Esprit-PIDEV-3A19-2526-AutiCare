<?php

namespace App\Controller;

use App\Entity\Seance;
use App\Form\SeanceType;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminSeanceController extends AbstractController
{
    // ✅ ROUTE POUR CONSULTATIONS (ancien template seance.html.twig)
    #[Route('/admin/seance', name: 'admin_seance')]
    public function seance(Request $request, EntityManagerInterface $entityManager, SeanceRepository $seanceRepository): Response
    {
        $seance = new Seance();
        $form = $this->createForm(SeanceType::class, $seance, ['is_create' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($seance);
            $entityManager->flush();
            $this->addFlash('success', 'Seance ajoutee avec succes.');

            return $this->redirectToRoute('admin_seance');
        }

        $seances = $seanceRepository->findAllByNewest();

        // 📌 Template pour CONSULTATIONS (ancienne version)
        return $this->render('admin/pages/seance.html.twig', [
            'form' => $form->createView(),
            'seances' => $seances,
            'isEdit' => false,
            'entity' => null,
        ]);
    }

    // ✅ ROUTE POUR EMPLOIS (nouveau template seancep.html.twig)
   

    // ✅ MODIFICATION - redirige vers EMPLOIS
    #[Route('/admin/seance/{id}/edit', name: 'admin_seance_edit', requirements: ['id' => '\\d+'])]
    public function editSeance(Request $request, Seance $seance, EntityManagerInterface $entityManager, SeanceRepository $seanceRepository): Response
    {
        $form = $this->createForm(SeanceType::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Seance modifiee avec succes.');

            return $this->redirectToRoute('admin_seance_emploi');
        }

        $seances = $seanceRepository->findAllByNewest();

        return $this->render('admin/pages/seancep.html.twig', [
            'form' => $form->createView(),
            'seances' => $seances,
            'isEdit' => true,
            'entity' => $seance,
        ]);
    }

    // ✅ SUPPRESSION - redirige vers EMPLOIS
    #[Route('/admin/seance/{id}/delete', name: 'admin_seance_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function deleteSeance(Request $request, Seance $seance, EntityManagerInterface $entityManager): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete_seance_'.$seance->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($seance);
            $entityManager->flush();
            $this->addFlash('success', 'Seance supprimee avec succes.');
        }

        return $this->redirectToRoute('admin_seance_emploi');
    }
    #[Route('/admin/seance-emploi', name: 'admin_seance_emploi')]
public function seanceEmploi(Request $request, EntityManagerInterface $entityManager, SeanceRepository $seanceRepository): Response
{
    $seance = new Seance();
    $form = $this->createForm(SeanceType::class, $seance, ['is_create' => true]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($seance);
        $entityManager->flush();
        $this->addFlash('success', 'Seance ajoutee avec succes.');

        return $this->redirectToRoute('admin_seance_emploi');
    }

    $seances = $seanceRepository->findAllByNewest();

    return $this->render('admin/pages/seancep.html.twig', [
        'form' => $form->createView(),
        'seances' => $seances,
        'isEdit' => false,
        'entity' => null,
    ]);
}
}