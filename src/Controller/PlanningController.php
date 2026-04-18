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
	private function getCurrentUserId(): ?int
	{
		$currentUser = $this->getUser();

		if (is_object($currentUser) && method_exists($currentUser, 'getId')) {
			$id = $currentUser->getId();

			return is_numeric($id) ? (int) $id : null;
		}

		return null;
	}

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

		return in_array($normalized, ['planifiee', 'planifie', 'confirmee', 'confirme', 'active', 'disponible', 'reporte'], true);
	}

	private function formatRdv(Rdv $rdv, ?int $currentUserId = null, ?string $day = null, ?string $time = null): array
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
		$consultationType = (string) ($rdv->getTypeConsultation() ?? 'Consultation');
		$status = (string) ($rdv->getStatutRdv() ?? 'Inconnu');
		$professorId = $rdv->getIdPsychologue();
		$patientId = $rdv->getIdAutiste();
		$canHost = $currentUserId !== null && $professorId !== null && $currentUserId === (int) $professorId;
		$isPatient = $currentUserId !== null && $patientId !== null && $currentUserId === (int) $patientId;

		// Titre lisible du type de consultation
		$typeLabel = match ($this->normalizeForCompare($consultationType)) {
			'premiereconsultation' => 'Première consultation',
			'suivi' => 'Suivi psychologique',
			'urgence' => 'Consultation urgente',
			'familiale' => 'Consultation familiale',
			'bilan' => 'Bilan psychologique',
			default => ucfirst(str_replace('_', ' ', $consultationType)),
		};

		return [
			'id' => 'r' . $rdv->getId(),
			'day' => $day,
			'time' => $time,
			'title' => $typeLabel,
			'type' => 'psychologue',
			'specialty' => 'Rendez-vous psychologique',
			'therapist' => 'Psychologue',
			'professorId' => $professorId,
			'patientId' => $patientId,
			'description' => 'Statut: ' . ucfirst($status) . ' — Durée: ' . ($rdv->getDureeRdvMinutes() ?? '?') . ' min',
			'icon' => '👩‍⚕️',
			'available' => $this->isAvailableStatus($status),
			'zoomJoinUrl' => $rdv->getZoomJoinUrl(),
			'zoomStartUrl' => $canHost ? $rdv->getZoomStartUrl() : null,
			'isHost' => $canHost,
			'isPatient' => $isPatient,
			'level' => 'Sur rendez-vous',
		];
	}

	private function formatSeance(Seance $seance, ?int $currentUserId = null, ?string $day = null, ?string $time = null): array
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
		$professorId = $seance->getIdProfesseur();
		$canHost = $currentUserId !== null && $professorId !== null && $currentUserId === (int) $professorId;

		return [
			'id' => 's' . $seance->getId(),
			'day' => $day,
			'time' => $time,
			'title' => ucfirst($seance->getTitreSeance() ?? 'Seance'),
			'type' => 'cours',
			'specialty' => 'Professeur',
			'therapist' => 'Professeur',
			'professorId' => $professorId,
			'description' => $seance->getDescription() ?? 'Aucune description',
			'icon' => '👨‍🏫',
			'available' => $this->isAvailableStatus($status),
			'zoomJoinUrl' => $seance->getZoomJoinUrl(),
			'zoomStartUrl' => $canHost ? $seance->getZoomStartUrl() : null,
			'level' => 'Tous niveaux',
		];
	}

	private function buildPlanningSessions(EntityManagerInterface $em): array
	{
		$currentUserId = $this->getCurrentUserId();
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
					$sessions[] = $this->formatRdv($rdv, $currentUserId, $day, $time);
				}
			} elseif ($emploi->getIdSeance()) {
				$seance = $seanceRepo->find($emploi->getIdSeance());
				if ($seance) {
					$sessions[] = $this->formatSeance($seance, $currentUserId, $day, $time);
				}
			}
		}

		if (count($sessions) === 0) {
			foreach ($rdvRepo->findBy([], ['dateHeureRdv' => 'ASC']) as $rdv) {
				$sessions[] = $this->formatRdv($rdv, $currentUserId);
			}

			foreach ($seanceRepo->findBy([], ['dateSeance' => 'ASC']) as $seance) {
				$sessions[] = $this->formatSeance($seance, $currentUserId);
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
		$currentUserId = $this->getCurrentUserId();
		$rdvs = $em->getRepository(Rdv::class)->findBy([], ['dateHeureRdv' => 'ASC']);
		$sessions = [];

		foreach ($rdvs as $rdv) {
			$sessions[] = $this->formatRdv($rdv, $currentUserId);
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
		$currentUserId = $this->getCurrentUserId();
		$seances = $em->getRepository(Seance::class)->findBy([], ['dateSeance' => 'ASC']);
		$latestSeance = $em->getRepository(Seance::class)->findOneBy([], ['updatedAt' => 'DESC']);
		$latestSeanceDetails = $em->getConnection()->fetchAssociative(
			'SELECT
				s.id_seance,
				s.titre_seance,
				s.date_seance,
				s.jours_semaine,
				s.duree,
				s.created_at,
				s.updated_at,
				CONCAT(COALESCE(enfant.prenom, \'\'), \' \', COALESCE(enfant.nom, \'\')) AS enfant_nom,
				CONCAT(COALESCE(prof.prenom, \'\'), \' \', COALESCE(prof.nom, \'\')) AS professeur_nom,
				c.titre AS cours_titre
			FROM seance s
			LEFT JOIN user enfant ON enfant.id = s.id_autiste
			LEFT JOIN user prof ON prof.id = s.id_professeur
			LEFT JOIN cours c ON c.id_cours = s.id_cours
			ORDER BY s.updated_at DESC
			LIMIT 1'
		);
		$sessions = [];

		foreach ($seances as $seance) {
			$sessions[] = $this->formatSeance($seance, $currentUserId);
		}

		$stats = [
			['icon' => 'fas fa-chalkboard-teacher', 'color' => 'primary', 'value' => count($sessions), 'label' => 'Total Seances'],
			['icon' => 'fas fa-check', 'color' => 'success', 'value' => count(array_filter($sessions, fn (array $s) => $s['available'])), 'label' => 'Seances planifiees'],
			['icon' => 'fas fa-times', 'color' => 'danger', 'value' => count(array_filter($sessions, fn (array $s) => !$s['available'])), 'label' => 'Annulees/Passees'],
			['icon' => 'fas fa-calendar-week', 'color' => 'secondary', 'value' => count(array_unique(array_map(fn (array $s) => $s['day'], $sessions))), 'label' => 'Jours actifs'],
		];

		$latestSeanceNotification = null;

		if ($latestSeance instanceof Seance) {
			$dateSeance = $latestSeance->getDateSeance();
			$dayLabel = (string) ($latestSeance->getJoursSemaine() ?? ($dateSeance ? $dateSeance->format('l') : 'jour non precise'));
			$dateLabel = $dateSeance ? $dateSeance->format('d/m/Y') : 'date non precisee';
			$timeLabel = $dateSeance ? $dateSeance->format('H:i') : 'heure non precisee';
			$durationLabel = $latestSeance->getDuree() ? sprintf('%d min', (int) $latestSeance->getDuree()) : 'duree non precisee';
			$createdAt = $latestSeance->getCreatedAt();
			$updatedAt = $latestSeance->getUpdatedAt();
			$isModified = $createdAt instanceof \DateTimeInterface
				&& $updatedAt instanceof \DateTimeInterface
				&& $updatedAt->getTimestamp() > ($createdAt->getTimestamp() + 5);

			$childName = trim((string) ($latestSeanceDetails['enfant_nom'] ?? ''));
			$childName = $childName !== '' ? $childName : 'votre enfant';

			$professorName = trim((string) ($latestSeanceDetails['professeur_nom'] ?? ''));
			$professorName = $professorName !== '' ? $professorName : 'professeur non precise';

			$courseName = trim((string) ($latestSeanceDetails['cours_titre'] ?? ''));
			$courseName = $courseName !== '' ? $courseName : (string) ($latestSeance->getTitreSeance() ?? 'Seance pedagogique');

			$parentGreeting = 'Cher Parent';
			$currentUser = $this->getUser();

			if (is_object($currentUser) && method_exists($currentUser, 'getPrenom') && method_exists($currentUser, 'getNom')) {
				$prenom = trim((string) $currentUser->getPrenom());
				$nom = trim((string) $currentUser->getNom());
				$fullName = trim($prenom . ' ' . $nom);

				if ($fullName !== '') {
					$parentGreeting = 'Bonjour ' . $fullName;
				}
			}

			$latestSeanceNotification = [
				'id' => sprintf('%s-%d', (string) $latestSeance->getId(), $updatedAt?->getTimestamp() ?? time()),
				'title' => $isModified ? 'Mise a jour de seance' : 'Nouvelle seance planifiee',
				'message' => sprintf(
					$isModified
						? '%s, la seance de %s a ete modifiee: cours "%s", professeur %s, jour %s (%s), heure %s, duree %s. Merci de verifier les nouveaux details.'
						: '%s, %s a une nouvelle seance de "%s" avec %s le %s (%s) a %s. Duree prevue: %s.',
					$parentGreeting,
					$childName,
					$courseName,
					$professorName,
					$dayLabel,
					$dateLabel,
					$timeLabel,
					$durationLabel
				),
				'type' => $isModified ? 'warning' : 'info',
				'icon' => $isModified ? '⚠️' : 'ℹ️',
				'duration' => 60000,
			];
		}

		return $this->render('front/planning/index.html.twig', [
			'sessions' => $sessions,
			'stats' => $stats,
			'page_title' => 'Seances',
			'page_subtitle' => 'Decouvrez toutes les seances pedagogiques disponibles.',
			'latest_seance_notification' => $latestSeanceNotification,
		]);
	}
}
