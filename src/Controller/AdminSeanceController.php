<?php

namespace App\Controller;

use App\Entity\Seance;
use App\Form\SeanceType;
use App\Repository\SeanceRepository;
use App\Service\WindowsNotificationService;
use App\Service\WebNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminSeanceController extends AbstractController
{
    #[Route('/admin/seance', name: 'admin_seance')]
    public function seance(Request $request, EntityManagerInterface $entityManager, SeanceRepository $seanceRepository, WindowsNotificationService $windowsNotifier, WebNotificationService $webNotifier): Response
    {
        $seance = new Seance();
        $form = $this->createForm(SeanceType::class, $seance, $this->buildSeanceFormOptions($entityManager, true));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($seance);
            $entityManager->flush();
            
            // Notification système Windows (admin)
            $windowsNotifier->notifyAdminNewSeance($seance);
            
            // Notification web (admin)
            $webData = $webNotifier->prepareNotificationData('info', $seance, 'create');
            $webDataJSON = $webNotifier->toJSON($webData);
            
            $this->addFlash('success', 'Seance ajoutee avec succes.');
            $this->addFlash('notification_web', $webDataJSON);

            return $this->redirectToRoute('admin_seance');
        }

        $seances = $seanceRepository->findAllByNewest();

        return $this->render('admin/pages/seance.html.twig', [
            'form' => $form->createView(),
            'seances' => $seances,
            'isEdit' => false,
            'entity' => null,
        ]);
    }

    #[Route('/admin/seance/{id}/edit', name: 'admin_seance_edit', requirements: ['id' => '\\d+'])]
    public function editSeance(Request $request, Seance $seance, EntityManagerInterface $entityManager, SeanceRepository $seanceRepository, WindowsNotificationService $windowsNotifier, WebNotificationService $webNotifier): Response
    {
        $form = $this->createForm(SeanceType::class, $seance, $this->buildSeanceFormOptions($entityManager));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            // Notification système Windows (admin)
            $windowsNotifier->notifyAdminSeanceUpdate($seance);
            
            // Notification web (admin)
            $webData = $webNotifier->prepareNotificationData('info', $seance, 'update');
            $webDataJSON = $webNotifier->toJSON($webData);
            
            $this->addFlash('success', 'Seance modifiee avec succes.');
            $this->addFlash('notification_web', $webDataJSON);

            return $this->redirectToRoute('admin_seance');
        }

        $seances = $seanceRepository->findAllByNewest();

        return $this->render('admin/pages/seance.html.twig', [
            'form' => $form->createView(),
            'seances' => $seances,
            'isEdit' => true,
            'entity' => $seance,
        ]);
    }

    #[Route('/admin/seance/{id}/delete', name: 'admin_seance_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function deleteSeance(Request $request, Seance $seance, EntityManagerInterface $entityManager): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete_seance_'.$seance->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($seance);
            $entityManager->flush();
            $this->addFlash('success', 'Seance supprimee avec succes.');
        } else {
            $this->addFlash('warning', 'Suppression seance refusee: jeton de securite invalide.');
        }

        return $this->redirectToRoute('admin_seance');
    }

    private function buildSeanceFormOptions(EntityManagerInterface $entityManager, bool $isCreate = false): array
    {
        return [
            'is_create' => $isCreate,
            'patient_choices' => $this->fetchUserChoicesByRoles($entityManager, ['enfant']),
            'professor_choices' => $this->fetchUserChoicesByRoles($entityManager, ['professeur']),
            'course_choices' => $this->fetchCourseChoices($entityManager),
        ];
    }

    /**
     * @param string[] $roles
     */
    private function fetchUserChoicesByRoles(EntityManagerInterface $entityManager, array $roles): array
    {
        $connection = $entityManager->getConnection();
        $rows = $connection->fetchAllAssociative(
            'SELECT id, nom, prenom, role
             FROM user
             WHERE role IN (:roles)
             ORDER BY nom ASC, prenom ASC',
            ['roles' => $roles],
            ['roles' => \Doctrine\DBAL\ArrayParameterType::STRING]
        );

        $choices = [];

        foreach ($rows as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $prenom = trim((string) ($row['prenom'] ?? ''));
            $nom = trim((string) ($row['nom'] ?? ''));
            $role = trim((string) ($row['role'] ?? ''));
            $displayName = trim($prenom . ' ' . $nom);
            $displayName = $displayName !== '' ? $displayName : ('Utilisateur #' . $id);
            $label = $displayName;

            $choices[$label] = $id;
        }

        return $choices;
    }

    private function fetchCourseChoices(EntityManagerInterface $entityManager): array
    {
        $rows = $entityManager->getConnection()->fetchAllAssociative(
            'SELECT id_cours, titre
             FROM cours
             ORDER BY titre ASC'
        );

        $choices = [];

        foreach ($rows as $row) {
            $id = (string) ($row['id_cours'] ?? '');
            if ($id === '') {
                continue;
            }

            $title = trim((string) ($row['titre'] ?? ''));
            $label = $title !== '' ? $title : ('Cours #' . $id);
            $choices[$label] = $id;
        }

        return $choices;
    }
}
