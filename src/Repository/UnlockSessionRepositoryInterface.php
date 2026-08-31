<?php

declare(strict_types=1);

namespace RewardGate\Repository;

interface UnlockSessionRepositoryInterface
{
    public function create(
        int $campaignId,
        string $tokenHash,
        ?string $visitorId,
        int $requiredDurationSeconds,
        string $startedAt,
        string $expiresAt,
    ): int;

    public function findByTokenHash(string $tokenHash): ?array;

    public function markCompleted(int $id): bool;

    public function markExpired(int $id): bool;
}
