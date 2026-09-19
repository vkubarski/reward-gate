<?php

declare(strict_types=1);

namespace RewardGate\Service;

interface UnlockSessionServiceInterface
{
    public function start(
        int $campaignId,
        ?string $visitorId = null,
    ): array;

    public function complete(
        string $token,
        ?string $visitorId = null,
    ): void;

    public function status(
        int $campaignId,
        ?string $visitorId = null,
    ): bool;
}
