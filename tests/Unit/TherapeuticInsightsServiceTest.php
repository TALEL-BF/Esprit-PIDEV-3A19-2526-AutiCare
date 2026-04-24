<?php

namespace App\Tests\Unit;

use App\Entity\GameSession;
use App\Repository\GameSessionRepository;
use App\Service\TherapeuticInsightsService;
use PHPUnit\Framework\TestCase;

class TherapeuticInsightsServiceTest extends TestCase
{
    public function testBuildWeeklyInsightsComputesKpisAndSummary(): void
    {
        $currentSessions = [
            $this->buildSession('memoire', 80, 100, 700),
            $this->buildSession('social', 50, 100, 900),
        ];

        $previousSessions = [
            $this->buildSession('memoire', 60, 100, 500),
            $this->buildSession('social', 55, 100, 500),
        ];

        $repo = $this->createMock(GameSessionRepository::class);
        $repo->expects($this->exactly(2))
            ->method('findByEnfantAndPeriod')
            ->willReturnOnConsecutiveCalls($currentSessions, $previousSessions);

        $service = new TherapeuticInsightsService($repo);

        $result = $service->buildWeeklyInsights(222, new \DateTimeImmutable('2026-04-24'));

        $this->assertSame(2, $result['kpis']['total_sessions']);
        $this->assertSame(65.0, $result['kpis']['success_rate']);
        $this->assertSame(800, $result['kpis']['avg_duration_seconds']);
        $this->assertSame(20.0, $result['kpis']['progress_by_skill']['memoire']['delta']);

        $this->assertContains('Reussite stable avec marge de progression.', $result['summary']['progress']);
        $this->assertContains(
            'Temps moyen eleve par jeu, possible fatigue ou hesitation.',
            $result['summary']['weak_points']
        );
    }

    public function testBuildWeeklyInsightsHandlesNoSessions(): void
    {
        $repo = $this->createMock(GameSessionRepository::class);
        $repo->expects($this->exactly(2))
            ->method('findByEnfantAndPeriod')
            ->willReturnOnConsecutiveCalls([], []);

        $service = new TherapeuticInsightsService($repo);

        $result = $service->buildWeeklyInsights(222, new \DateTimeImmutable('2026-04-24'));

        $this->assertSame(0, $result['kpis']['total_sessions']);
        $this->assertSame(0.0, $result['kpis']['success_rate']);
        $this->assertContains('Taux de reussite faible sur la semaine.', $result['summary']['weak_points']);
        $this->assertContains(
            'Redescendre temporairement la difficulte et augmenter le guidage.',
            $result['summary']['recommended_actions']
        );
    }

    private function buildSession(string $skill, float $score, float $maxScore, int $duration): GameSession
    {
        return (new GameSession())
            ->setEnfantId(222)
            ->setGameTitle('Session de test')
            ->setSkill($skill)
            ->setScore($score)
            ->setMaxScore($maxScore)
            ->setDurationSeconds($duration)
            ->setPlayedAt(new \DateTimeImmutable('2026-04-24 10:00:00'));
    }
}
