<?php

namespace App\Controller;

use App\Entity\EmploiDuTemps;
use App\Form\EmploiDuTempsType;
use App\Repository\EmploiDuTempsRepository;
use App\Repository\RdvRepository;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminPlanningController extends AbstractController
{
    #[Route('/admin/planning', name: 'admin_planning')]
    public function planning(
        Request $request,
        EntityManagerInterface $entityManager,
        EmploiDuTempsRepository $emploiDuTempsRepository,
        RdvRepository $rdvRepository,
        SeanceRepository $seanceRepository
    ): Response {
        $emploi = new EmploiDuTemps();
        $currentSchoolYear = $this->getCurrentSchoolYear();
        $emploi->setAnneeScolaire($currentSchoolYear);
        $form = $this->createForm(EmploiDuTempsType::class, $emploi, array_merge(
            $this->getEmploiFormOptions($rdvRepository, $seanceRepository),
            ['is_create' => true]
        ));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emploi->setAnneeScolaire($currentSchoolYear);
            $this->applyEmploiSelection($form, $emploi);

            if (!$this->isEmploiSelectionValid($emploi)) {
                $form->addError(new FormError('Vous devez specifier SOIT un RDV SOIT une Seance (pas les deux en meme temps).'));
            } else {
                $entityManager->persist($emploi);
                $entityManager->flush();
                $this->addFlash('success', 'Ligne d\'emploi du temps ajoutee avec succes.');

                return $this->redirectToRoute('admin_planning');
            }
        }

        $emplois = $emploiDuTempsRepository->findAllByNewest();

        return $this->render('admin/pages/planning.html.twig', [
            'form' => $form->createView(),
            'emplois' => $emplois,
            'isEdit' => false,
            'entity' => null,
            'currentSchoolYear' => $currentSchoolYear,
        ]);
    }

    #[Route('/admin/planning/{id}/edit', name: 'admin_planning_edit', requirements: ['id' => '\\d+'])]
    public function editPlanning(
        Request $request,
        EmploiDuTemps $emploi,
        EntityManagerInterface $entityManager,
        EmploiDuTempsRepository $emploiDuTempsRepository,
        RdvRepository $rdvRepository,
        SeanceRepository $seanceRepository
    ): Response {
        $currentSchoolYear = $this->getCurrentSchoolYear();
        $emploi->setAnneeScolaire($currentSchoolYear);
        $formOptions = $this->getEmploiFormOptions($rdvRepository, $seanceRepository);
        $formOptions['data'] = $emploi;

        $form = $this->createForm(EmploiDuTempsType::class, $emploi, $formOptions);
        $form->get('rdvSelection')->setData($emploi->getIdRdv());
        $form->get('seanceSelection')->setData($emploi->getIdSeance());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emploi->setAnneeScolaire($currentSchoolYear);
            $this->applyEmploiSelection($form, $emploi);

            if (!$this->isEmploiSelectionValid($emploi)) {
                $form->addError(new FormError('Vous devez specifier SOIT un RDV SOIT une Seance (pas les deux en meme temps).'));
            } else {
                $entityManager->flush();
                $this->addFlash('success', 'Ligne d\'emploi du temps modifiee avec succes.');

                return $this->redirectToRoute('admin_planning');
            }
        }

        $emplois = $emploiDuTempsRepository->findAllByNewest();

        return $this->render('admin/pages/planning.html.twig', [
            'form' => $form->createView(),
            'emplois' => $emplois,
            'isEdit' => true,
            'entity' => $emploi,
            'currentSchoolYear' => $currentSchoolYear,
        ]);
    }

    #[Route('/admin/planning/{id}/delete', name: 'admin_planning_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function deletePlanning(Request $request, EmploiDuTemps $emploi, EntityManagerInterface $entityManager): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete_emploi_'.$emploi->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($emploi);
            $entityManager->flush();
            $this->addFlash('success', 'Ligne d\'emploi du temps supprimee avec succes.');
        }

        return $this->redirectToRoute('admin_planning');
    }

    private function getEmploiFormOptions(RdvRepository $rdvRepository, SeanceRepository $seanceRepository): array
    {
        $rdvs = $rdvRepository->findAllByNewest();
        $seances = $seanceRepository->findAllByNewest();

        $rdvChoices = [];
        foreach ($rdvs as $rdv) {
            $label = sprintf(
                'RDV - %s - %s',
                $rdv->getTypeConsultation(),
                $rdv->getDateHeureRdv()?->format('d/m/Y H:i') ?? 'n/a'
            );
            $rdvChoices[$label] = $rdv->getId();
        }

        $seanceChoices = [];
        foreach ($seances as $seance) {
            $label = sprintf(
                'Seance - %s - %s',
                $seance->getTitreSeance(),
                $seance->getDateSeance()?->format('d/m/Y H:i') ?? 'n/a'
            );
            $seanceChoices[$label] = $seance->getId();
        }

        return [
            'rdv_choices' => $rdvChoices,
            'seance_choices' => $seanceChoices,
        ];
    }

   
