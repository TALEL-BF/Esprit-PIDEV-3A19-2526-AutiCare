<?php

namespace App\Controller;

use App\Entity\EmploiDuTemps;
use App\Entity\Rdv;
use App\Entity\Seance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlanningController extends AbstractController
{
	private function getDaysMap(): array
	{
		return [
			0 => 'Dimanche',
			1 => 'Lundi',
			2 => 'Mardi',
			3 => 'Mercredi',
			4 => 'Jeudi',
			5 => 'Vendredi',
			6 => 'Samedi',
		];
	}

	private function normalizeForCompare(?string $value): string
	{
		$normalized = strtolower(trim((string) $value));
		$ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);

		if (is_string($ascii) && $ascii !== '') {
			$normalized = $ascii;
		}

		return str_replace([' ', '-', '_'], '', $normalized);
	}

	private function normalizeDay(?string $day): string
	{
		$normalized = $this->normalizeForCompare($day);

		return match ($normalized) {
			'lundi', 'monday' => 'Lundi',
			'mardi', 'tuesday' => 'Mardi',
			'mercredi', 'wednesday' => 'Mercredi',
			'jeudi', 'thursday' => 'Jeudi',
			'vendredi', 'friday' => 'Vendredi',
			'samedi', 'saturday' => 'Samedi',
			'dimanche', 'sunday' => 'Dimanche',
			default => 'Lundi',
		};
	}

	private function mapTrancheToTime(?string $tranche): string
	{
		$normalized = $this->normalizeForCompare($tranche);

		return match ($normalized) {
			'matin' => '09h00',
			'apresmidi', 'apresmidis' => '14h00',
			'soir' => '18h00',
			'journee' => '09h00 - 17h00',
			default => '09h00',
		};
	}

	private function isAvailableStatus(?string $status): bool
	{
		$normalized = $this->normalizeForCompare($status);

		return in_array($normalized, ['planifiee', 'confirmee', 'active', 'disponible'], true);
	}

	private function formatRdv(Rdv $rdv, ?string $day = null, ?string $time = null): array
	{
		$date = $rdv->getDateHeureRdv();
		$days = $this->getDaysMap();

		if (!$day) {
			$day = $date ? ($days[(int) $date->format('w')] ?? 'Lundi') : 'Lundi';
		}

		if (!$time) {
			$time = $date ? $date->format('H\\hi') : '10h00';
		}

		$day = $this->normalizeDay($day);
		$consultationType = (string) ($rdv->getTypeConsultation() ?? 'Psychologue');
		$status = (string) ($rdv->getStatutRdv() ?? 'Inconnu');

		return [
			'id' => 'r' . $rdv->getId(),
			'day' => $day,
			'time' => $time,
			'title' => 'Consultation ' . ucfirst($consultationType),
			'type' => 'psychologue',
			'specialty' => ucfirst($consultationType),
			'therapist' => 'Psychologue',
			'description' => 'Statut: ' . ucfirst($status),
			'icon' => '👩‍⚕️',
			'available' => $this->isAvailableStatus($status),
			'level' => 'Sur rendez-vous',
		];
	}

	private function formatSeance(Seance $seance, ?string $day = null, ?string $time = null): array
	{
		$date = $seance->getDateSeance();

		if (!$day) {
			$day = $seance->getJoursSemaine() ?: 'Mardi';
		}

		if (!$time) {
			$time = $date ? $date->format('H\\hi') : '14h00';
		}

		$day = $this->normalizeDay($day);
		$status = (string) ($seance->getStatutSeance() ?? 'Inconnu');

		return [
			'id' => 's' . $seance->getId(),
			'day' => $day,
			'time' => $time,
			'title' => ucfirst($seance->getTitreSeance() ?? 'Seance'),
			'type' => 'cours',
			'specialty' => 'Professeur',
			'therapist' => 'Professeur',
			'description' => $seance->getDescription() ?? 'Aucune description',
			'icon' => '👨‍🏫',
			'available' => $this->isAvailableStatus($status),
			'level' => 'Tous niveaux',
		];
	}

	private function buildPlanningSessions(EntityManagerInterface $em): array
	{
		$emplois = $em->getRepository(EmploiDuTemps::class)->findAll();
		$rdvRepo = $em->getRepository(Rdv::class);
		$seanceRepo = $em->getRepository(Seance::class);
		$sessions = [];

		foreach ($emplois as $emploi) {
			$day = $this->normalizeDay($emploi->getJourSemaine());
			$time = $this->mapTrancheToTime($emploi->getTrancheHoraire());

			if ($emploi->getIdRdv()) {
				$rdv = $rdvRepo->find($emploi->getIdRdv());
				if ($rdv) {
					$sessions[] = $this->formatRdv($rdv, $day, $time);
				}
			} elseif ($emploi->getIdSeance()) {
				$seance = $seanceRepo->find($emploi->getIdSeance());
				if ($seance) {
					$sessions[] = $this->formatSeance($seance, $day, $time);
				}
			}
		}

		if (count($sessions) === 0) {
			foreach ($rdvRepo->findBy([], ['dateHeureRdv' => 'ASC']) as $rdv) {
				$sessions[] = $this->formatRdv($rdv);
			}

			foreach ($seanceRepo->findBy([], ['dateSeance' => 'ASC']) as $seance) {
				$sessions[] = $this->formatSeance($seance);
			}
		}

		return $sessions;
	}

	#[Route('/planning', name: 'app_planning')]
	#[Route('/planning-global', name: 'app_front_planning_global')]
	public function planning(EntityManagerInterface $em): Response
	{
		$sessions = $this->buildPlanningSessions($em);

		$stats = [
			['icon' => 'fas fa-calendar-alt', 'color' => 'primary', 'value' => count($sessions), 'label' => 'Total Creneaux'],
			['icon' => 'fas fa-user-md', 'color' => 'secondary', 'value' => count(array_filter($sessions, fn (array $s) => $s['type'] === 'psychologue')), 'label' => 'RDV'],
			['icon' => 'fas fa-chalkboard-teacher', 'color' => 'primary', 'value' => count(array_filter($sessions, fn (array $s) => $s['type'] === 'cours')), 'label' => 'Seances'],
		];

		return $this->render('front/planning/index.html.twig', [
			'sessions' => $sessions,
			'stats' => $stats,
			'page_title' => 'Planning Global',
			'page_subtitle' => 'Emploi du temps des rendez-vous et des seances interactives.',
		]);
	}

	#[Route('/rdv', name: 'app_rdv')]
	#[Route('/rdv-front', name: 'app_front_rdv_list')]
	public function rdv(EntityManagerInterface $em): Response
	{
		$rdvs = $em->getRepository(Rdv::class)->findBy([], ['dateHeureRdv' => 'ASC']);
		$sessions = [];

		foreach ($rdvs as $rdv) {
			$sessions[] = $this->formatRdv($rdv);
		}

		$stats = [
			['icon' => 'fas fa-user-md', 'color' => 'primary', 'value' => count($sessions), 'label' => 'Total Consultations'],
			['icon' => 'fas fa-check', 'color' => 'success', 'value' => count(array_filter($sessions, fn (array $s) => $s['available'])), 'label' => 'Planifiees'],
			['icon' => 'fas fa-times', 'color' => 'danger', 'value' => count(array_filter($sessions, fn (array $s) => !$s['available'])), 'label' => 'Indisponibles'],
			['icon' => 'fas fa-calendar-week', 'color' => 'secondary', 'value' => count(array_unique(array_map(fn (array $s) => $s['day'], $sessions))), 'label' => 'Jours actifs'],
		];

		return $this->render('front/planning/index.html.twig', [
			'sessions' => $sessions,
			'stats' => $stats,
			'page_title' => 'Rendez-vous',
			'page_subtitle' => 'Gerez et consultez tous vos rendez-vous de therapie.',
		]);
	}

	#[Route('/seance', name: 'app_seance')]
	#[Route('/seance-front', name: 'app_front_seance_list')]
	public function seance(EntityManagerInterface $em): Response
	{
		$seances = $em->getRepository(Seance::class)->findBy([], ['dateSeance' => 'ASC']);
		$sessions = [];

		foreach ($seances as $seance) {
			$sessions[] = $this->formatSeance($seance);
		}

		$stats = [
			['icon' => 'fas fa-chalkboard-teacher', 'color' => 'primary', 'value' => count($sessions), 'label' => 'Total Seances'],
			['icon' => 'fas fa-check', 'color' => 'success', 'value' => count(array_filter($sessions, fn (array $s) => $s['available'])), 'label' => 'Seances planifiees'],
			['icon' => 'fas fa-times', 'color' => 'danger', 'value' => count(array_filter($sessions, fn (array $s) => !$s['available'])), 'label' => 'Annulees/Passees'],
			['icon' => 'fas fa-calendar-week', 'color' => 'secondary', 'value' => count(array_unique(array_map(fn (array $s) => $s['day'], $sessions))), 'label' => 'Jours actifs'],
		];

		return $this->render('front/planning/index.html.twig', [
			'sessions' => $sessions,
			'stats' => $stats,
			'page_title' => 'Seances',
			'page_subtitle' => 'Decouvrez toutes les seances pedagogiques disponibles.',
		]);
	}
}
