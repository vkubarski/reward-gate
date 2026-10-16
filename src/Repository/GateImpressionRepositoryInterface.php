<?php

declare(strict_types=1);

namespace RewardGate\Repository;

interface GateImpressionRepositoryInterface
{
    public function record(
        int $campaignId,
        string $visitorId,
        string $pageViewId,
        string $presentationType,
        string $unlockMethod,
    ): void;
}
