<?php

namespace App\AutiCareGameBundle\Service;

use App\Service\AiTherapeuticSummaryService;
use App\Service\TherapeuticInsightsService;

class GameIntelligenceFacade
{
    public function __construct(
        private readonly TherapeuticInsightsService $therapeuticInsightsService,
        private readonly AiTherapeuticSummaryService $aiTherapeuticSummaryService
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
            'ai_summary' => $aiSummary,
        ];
    }
}
