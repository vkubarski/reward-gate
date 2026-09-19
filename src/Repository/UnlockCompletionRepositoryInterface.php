<?php

declare(strict_types=1);

namespace RewardGate\Repository;

interface UnlockCompletionRepositoryInterface
{
    public function create(
        int $unlockSessionId,
        int $campaignId,
        ?string $visitorId,
        string $completedAt,
    ): int;

    public function findByUnlockSessionId(
        int $unlockSessionId
    ): ?array;

    public function countRecentByCampaignAndVisitor(
        int $campaignId,
        string $visitorId,
        string $cutoff,
    ): int;

    public function hasCompletionByCampaignAndVisitor(
        int $campaignId,
        string $visitorId,
    ): bool;
}
