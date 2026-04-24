<?php

namespace App\AutiCareGameBundle\Service;

use App\Service\AiTherapeuticSummaryService;
use App\Service\AdaptiveGamePlanService;
use App\Service\TherapeuticInsightsService;

class GameIntelligenceFacade
{
    public function __construct(
        private readonly TherapeuticInsightsService $therapeuticInsightsService,
        private readonly AiTherapeuticSummaryService $aiTherapeuticSummaryService,
        private readonly AdaptiveGamePlanService $adaptiveGamePlanService
    ) {
    }

    /**
     * @param array<int, object> $recentSessions
     * @param array<int, object> $recentNotes
     * @return array<string, mixed>
     */
    public function buildInsightPayload(
        int $enfantId,
        array $recentSessions,
        array $recentNotes,
        bool $generateAi = false
    ): array {
        $insights = $this->therapeuticInsightsService->buildWeeklyInsights($enfantId);
        $adaptivePlan = $this->adaptiveGamePlanService->buildWeeklyPlan($insights);

        $aiSummary = null;
        if ($generateAi) {
            $aiSummary = $this->aiTherapeuticSummaryService->generateWeeklySummary(
                $enfantId,
                $insights,
                $recentSessions,
                $recentNotes
            );
        }

        return [
            'insights' => $insights,
            'adaptive_plan' => $adaptivePlan,
            'ai_summary' => $aiSummary,
        ];
    }
}
