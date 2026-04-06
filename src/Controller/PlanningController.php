<?php

namespace App\Controller;

<<<<<<< HEAD
use App\Entity\EmploiDuTemps;
use App\Entity\Rdv;
use App\Entity\Seance;
use Doctrine\ORM\EntityManagerInterface;
=======
>>>>>>> Consultation
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlanningController extends AbstractController
{
<<<<<<< HEAD
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
=======
    #[Route('/planning', name: 'app_planning')]
    #[Route('/emplois', name: 'app_emplois')]
    public function index(): Response
    {
        // Données des sessions
        $sessions = [
            // Lundi - Cours
            ['id' => 1, 'day' => 'Lundi', 'time' => '09h00 - 10h00', 'title' => 'Communication Augmentée', 'type' => 'cours', 'specialty' => 'Orthophonie', 'therapist' => 'Mme. Sonia Trabelsi', 'description' => 'Apprentissage des outils de communication alternative (PECS, tablettes)', 'icon' => '👩‍🏫', 'available' => true, 'level' => 'Débutant'],
            ['id' => 2, 'day' => 'Lundi', 'time' => '10h30 - 11h30', 'title' => 'Motricité Fine', 'type' => 'cours', 'specialty' => 'Ergothérapie', 'therapist' => 'M. Karim Hamdi', 'description' => 'Exercices pour développer la coordination et la précision des gestes', 'icon' => '👨‍⚕️', 'available' => true, 'level' => 'Intermédiaire'],
            ['id' => 3, 'day' => 'Lundi', 'time' => '14h00 - 15h00', 'title' => 'Soutien Psychologique', 'type' => 'psychologue', 'specialty' => 'Psychologue TSA', 'therapist' => 'Dr. Amira Ben Ali', 'description' => 'Accompagnement émotionnel et gestion du stress', 'icon' => '👩‍⚕️', 'available' => true, 'level' => 'Tous niveaux'],
            ['id' => 4, 'day' => 'Lundi', 'time' => '15h30 - 16h30', 'title' => 'Gestion des Émotions', 'type' => 'psychologue', 'specialty' => 'Psychologue', 'therapist' => 'Dr. Rania Jebali', 'description' => 'Apprendre à identifier et exprimer ses émotions', 'icon' => '👩‍⚕️', 'available' => false, 'level' => 'Enfant'],

            // Mardi - Cours
            ['id' => 5, 'day' => 'Mardi', 'time' => '09h00 - 10h00', 'title' => 'Mathématiques Visuelles', 'type' => 'cours', 'specialty' => 'Pédagogie adaptée', 'therapist' => 'M. Yassine Bouzid', 'description' => 'Apprentissage des mathématiques par approche concrète et visuelle', 'icon' => '👨‍🏫', 'available' => true, 'level' => 'Intermédiaire'],
            ['id' => 6, 'day' => 'Mardi', 'time' => '10h30 - 11h30', 'title' => 'Habiletés Sociales', 'type' => 'cours', 'specialty' => 'Psychologie', 'therapist' => 'Dr. Lina Mansouri', 'description' => 'Développer les compétences sociales et la compréhension des codes', 'icon' => '👩‍🎨', 'available' => true, 'level' => 'Avancé'],
            ['id' => 7, 'day' => 'Mardi', 'time' => '14h00 - 15h00', 'title' => 'Thérapie ABA', 'type' => 'psychologue', 'specialty' => 'ABA Thérapeute', 'therapist' => 'Dr. Amira Ben Ali', 'description' => 'Séance de thérapie comportementale appliquée', 'icon' => '👩‍⚕️', 'available' => true, 'level' => 'Enfant'],
            ['id' => 8, 'day' => 'Mardi', 'time' => '16h00 - 17h00', 'title' => 'Consultation Parents', 'type' => 'psychologue', 'specialty' => 'Psychologue familial', 'therapist' => 'Dr. Rania Jebali', 'description' => 'Soutien et conseils pour les parents d\'enfants autistes', 'icon' => '👩‍⚕️', 'available' => true, 'level' => 'Parents'],

            // Mercredi - Cours
            ['id' => 9, 'day' => 'Mercredi', 'time' => '09h00 - 10h00', 'title' => 'Lecture et Écriture', 'type' => 'cours', 'specialty' => 'Orthophonie', 'therapist' => 'Mme. Sonia Trabelsi', 'description' => 'Méthodes adaptées pour apprendre à lire et écrire', 'icon' => '👩‍🏫', 'available' => true, 'level' => 'Débutant'],
            ['id' => 10, 'day' => 'Mercredi', 'time' => '10h30 - 11h30', 'title' => 'Gestion du Temps', 'type' => 'cours', 'specialty' => 'Ergothérapie', 'therapist' => 'M. Karim Hamdi', 'description' => 'Apprendre à organiser son emploi du temps et ses routines', 'icon' => '👨‍⚕️', 'available' => true, 'level' => 'Intermédiaire'],
            ['id' => 11, 'day' => 'Mercredi', 'time' => '13h30 - 14h30', 'title' => 'Art-thérapie', 'type' => 'cours', 'specialty' => 'Art-thérapie', 'therapist' => 'Dr. Lina Mansouri', 'description' => 'Expression créative par les arts plastiques', 'icon' => '👩‍🎨', 'available' => true, 'level' => 'Tous niveaux'],
            ['id' => 12, 'day' => 'Mercredi', 'time' => '15h00 - 16h00', 'title' => 'Bilan Psychologique', 'type' => 'psychologue', 'specialty' => 'Psychologue clinicien', 'therapist' => 'Dr. Amira Ben Ali', 'description' => 'Évaluation et suivi personnalisé', 'icon' => '👩‍⚕️', 'available' => false, 'level' => 'Sur rendez-vous'],

            // Jeudi - Cours
            ['id' => 13, 'day' => 'Jeudi', 'time' => '09h00 - 10h00', 'title' => 'Sciences Exploratoires', 'type' => 'cours', 'specialty' => 'Sciences', 'therapist' => 'M. Yassine Bouzid', 'description' => 'Découverte des sciences par l\'expérimentation sensorielle', 'icon' => '👨‍🏫', 'available' => true, 'level' => 'Enfant'],
            ['id' => 14, 'day' => 'Jeudi', 'time' => '10h30 - 11h30', 'title' => 'Musique et Rythme', 'type' => 'cours', 'specialty' => 'Musicothérapie', 'therapist' => 'M. Karim Hamdi', 'description' => 'Initiation à la musique et développement du rythme', 'icon' => '👨‍⚕️', 'available' => true, 'level' => 'Débutant'],
            ['id' => 15, 'day' => 'Jeudi', 'time' => '14h00 - 15h00', 'title' => 'Gestion des Crises', 'type' => 'psychologue', 'specialty' => 'Psychologue TSA', 'therapist' => 'Dr. Rania Jebali', 'description' => 'Techniques pour gérer les crises et l\'anxiété', 'icon' => '👩‍⚕️', 'available' => true, 'level' => 'Parents'],

            // Vendredi - Cours
            ['id' => 16, 'day' => 'Vendredi', 'time' => '09h00 - 10h00', 'title' => 'Langage des Signes', 'type' => 'cours', 'specialty' => 'Orthophonie', 'therapist' => 'Mme. Sonia Trabelsi', 'description' => 'Apprentissage des signes de base pour communiquer', 'icon' => '👩‍🏫', 'available' => true, 'level' => 'Débutant'],
            ['id' => 17, 'day' => 'Vendredi', 'time' => '10h30 - 11h30', 'title' => 'Yoga et Relaxation', 'type' => 'cours', 'specialty' => 'Bien-être', 'therapist' => 'Dr. Lina Mansouri', 'description' => 'Séance de yoga adaptée pour se détendre', 'icon' => '👩‍🎨', 'available' => true, 'level' => 'Tous niveaux'],
            ['id' => 18, 'day' => 'Vendredi', 'time' => '14h00 - 15h00', 'title' => 'Thérapie Cognitive', 'type' => 'psychologue', 'specialty' => 'Psychologue TCC', 'therapist' => 'Dr. Amira Ben Ali', 'description' => 'Travail sur les pensées et les comportements', 'icon' => '👩‍⚕️', 'available' => true, 'level' => 'Adolescent/Adulte'],
            ['id' => 19, 'day' => 'Vendredi', 'time' => '15h30 - 16h30', 'title' => 'Groupe de Parole', 'type' => 'psychologue', 'specialty' => 'Psychologue', 'therapist' => 'Dr. Rania Jebali', 'description' => 'Échange entre parents autour des défis du quotidien', 'icon' => '👩‍⚕️', 'available' => true, 'level' => 'Parents'],

            // Samedi - Cours
            ['id' => 20, 'day' => 'Samedi', 'time' => '09h00 - 10h00', 'title' => 'Cuisine Thérapeutique', 'type' => 'cours', 'specialty' => 'Ergothérapie', 'therapist' => 'M. Karim Hamdi', 'description' => 'Atelier cuisine pour développer l\'autonomie', 'icon' => '👨‍⚕️', 'available' => true, 'level' => 'Intermédiaire'],
            ['id' => 21, 'day' => 'Samedi', 'time' => '10h30 - 11h30', 'title' => 'Jeux de Société', 'type' => 'cours', 'specialty' => 'Socialisation', 'therapist' => 'M. Yassine Bouzid', 'description' => 'Apprendre les règles et jouer en groupe', 'icon' => '👨‍🏫', 'available' => true, 'level' => 'Enfant'],
            ['id' => 22, 'day' => 'Samedi', 'time' => '13h00 - 14h00', 'title' => 'Consultation Adolescent', 'type' => 'psychologue', 'specialty' => 'Psychologue ado', 'therapist' => 'Dr. Lina Mansouri', 'description' => 'Soutien psychologique pour adolescents', 'icon' => '👩‍🎨', 'available' => true, 'level' => 'Adolescent']
        ];

        // Statistiques
        $stats = [
            ['icon' => 'fas fa-chalkboard-teacher', 'color' => 'primary', 'value' => '18', 'label' => 'Cours cette semaine'],
            ['icon' => 'fas fa-user-md', 'color' => 'secondary', 'value' => '12', 'label' => 'Rendez-vous psy'],
            ['icon' => 'fas fa-video', 'color' => 'primary', 'value' => '100%', 'label' => 'Séances en ligne'],
            ['icon' => 'fas fa-smile', 'color' => 'secondary', 'value' => '98%', 'label' => 'Satisfaction']
        ];

        return $this->render('front/planning/index.html.twig', [
            'sessions' => $sessions,
            'stats' => $stats
        ]);
    }
}
>>>>>>> Consultation
