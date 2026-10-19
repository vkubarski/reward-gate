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

    public function getDailyCounts(
        int $campaignId,
        string $start,
        string $end,
    ): array {
        $shownStatement = $this->pdo->prepare(
            'SELECT
                DATE(created_at) AS report_date,
                COUNT(*) AS shown
             FROM gate_impressions
             WHERE campaign_id = :campaign_id
               AND created_at >= :start
               AND created_at < :end
             GROUP BY DATE(created_at)'
        );

        $shownStatement->execute([
            'campaign_id' => $campaignId,
            'start' => $start,
            'end' => $end,
        ]);

        $daily = [];

        foreach ($shownStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $daily[$row['report_date']] = [
                'shown' => (int) $row['shown'],
                'sessions' => 0,
                'completed' => 0,
            ];
        }

        $sessionsStatement = $this->pdo->prepare(
            'SELECT
                DATE(started_at) AS report_date,
                COUNT(*) AS sessions
             FROM unlock_sessions
             WHERE campaign_id = :campaign_id
               AND started_at >= :start
               AND started_at < :end
             GROUP BY DATE(started_at)'
        );

        $sessionsStatement->execute([
            'campaign_id' => $campaignId,
            'start' => $start,
            'end' => $end,
        ]);

        foreach ($sessionsStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $date = $row['report_date'];

            $daily[$date] ??= [
                'shown' => 0,
                'sessions' => 0,
                'completed' => 0,
            ];

            $daily[$date]['sessions'] = (int) $row['sessions'];
        }

        $completedStatement = $this->pdo->prepare(
            'SELECT
                DATE(s.started_at) AS report_date,
                COUNT(*) AS completed
             FROM unlock_completions AS c
             INNER JOIN unlock_sessions AS s
                ON s.id = c.unlock_session_id
             WHERE s.campaign_id = :campaign_id
               AND s.started_at >= :start
               AND s.started_at < :end
             GROUP BY DATE(s.started_at)'
        );

        $completedStatement->execute([
            'campaign_id' => $campaignId,
            'start' => $start,
            'end' => $end,
        ]);

        foreach ($completedStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $date = $row['report_date'];

            $daily[$date] ??= [
                'shown' => 0,
                'sessions' => 0,
                'completed' => 0,
            ];

            $daily[$date]['completed'] = (int) $row['completed'];
        }

        ksort($daily);

        return $daily;
    }
}
