<?php

namespace App\Controller;

use App\Entity\Rdv;
use App\Form\RdvType;
use App\Repository\RdvRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminRdvController extends AbstractController
{
    #[Route('/admin/rdv', name: 'admin_rdv')]
    public function rdv(Request $request, EntityManagerInterface $entityManager, RdvRepository $rdvRepository): Response
    {
        $rdv = new Rdv();
        $form = $this->createForm(RdvType::class, $rdv, ['is_create' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($rdv);
            $entityManager->flush();
            $this->addFlash('success', 'RDV ajoute avec succes.');

            return $this->redirectToRoute('admin_rdv');
        }

        $rdvs = $rdvRepository->findAllByNewest();

        return $this->render('admin/pages/rdv.html.twig', [
            'form' => $form->createView(),
            'rdvs' => $rdvs,
            'isEdit' => false,
            'entity' => null,
        ]);
    }

    #[Route('/admin/rdv/{id}/edit', name: 'admin_rdv_edit', requirements: ['id' => '\\d+'])]
    public function editRdv(Request $request, Rdv $rdv, EntityManagerInterface $entityManager, RdvRepository $rdvRepository): Response
    {
        $form = $this->createForm(RdvType::class, $rdv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'RDV modifie avec succes.');

            return $this->redirectToRoute('admin_rdv');
        }

        $rdvs = $rdvRepository->findAllByNewest();

        return $this->render('admin/pages/rdv.html.twig', [
            'form' => $form->createView(),
            'rdvs' => $rdvs,
            'isEdit' => true,
            'entity' => $rdv,
        ]);
    }

    #[Route('/admin/rdv/{id}/delete', name: 'admin_rdv_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function deleteRdv(Request $request, Rdv $rdv, EntityManagerInterface $entityManager): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete_rdv_'.$rdv->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($rdv);
            $entityManager->flush();
            $this->addFlash('success', 'RDV supprime avec succes.');
        } else {
            $this->addFlash('warning', 'Suppression RDV refusee: jeton de securite invalide.');
        }

        return $this->redirectToRoute('admin_rdv');
    }
}
