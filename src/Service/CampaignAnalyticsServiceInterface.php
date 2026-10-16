<?php

declare(strict_types=1);

namespace RewardGate\Service;

use DateTimeImmutable;

interface CampaignAnalyticsServiceInterface
{
    public function getReport(
        int $campaignId,
        DateTimeImmutable $referenceTime,
    ): array;
}
