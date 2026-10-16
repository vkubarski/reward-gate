<?php

declare(strict_types=1);

namespace RewardGate\Repository;

use PDO;

final class GateImpressionRepository implements GateImpressionRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function record(
        int $campaignId,
        string $visitorId,
        string $pageViewId,
        string $presentationType,
        string $unlockMethod,
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO gate_impressions (
                campaign_id,
                visitor_id,
                page_view_id,
                presentation_type,
                unlock_method
            ) VALUES (
                :campaign_id,
                :visitor_id,
                :page_view_id,
                :presentation_type,
                :unlock_method
            )
            ON DUPLICATE KEY UPDATE id = id'
        );

        $statement->execute([
            'campaign_id' => $campaignId,
            'visitor_id' => $visitorId,
            'page_view_id' => $pageViewId,
            'presentation_type' => $presentationType,
            'unlock_method' => $unlockMethod,
        ]);
    }
}
