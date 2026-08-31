<?php

declare(strict_types=1);

namespace RewardGate\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use RewardGate\Repository\CampaignRepositoryInterface;
use RewardGate\Repository\UnlockCompletionRepositoryInterface;
use RewardGate\Repository\UnlockSessionRepositoryInterface;
use RuntimeException;

final class UnlockSessionService implements UnlockSessionServiceInterface
{
    private const TOKEN_BYTES = 32;

    public function __construct(
        private PDO $pdo,
        private CampaignRepositoryInterface $campaignRepository,
        private UnlockSessionRepositoryInterface $unlockSessionRepository,
        private UnlockCompletionRepositoryInterface $unlockCompletionRepository,
    ) {
    }

    public function start(
        int $campaignId,
        ?string $visitorId = null,
    ): array {
        $campaign = $this->campaignRepository->findById($campaignId);

        if ($campaign === null) {
            throw new InvalidArgumentException('Campaign not found.');
        }

        if ($campaign['status'] !== 'active') {
            throw new InvalidArgumentException('Campaign is not active.');
        }

        if ($campaign['unlock_method'] !== 'timer') {
            throw new InvalidArgumentException(
                'Unsupported unlock method.'
            );
        }

        $requiredDuration = (int) $campaign['timer_duration_seconds'];

        if ($requiredDuration < 1) {
            throw new InvalidArgumentException(
                'Campaign timer duration must be greater than zero.'
            );
        }

        $frequencyLimitSeconds = $campaign['frequency_limit_seconds'];

        if ($visitorId !== null && $frequencyLimitSeconds !== null) {
            $this->assertFrequencyLimitAllowsVisitor(
                $campaignId,
                $visitorId,
                (int) $frequencyLimitSeconds,
            );
        }

        $token = bin2hex(random_bytes(self::TOKEN_BYTES));
        $tokenHash = hash('sha256', $token);

        $startedAt = new DateTimeImmutable();
        $expiresAt = $startedAt->modify(
            '+' . ($requiredDuration + 300) . ' seconds'
        );

        $sessionId = $this->unlockSessionRepository->create(
            $campaignId,
            $tokenHash,
            $visitorId,
            $requiredDuration,
            $startedAt->format('Y-m-d H:i:s'),
            $expiresAt->format('Y-m-d H:i:s'),
        );

        return [
            'id' => $sessionId,
            'token' => $token,
            'required_duration_seconds' => $requiredDuration,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ];
    }

    public function complete(string $token): void
    {
        if ($token === '') {
            throw new InvalidArgumentException(
                'Unlock token is required.'
            );
        }

        $tokenHash = hash('sha256', $token);

        $session = $this->unlockSessionRepository->findByTokenHash(
            $tokenHash
        );

        if ($session === null) {
            throw new InvalidArgumentException(
                'Invalid unlock session.'
            );
        }

        if ($session['status'] !== 'active') {
            throw new InvalidArgumentException(
                'Unlock session is no longer active.'
            );
        }

        $now = new DateTimeImmutable();
        $startedAt = new DateTimeImmutable($session['started_at']);
        $expiresAt = new DateTimeImmutable($session['expires_at']);

        if ($now >= $expiresAt) {
            $this->unlockSessionRepository->markExpired(
                (int) $session['id']
            );

            throw new InvalidArgumentException(
                'Unlock session has expired.'
            );
        }

        $elapsedSeconds = $now->getTimestamp()
                - $startedAt->getTimestamp();

        if (
            $elapsedSeconds
            < (int) $session['required_duration_seconds']
        ) {
            throw new InvalidArgumentException(
                'Required unlock duration has not elapsed.'
            );
        }

        $campaign = $this->campaignRepository->findById(
            (int) $session['campaign_id']
        );

        if ($campaign === null) {
            throw new RuntimeException(
                'Campaign associated with unlock session was not found.'
            );
        }

        $frequencyLimitSeconds = $campaign['frequency_limit_seconds'];

        $this->pdo->beginTransaction();

        try {
            $completed = $this->unlockSessionRepository->markCompleted(
                (int) $session['id']
            );

            if (!$completed) {
                throw new InvalidArgumentException(
                    'Unlock session was already completed.'
                );
            }

            if (
                $session['visitor_id'] !== null
                && $frequencyLimitSeconds !== null
            ) {
                $this->assertFrequencyLimitAllowsVisitor(
                    (int) $session['campaign_id'],
                    $session['visitor_id'],
                    (int) $frequencyLimitSeconds,
                    $now,
                );
            }

            $this->unlockCompletionRepository->create(
                (int) $session['id'],
                (int) $session['campaign_id'],
                $session['visitor_id'],
                $now->format('Y-m-d H:i:s'),
            );

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }
    }

    private function assertFrequencyLimitAllowsVisitor(
        int $campaignId,
        string $visitorId,
        int $frequencyLimitSeconds,
        ?DateTimeImmutable $now = null,
    ): void {
        $now ??= new DateTimeImmutable();

        $cutoff = $now
            ->modify("-{$frequencyLimitSeconds} seconds")
            ->format('Y-m-d H:i:s');

        $recentCompletions = $this->unlockCompletionRepository
            ->countRecentByCampaignAndVisitor(
                $campaignId,
                $visitorId,
                $cutoff,
            );

        if ($recentCompletions > 0) {
            throw new InvalidArgumentException(
                'Campaign frequency limit has not elapsed.'
            );
        }
    }
}
