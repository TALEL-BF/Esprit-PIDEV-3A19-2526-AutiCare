<?php

namespace App\Controller;

use App\Entity\SuivieEntity;
use App\Repository\SuivieEntityRepository;
use App\Repository\TherapieEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/seance')]
class SuivieController extends AbstractController
{
    #[Route('', name: 'admin_seance', methods: ['GET'])]
    public function index(SuivieEntityRepository $repo, TherapieEntityRepository $therapieRepo): Response
    {
        // 🔥 FIX IMPORTANT (JOIN)
        $sessions = $repo->createQueryBuilder('s')
            ->leftJoin('s.therapie', 't')
            ->addSelect('t')
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/pages/seance.html.twig', [
            'sessions'  => $sessions,
            'therapies' => $therapieRepo->findAll()
        ]);
    }

    #[Route('/new', name: 'seance_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, TherapieEntityRepository $tr): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $s = new SuivieEntity();
        $this->hydrate($s, $data, $tr);

        $em->persist($s);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Ajout réussi']);
    }

    #[Route('/{id}/edit', name: 'seance_edit', methods: ['POST'])]
    public function edit($id, Request $request, SuivieEntityRepository $repo, EntityManagerInterface $em, TherapieEntityRepository $tr): JsonResponse
    {
        $s = $repo->find($id);
        if (!$s) return $this->json(['success'=>false]);

        $data = json_decode($request->getContent(), true);
        $this->hydrate($s, $data, $tr);

        $em->flush();

        return $this->json(['success'=>true,'message'=>'Modifié']);
    }

    #[Route('/{id}/delete', name: 'seance_delete', methods: ['POST'])]
    public function delete($id, EntityManagerInterface $em, SuivieEntityRepository $repo): JsonResponse
    {
        $s = $repo->find($id);
        if (!$s) return $this->json(['success'=>false]);

        $em->remove($s);
        $em->flush();

        return $this->json(['success'=>true]);
    }

    private function hydrate($s, $d, $repo)
    {
        $s->setNomEnfant($d['nomEnfant']);
        $s->setNomPsy($d['nomPsy']);
        $s->setScoreHumeur($d['scoreHumeur']);
        $s->setScoreStress($d['scoreStress']);
        $s->setScoreAttention($d['scoreAttention']);
        $s->setComportement($d['comportement']);
        $s->setInteractionSociale($d['interactionSociale']);
        $s->setStatut($d['statut']);
        $s->setObservation($d['observation']);

        if (!empty($d['date'])) {
            $s->setDateSuivie(new \DateTime($d['date'].'T'.$d['time']));
        }

        if (!empty($d['therapieId'])) {
            $s->setTherapie($repo->find($d['therapieId']));
        }
    }
}

