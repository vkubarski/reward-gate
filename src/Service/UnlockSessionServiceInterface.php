<?php

declare(strict_types=1);

namespace RewardGate\Service;

interface UnlockSessionServiceInterface
{
    public function start(
        int $campaignId,
        ?string $visitorId = null,
    ): array;

    public function complete(string $token): void;
}
