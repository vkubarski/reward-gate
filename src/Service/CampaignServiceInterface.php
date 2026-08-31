<?php

declare(strict_types=1);

namespace RewardGate\Service;

interface CampaignServiceInterface
{
    public function create(
        string $name,
        string $presentationType,
        ?array $presentationSettings = null,
        string $status = 'draft',
        string $unlockMethod = 'timer',
        int $timerDurationSeconds = 10,
        string $rewardType = 'content',
        ?int $frequencyLimitSeconds = null,
    ): int;

    public function findById(int $id): ?array;

    public function findAll(
        string $orderBy = 'created_at',
        string $direction = 'DESC',
        int $limit = 20,
        int $offset = 0,
    ): array;

    public function update(int $id, array $data): bool;
}
