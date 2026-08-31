<?php

declare(strict_types=1);

namespace RewardGate\Repository;

interface AdminUserRepositoryInterface
{
    public function findByUsername(string $username): ?array;

    public function findById(int $id): ?array;

    public function create(
        string $username,
        string $passwordHash
    ): int;

    public function updatePasswordHash(
        int $id,
        string $passwordHash
    ): bool;
}
