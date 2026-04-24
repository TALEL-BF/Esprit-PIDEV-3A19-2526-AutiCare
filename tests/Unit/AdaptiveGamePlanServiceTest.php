<?php

namespace App\Tests\Unit;

use App\Service\AdaptiveGamePlanService;
use App\Service\GameCatalogService;
use PHPUnit\Framework\TestCase;

class AdaptiveGamePlanServiceTest extends TestCase
{
    public function testBuildWeeklyPlanTargetsFacileAndWeakSkills(): void
    {
        $service = new AdaptiveGamePlanService(new GameCatalogService());

        $insights = [
            'kpis' => [
                'success_rate' => 42.0,
                'progress_by_skill' => [
                    'memoire' => ['current' => 40.0, 'delta' => -5.0],
                    'social' => ['current' => 60.0, 'delta' => 0.0],
                    'communication' => ['current' => 30.0, 'delta' => -3.0],
                ],
            ],
        ];

        $plan = $service->buildWeeklyPlan($insights);

        $this->assertSame('FACILE', $plan['target_level']);
        $this->assertSame(['communication', 'memoire'], $plan['focus_skills']);
        $this->assertNotEmpty($plan['recommended_games']);
        $this->assertStringContainsString('Stabiliser les acquis', $plan['weekly_objective']);
    }

    public function testBuildWeeklyPlanTargetsDifficileForHighSuccessRate(): void
    {
        $service = new AdaptiveGamePlanService(new GameCatalogService());

        $insights = [
            'kpis' => [
                'success_rate' => 89.0,
                'progress_by_skill' => [
                    'memoire' => ['current' => 85.0, 'delta' => 4.0],
                    'social' => ['current' => 78.0, 'delta' => -1.0],
                    'communication' => ['current' => 82.0, 'delta' => 2.0],
                ],
            ],
        ];

        $plan = $service->buildWeeklyPlan($insights);

        $this->assertSame('DIFFICILE', $plan['target_level']);
        $this->assertCount(2, $plan['focus_skills']);
        $this->assertCount(3, $plan['recommended_games']);
        $this->assertSame('Scenarios Sociaux', $plan['recommended_games'][0]['title']);
        $this->assertStringContainsString('Maintenir le niveau actuel', $plan['weekly_objective']);
    }
}
