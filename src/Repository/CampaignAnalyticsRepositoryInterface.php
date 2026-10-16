<?php

declare(strict_types=1);

namespace RewardGate\Repository;

interface CampaignAnalyticsRepositoryInterface
{
    public function getCounts(
        int $campaignId,
        string $start,
        string $end,
    ): array;
}
