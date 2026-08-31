<?php

declare(strict_types=1);

namespace RewardGate\Repository;

use InvalidArgumentException;
use PDO;

final class CampaignRepository implements CampaignRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    private function hydrate(array $campaign): array
    {
        if ($campaign['presentation_settings'] !== null) {
            $campaign['presentation_settings'] = json_decode(
                $campaign['presentation_settings'],
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        return $campaign;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT *
                FROM campaigns
                WHERE id = :id'
        );

        $statement->execute([
                'id' => $id,
        ]);

        $campaign = $statement->fetch();

        return $campaign === false
            ? null
            : $this->hydrate($campaign);
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
        $statement = $this->pdo->prepare(
            'INSERT INTO campaigns (
                name,
                status,
                presentation_type,
                presentation_settings,
                unlock_method,
                timer_duration_seconds,
                frequency_limit_seconds,
                reward_type
            ) VALUES (
                :name,
                :status,
                :presentation_type,
                :presentation_settings,
                :unlock_method,
                :timer_duration_seconds,
                :frequency_limit_seconds,
                :reward_type
            )'
        );

        $statement->execute([
            'name' => $name,
            'status' => $status,
            'presentation_type' => $presentationType,
            'presentation_settings' => $presentationSettings === null
                ? null
                : json_encode($presentationSettings, JSON_THROW_ON_ERROR),
            'unlock_method' => $unlockMethod,
            'timer_duration_seconds' => $timerDurationSeconds,
            'frequency_limit_seconds' => $frequencyLimitSeconds,
            'reward_type' => $rewardType,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function findAll(
        string $orderBy = 'created_at',
        string $direction = 'DESC',
        int $limit = 20,
        int $offset = 0,
    ): array {
        $orderBy = match($orderBy) {
            'id' => 'id',
            'name' => 'name',
            'status' => 'status',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            default => 'created_at',
        };

        $direction = match(strtoupper($direction)) {
            'ASC' => 'ASC',
            'DESC' => 'DESC',
            default => 'DESC',
        };

        if ($limit < 1) {
            throw new InvalidArgumentException('Limit must be greater than zero.');
        }

        if ($offset < 0) {
            throw new InvalidArgumentException('Offset cannot be negative.');
        }

        $statement = $this->pdo->query(
            "SELECT *
             FROM campaigns
             ORDER BY {$orderBy} {$direction}
             LIMIT {$limit} OFFSET {$offset}"
        );

        return array_map(
            fn (array $campaign): array => $this->hydrate($campaign),
            $statement->fetchAll()
        );
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'name',
            'status',
            'presentation_type',
            'presentation_settings',
            'unlock_method',
            'timer_duration_seconds',
            'frequency_limit_seconds',
            'reward_type',
        ];

        $fields = array_intersect_key($data, array_flip($allowedFields));

        if ($fields === []) {
            return false;
        }

        $set = [];
        $parameters = ['id' => $id];

        foreach ($fields as $field => $value) {
            $set[] = "{$field} = :{$field}";
            $parameters[$field] = $field === 'presentation_settings'
                ? ($value !== null
                    ? json_encode($value, JSON_THROW_ON_ERROR)
                    : null)
                : $value;
        }

        $statement = $this->pdo->prepare(
            'UPDATE campaigns SET ' . implode(', ', $set) . ' WHERE id = :id'
        );

        $statement->execute($parameters);

        return $statement->rowCount() > 0;
    }
}
