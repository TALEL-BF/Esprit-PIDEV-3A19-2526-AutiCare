<?php

namespace App\Service;

class AdaptiveGamePlanService
{
    private const SKILL_TO_CATEGORY = [
        'memoire' => 'Memoire',
        'social' => 'Social',
        'communication' => 'Communication',
    ];

    public function __construct(private readonly GameCatalogService $gameCatalogService)
    {
    }

    /**
     * @param array<string, mixed> $insights
     * @return array<string, mixed>
     */
    public function buildWeeklyPlan(array $insights): array
    {
        $kpis = $insights['kpis'] ?? [];
        $successRate = (float) ($kpis['success_rate'] ?? 0.0);
        $progressBySkill = (array) ($kpis['progress_by_skill'] ?? []);

        $targetLevel = $this->resolveLevel($successRate);
        $focusSkills = $this->resolveFocusSkills($progressBySkill);
        $games = $this->rankGamesForFocus($targetLevel, $focusSkills);

        return [
            'target_level' => $targetLevel,
            'focus_skills' => $focusSkills,
            'weekly_objective' => $this->buildObjective($successRate, $focusSkills),
            'recommended_games' => array_slice($games, 0, 3),
        ];
    }

    private function resolveLevel(float $successRate): string
    {
        if ($successRate < 55.0) {
            return 'FACILE';
        }

        if ($successRate <= 75.0) {
            return 'MOYEN';
        }

        return 'DIFFICILE';
    }

    /**
     * @param array<string, mixed> $progressBySkill
     * @return string[]
     */
    private function resolveFocusSkills(array $progressBySkill): array
    {
        $rows = [];
        foreach (['memoire', 'social', 'communication'] as $skill) {
            $data = (array) ($progressBySkill[$skill] ?? []);
            $rows[] = [
                'skill' => $skill,
                'current' => (float) ($data['current'] ?? 0.0),
                'delta' => (float) ($data['delta'] ?? 0.0),
            ];
        }

        usort(
            $rows,
            static fn (array $a, array $b): int => ($a['current'] <=> $b['current']) ?: ($a['delta'] <=> $b['delta'])
        );

        return array_map(static fn (array $row): string => (string) $row['skill'], array_slice($rows, 0, 2));
    }

    /**
     * @param string[] $focusSkills
     * @return array<int, array<string, mixed>>
     */
    private function rankGamesForFocus(string $targetLevel, array $focusSkills): array
    {
        $games = $this->gameCatalogService->gamesForLevel($targetLevel);

        usort($games, function (array $left, array $right) use ($focusSkills): int {
            return $this->scoreGame($right, $focusSkills) <=> $this->scoreGame($left, $focusSkills);
        });

        $ranked = [];
        foreach ($games as $game) {
            $ranked[] = [
                'id' => $game['id'],
                'title' => $game['title'],
                'category' => $game['category'],
                'difficulty' => $game['difficulty'],
                'justification' => $this->buildGameJustification($game, $focusSkills),
            ];
        }

        return $ranked;
    }

    /**
     * @param string[] $focusSkills
     */
    private function scoreGame(array $game, array $focusSkills): int
    {
        $score = 0;
        $category = (string) ($game['category'] ?? '');

        foreach ($focusSkills as $index => $skill) {
            $targetCategory = self::SKILL_TO_CATEGORY[$skill] ?? null;
            if ($targetCategory === null) {
                continue;
            }

            if (mb_strtolower($category) === mb_strtolower($targetCategory)) {
                $score += $index === 0 ? 3 : 2;
            }
        }

        return $score;
    }

    /**
     * @param string[] $focusSkills
     */
    private function buildGameJustification(array $game, array $focusSkills): string
    {
        $category = (string) ($game['category'] ?? '');

        foreach ($focusSkills as $skill) {
            $targetCategory = self::SKILL_TO_CATEGORY[$skill] ?? null;
            if ($targetCategory !== null && mb_strtolower($targetCategory) === mb_strtolower($category)) {
                return sprintf('Prioritaire pour travailler la competence %s.', $skill);
            }
        }

        return 'Jeu complementaire pour maintenir la variete cognitive.';
    }

    /**
     * @param string[] $focusSkills
     */
    private function buildObjective(float $successRate, array $focusSkills): string
    {
        $mainSkill = $focusSkills[0] ?? 'memoire';

        if ($successRate < 55.0) {
            return sprintf('Stabiliser les acquis avec 3 sessions courtes ciblees sur %s.', $mainSkill);
        }

        if ($successRate <= 75.0) {
            return sprintf('Augmenter progressivement la maitrise sur %s et consolider la seconde competence cible.', $mainSkill);
        }

        return sprintf('Maintenir le niveau actuel tout en ajoutant un challenge controle sur %s.', $mainSkill);
    }
}
