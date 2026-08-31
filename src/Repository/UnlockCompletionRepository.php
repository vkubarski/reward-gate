<?php

declare(strict_types=1);

namespace RewardGate\Repository;

use PDO;

final class UnlockCompletionRepository implements UnlockCompletionRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function create(
        int $unlockSessionId,
        int $campaignId,
        ?string $visitorId,
        string $completedAt,
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO unlock_completions (
                                unlock_session_id,
                                campaign_id,
                                visitor_id,
                                completed_at
                        ) VALUES (
                                :unlock_session_id,
                                :campaign_id,
                                :visitor_id,
                                :completed_at
                        )'
        );

        $statement->execute([
                'unlock_session_id' => $unlockSessionId,
                'campaign_id' => $campaignId,
                'visitor_id' => $visitorId,
                'completed_at' => $completedAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByUnlockSessionId(
        int $unlockSessionId
    ): ?array {
        $statement = $this->pdo->prepare(
            'SELECT *
                         FROM unlock_completions
                         WHERE unlock_session_id = :unlock_session_id
                         LIMIT 1'
        );

        $statement->execute([
                'unlock_session_id' => $unlockSessionId,
        ]);

        $completion = $statement->fetch();

        return $completion === false
                ? null
                : $completion;
    }
    // $cutoff must use 'Y-m-d H:i:s' format, matching the DATETIME column.
    public function countRecentByCampaignAndVisitor(
        int $campaignId,
        string $visitorId,
        string $cutoff,
    ): int {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
                         FROM unlock_completions
                         WHERE campaign_id = :campaign_id
                           AND visitor_id = :visitor_id
                           AND completed_at > :cutoff'
        );

        $statement->execute([
                'campaign_id' => $campaignId,
                'visitor_id' => $visitorId,
                'cutoff' => $cutoff,
        ]);

        return (int) $statement->fetchColumn();
    }
}
