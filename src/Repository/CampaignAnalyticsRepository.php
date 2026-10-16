<?php

declare(strict_types=1);

namespace RewardGate\Repository;

use PDO;

final class CampaignAnalyticsRepository implements CampaignAnalyticsRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function getCounts(
        int $campaignId,
        string $start,
        string $end,
    ): array {
        $shownStatement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM gate_impressions
             WHERE campaign_id = :campaign_id
               AND created_at >= :start
               AND created_at < :end'
        );

        $shownStatement->execute([
            'campaign_id' => $campaignId,
            'start' => $start,
            'end' => $end,
        ]);

        $sessionsStatement = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS sessions,
                SUM(
                    CASE
                        WHEN required_duration_seconds > 0
                        THEN 1
                        ELSE 0
                    END
                ) AS timer_sessions
             FROM unlock_sessions
             WHERE campaign_id = :campaign_id
               AND started_at >= :start
               AND started_at < :end'
        );

        $sessionsStatement->execute([
            'campaign_id' => $campaignId,
            'start' => $start,
            'end' => $end,
        ]);

        $sessions = $sessionsStatement->fetch(PDO::FETCH_ASSOC);

        $completedStatement = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS completed,
                SUM(
                    CASE
                        WHEN s.required_duration_seconds > 0
                        THEN 1
                        ELSE 0
                    END
                ) AS timer_completed
             FROM unlock_completions AS c
             INNER JOIN unlock_sessions AS s
                ON s.id = c.unlock_session_id
             WHERE s.campaign_id = :campaign_id
               AND s.started_at >= :start
               AND s.started_at < :end'
        );

        $completedStatement->execute([
            'campaign_id' => $campaignId,
            'start' => $start,
            'end' => $end,
        ]);

        $completed = $completedStatement->fetch(PDO::FETCH_ASSOC);

        return [
            'shown' => (int) $shownStatement->fetchColumn(),
            'sessions' => (int) $sessions['sessions'],
            'completed' => (int) $completed['completed'],
            'timer_sessions' => (int) $sessions['timer_sessions'],
            'timer_completed' => (int) $completed['timer_completed'],
        ];
    }
}
