<?php

namespace App\Service;

use App\Entity\ClinicalNote;
use App\Entity\GameSession;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiTherapeuticSummaryService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(default::AI_SUMMARY_API_KEY)%')]
        private readonly ?string $apiKey,
        #[Autowire('%env(default::AI_SUMMARY_API_URL)%')]
        private readonly ?string $apiUrl,
        #[Autowire('%env(default::AI_SUMMARY_MODEL)%')]
        private readonly ?string $model
    ) {
    }

    /**
     * @param array<string, mixed> $insights
     * @param GameSession[] $recentSessions
     * @param ClinicalNote[] $recentNotes
     * @return array<string, mixed>
     */
    public function generateWeeklySummary(
        int $enfantId,
        array $insights,
        array $recentSessions,
        array $recentNotes
    ): array {
        $fallback = $this->buildLocalFallback($insights);

        if (!$this->apiKey || !$this->apiUrl) {
            return [
                'generated' => false,
                'source' => 'local',
                'text' => $fallback,
                'error' => 'AI_SUMMARY_API_KEY ou AI_SUMMARY_API_URL non configure.',
            ];
        }

        $payload = [
            'model' => $this->model ?: 'gpt-4o-mini',
            'temperature' => 0.2,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un assistant clinique specialise en autisme. Reponds en francais simple, professionnel et actionnable. Donne uniquement du texte brut.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($enfantId, $insights, $recentSessions, $recentNotes),
                ],
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 20,
            ]);

            $data = $response->toArray(false);
            $text = trim((string) ($data['choices'][0]['message']['content'] ?? ''));

            if ($text === '') {
                return [
                    'generated' => false,
                    'source' => 'local',
                    'text' => $fallback,
                    'error' => 'Reponse IA vide, fallback local applique.',
                ];
            }

            return [
                'generated' => true,
                'source' => 'ai',
                'text' => $text,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'generated' => false,
                'source' => 'local',
                'text' => $fallback,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $insights
     * @param GameSession[] $recentSessions
     * @param ClinicalNote[] $recentNotes
     */
    private function buildPrompt(int $enfantId, array $insights, array $recentSessions, array $recentNotes): string
    {
        $successRate = (float) ($insights['kpis']['success_rate'] ?? 0.0);
        $avgDuration = (int) ($insights['kpis']['avg_duration_seconds'] ?? 0);
        $totalSessions = (int) ($insights['kpis']['total_sessions'] ?? 0);

        $skills = $insights['kpis']['progress_by_skill'] ?? [];
        $memoire = $skills['memoire']['current'] ?? 0;
        $social = $skills['social']['current'] ?? 0;
        $communication = $skills['communication']['current'] ?? 0;

        $recentGames = array_map(
            static fn (GameSession $s): string => sprintf(
                '- %s (%s): %.2f/%.2f en %d sec',
                (string) $s->getGameTitle(),
                (string) $s->getSkill(),
                (float) $s->getScore(),
                (float) $s->getMaxScore(),
                (int) $s->getDurationSeconds()
            ),
            array_slice($recentSessions, 0, 6)
        );

        $recentClinical = array_map(
            static fn (ClinicalNote $n): string => sprintf(
                '- Note: %s | Action: %s | Resultat: %s',
                mb_substr((string) $n->getSessionNote(), 0, 90),
                mb_substr((string) ($n->getProposedAction() ?? '-'), 0, 60),
                mb_substr((string) ($n->getResultAfterAction() ?? '-'), 0, 60)
            ),
            array_slice($recentNotes, 0, 4)
        );

        $format = [
            'Format de sortie attendu (4 blocs):',
            '1) Synthese hebdo (4-6 lignes)',
            '2) Progres observes (puces)',
            '3) Points faibles (puces)',
            '4) Plan d action concret pour la semaine prochaine (3 a 5 actions)',
        ];

        return implode("\n", [
            'Contexte:',
            "Enfant ID: {$enfantId}",
            "Taux de reussite: {$successRate}%",
            "Temps moyen par jeu: {$avgDuration} sec",
            "Nombre de sessions cette semaine: {$totalSessions}",
            "Progression memoire: {$memoire}%",
            "Progression social: {$social}%",
            "Progression communication: {$communication}%",
            '',
            'Dernieres sessions de jeu:',
            $recentGames !== [] ? implode("\n", $recentGames) : '- Aucune session recente',
            '',
            'Dernieres notes cliniques:',
            $recentClinical !== [] ? implode("\n", $recentClinical) : '- Aucune note clinique recente',
            '',
            implode("\n", $format),
        ]);
    }

    /**
     * @param array<string, mixed> $insights
     */
    private function buildLocalFallback(array $insights): string
    {
        $summary = $insights['summary'] ?? [];
        $progress = $summary['progress'] ?? [];
        $weakPoints = $summary['weak_points'] ?? [];
        $actions = $summary['recommended_actions'] ?? [];

        return implode("\n", [
            'Synthese locale (fallback):',
            '- Progres: ' . ($progress !== [] ? implode(' | ', $progress) : 'N/A'),
            '- Points faibles: ' . ($weakPoints !== [] ? implode(' | ', $weakPoints) : 'N/A'),
            '- Actions recommandees: ' . ($actions !== [] ? implode(' | ', $actions) : 'N/A'),
        ]);
    }
}
