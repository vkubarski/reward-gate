<?php

declare(strict_types=1);

namespace RewardGate\Service;

use InvalidArgumentException;
use RewardGate\Repository\CampaignRepositoryInterface;

final class CampaignService implements CampaignServiceInterface
{
    private const PRESENTATION_TYPES = [
        'popup',
        'content',
    ];

    private const UNLOCK_METHODS = [
        'timer',
    ];

    private const REWARD_TYPES = [
        'content',
    ];

    public function __construct(
        private CampaignRepositoryInterface $campaignRepository
    ) {
    }

    public function create(
        string $name,
        string $presentationType,
        ?array $presentationSettings = null,
        string $status = 'draft',
        string $unlockMethod = 'timer',
        int $timerDurationSeconds = 10,
        string $rewardType = 'content',
        ?int $frequencyLimitSeconds = null,
    ): int {
        $this->assertValidPresentationType($presentationType);
        $this->assertValidUnlockMethod($unlockMethod);
        $this->assertValidRewardType($rewardType);
        $this->assertValidFrequencyLimit($frequencyLimitSeconds);

        return $this->campaignRepository->create(
            $name,
            $presentationType,
            $presentationSettings,
            $status,
            $unlockMethod,
            $timerDurationSeconds,
            $rewardType,
            $frequencyLimitSeconds,
        );
    }

    public function findById(int $id): ?array
    {
        return $this->campaignRepository->findById($id);
    }

    public function findAll(
        string $orderBy = 'created_at',
        string $direction = 'DESC',
        int $limit = 20,
        int $offset = 0,
    ): array {
        return $this->campaignRepository->findAll(
            $orderBy,
            $direction,
            $limit,
            $offset
        );
    }

    public function update(int $id, array $data): bool
    {
        if (isset($data['presentation_type'])) {
            $this->assertValidPresentationType(
                $data['presentation_type']
            );
        }

        if (isset($data['unlock_method'])) {
            $this->assertValidUnlockMethod(
                $data['unlock_method']
            );
        }

        if (isset($data['reward_type'])) {
            $this->assertValidRewardType(
                $data['reward_type']
            );
        }

        if (array_key_exists('frequency_limit_seconds', $data)) {
            $this->assertValidFrequencyLimit(
                $data['frequency_limit_seconds']
            );
        }

        return $this->campaignRepository->update($id, $data);
    }

    private function assertValidPresentationType(string $type): void
    {
        if (!in_array($type, self::PRESENTATION_TYPES, true)) {
            throw new InvalidArgumentException(
                "Invalid presentation type: {$type}"
            );
        }
    }

    private function assertValidUnlockMethod(string $method): void
    {
        if (!in_array($method, self::UNLOCK_METHODS, true)) {
            throw new InvalidArgumentException(
                "Invalid unlock method: {$method}"
            );
        }
    }

    private function assertValidRewardType(string $type): void
    {
        if (!in_array($type, self::REWARD_TYPES, true)) {
            throw new InvalidArgumentException(
                "Invalid reward type: {$type}"
            );
        }
    }

    private function assertValidFrequencyLimit(?int $seconds): void
    {
        if ($seconds !== null && $seconds < 1) {
            throw new InvalidArgumentException(
                'Frequency limit must be null or greater than zero.'
            );
        }
    }
}
