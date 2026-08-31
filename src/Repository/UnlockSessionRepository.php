<?php

declare(strict_types=1);

namespace RewardGate\Repository;

use PDO;

final class UnlockSessionRepository implements UnlockSessionRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function create(
        int $campaignId,
        string $tokenHash,
        ?string $visitorId,
        int $requiredDurationSeconds,
        string $startedAt,
        string $expiresAt,
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO unlock_sessions (
                                campaign_id,
                                token_hash,
                                visitor_id,
                                required_duration_seconds,
                                started_at,
                                expires_at
                        ) VALUES (
                                :campaign_id,
                                :token_hash,
                                :visitor_id,
                                :required_duration_seconds,
                                :started_at,
                                :expires_at
                        )'
        );

        $statement->execute([
                'campaign_id' => $campaignId,
                'token_hash' => $tokenHash,
                'visitor_id' => $visitorId,
                'required_duration_seconds' => $requiredDurationSeconds,
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByTokenHash(string $tokenHash): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT *
                         FROM unlock_sessions
                         WHERE token_hash = :token_hash
                         LIMIT 1'
        );

        $statement->execute([
                'token_hash' => $tokenHash,
        ]);

        $session = $statement->fetch();

        return $session === false
                ? null
                : $session;
    }

    public function markCompleted(int $id): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE unlock_sessions
                         SET status = \'completed\'
                         WHERE id = :id
                           AND status = \'active\''
        );

        $statement->execute([
                'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    public function markExpired(int $id): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE unlock_sessions
                         SET status = \'expired\'
                         WHERE id = :id
                           AND status = \'active\''
        );

        $statement->execute([
                'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }
}
