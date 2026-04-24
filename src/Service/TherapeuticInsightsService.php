<?php

namespace App\Service;

use App\Entity\GameSession;
use App\Repository\GameSessionRepository;

class TherapeuticInsightsService
{
    public function __construct(private readonly GameSessionRepository $gameSessionRepository)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildWeeklyInsights(int $enfantId, ?\DateTimeImmutable $referenceDate = null): array
    {
        $ref = $referenceDate ?? new \DateTimeImmutable();
        $startOfWeek = $ref->modify('monday this week')->setTime(0, 0);
        $endOfWeek = $startOfWeek->modify('+6 days')->setTime(23, 59, 59);

        $previousStart = $startOfWeek->modify('-7 days');
        $previousEnd = $endOfWeek->modify('-7 days');

        $currentSessions = $this->gameSessionRepository->findByEnfantAndPeriod($enfantId, $startOfWeek, $endOfWeek);
        $previousSessions = $this->gameSessionRepository->findByEnfantAndPeriod($enfantId, $previousStart, $previousEnd);

        $currentBySkill = $this->averageBySkill($currentSessions);
        $previousBySkill = $this->averageBySkill($previousSessions);

        $kpis = [
            'total_sessions' => count($currentSessions),
            'success_rate' => $this->successRate($currentSessions),
            'avg_duration_seconds' => $this->avgDuration($currentSessions),
            'progress_by_skill' => [
                'memoire' => $this->buildSkillProgress('memoire', $currentBySkill, $previousBySkill),
                'social' => $this->buildSkillProgress('social', $currentBySkill, $previousBySkill),
                'communication' => $this->buildSkillProgress('communication', $currentBySkill, $previousBySkill),
            ],
        ];

        return [
            'range' => [
                'start' => $startOfWeek,
                'end' => $endOfWeek,
            ],
            'kpis' => $kpis,
            'summary' => $this->buildSummary($kpis),
        ];
    }

    /**
     * @param GameSession[] $sessions
     * @return array<string, float>
     */
    private function averageBySkill(array $sessions): array
    {
        $totals = [
            'memoire' => ['sum' => 0.0, 'count' => 0],
            'social' => ['sum' => 0.0, 'count' => 0],
            'communication' => ['sum' => 0.0, 'count' => 0],
        ];

        foreach ($sessions as $session) {
            $skill = (string) $session->getSkill();
            if (!isset($totals[$skill])) {
                continue;
            }

            $max = (float) $session->getMaxScore();
            if ($max <= 0) {
                continue;
            }

            $totals[$skill]['sum'] += ((float) $session->getScore() / $max) * 100;
            $totals[$skill]['count']++;
        }

        $averages = [];
        foreach ($totals as $skill => $data) {
            $averages[$skill] = $data['count'] > 0 ? round($data['sum'] / $data['count'], 2) : 0.0;
        }

        return $averages;
    }

    /**
     * @param GameSession[] $sessions
     */
    private function successRate(array $sessions): float
    {
        if ($sessions === []) {
            return 0.0;
        }

        $sum = 0.0;
        $count = 0;
        foreach ($sessions as $session) {
            $max = (float) $session->getMaxScore();
            if ($max <= 0) {
                continue;
            }

            $sum += ((float) $session->getScore() / $max) * 100;
            $count++;
        }

        return $count > 0 ? round($sum / $count, 2) : 0.0;
    }

    /**
     * @param GameSession[] $sessions
     */
    private function avgDuration(array $sessions): int
    {
        if ($sessions === []) {
            return 0;
        }

        $sum = array_reduce(
            $sessions,
            static fn (int $carry, GameSession $session): int => $carry + (int) $session->getDurationSeconds(),
            0
        );

        return (int) round($sum / count($sessions));
    }

    /**
     * @param array<string, float> $currentBySkill
     * @param array<string, float> $previousBySkill
     * @return array<string, float>
     */
    private function buildSkillProgress(string $skill, array $currentBySkill, array $previousBySkill): array
    {
        $current = $currentBySkill[$skill] ?? 0.0;
        $previous = $previousBySkill[$skill] ?? 0.0;

        return [
            'current' => $current,
            'previous' => $previous,
            'delta' => round($current - $previous, 2),
        ];
    }

    /**
     * @param array<string, mixed> $kpis
     * @return array<string, array<int, string>>
     */
    private function buildSummary(array $kpis): array
    {
        $successRate = (float) $kpis['success_rate'];
        $duration = (int) $kpis['avg_duration_seconds'];
        $skills = $kpis['progress_by_skill'];

        $progress = [];
        $weakPoints = [];
        $actions = [];

        if ($successRate >= 75) {
            $progress[] = 'Bonne reussite globale cette semaine.';
        } elseif ($successRate >= 55) {
            $progress[] = 'Reussite stable avec marge de progression.';
            $actions[] = 'Renforcer les seances sur les jeux les mieux maitrises pour consolider.';
        } else {
            $weakPoints[] = 'Taux de reussite faible sur la semaine.';
            $actions[] = 'Redescendre temporairement la difficulte et augmenter le guidage.';
        }

        if ($duration > 0 && $duration <= 600) {
            $progress[] = 'Temps moyen par jeu adapte a la concentration actuelle.';
        } elseif ($duration > 600) {
            $weakPoints[] = 'Temps moyen eleve par jeu, possible fatigue ou hesitation.';
            $actions[] = 'Fractionner les activites en blocs de 8 a 10 minutes.';
        }

        foreach (['memoire', 'social', 'communication'] as $skill) {
            $delta = (float) $skills[$skill]['delta'];
            $current = (float) $skills[$skill]['current'];

            if ($delta >= 5) {
                $progress[] = sprintf('Progression notable sur la competence %s (+%.2f points).', $skill, $delta);
            }

            if ($current > 0 && $current < 55) {
                $weakPoints[] = sprintf('Competence %s en difficulte (%.2f%%).', $skill, $current);
                $actions[] = sprintf('Proposer 2 activites ciblees %s la semaine prochaine.', $skill);
            }
        }

        if ($actions === []) {
            $actions[] = 'Maintenir le rythme actuel avec un objectif progressif de +5% la semaine prochaine.';
        }

        if ($progress === []) {
            $progress[] = 'Pas assez de donnees cette semaine pour confirmer une progression.';
        }

        if ($weakPoints === []) {
            $weakPoints[] = 'Aucun point faible majeur detecte sur la periode.';
        }

        return [
            'progress' => array_values(array_unique($progress)),
            'weak_points' => array_values(array_unique($weakPoints)),
            'recommended_actions' => array_values(array_unique($actions)),
        ];
    }
}
