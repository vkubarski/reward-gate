<?php

declare(strict_types=1);

namespace RewardGate\Repository;

use PDO;

final class AdminUserRepository implements AdminUserRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT *
             FROM admin_users
             WHERE username = :username
             LIMIT 1'
        );

        $statement->execute([
            'username' => $username,
        ]);

        $adminUser = $statement->fetch();

        return $adminUser === false
            ? null
            : $adminUser;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT *
             FROM admin_users
             WHERE id = :id
             LIMIT 1'
        );

        $statement->execute([
            'id' => $id,
        ]);

        $adminUser = $statement->fetch();

        return $adminUser === false
            ? null
            : $adminUser;
    }

    public function create(
        string $username,
        string $passwordHash
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO admin_users (
                username,
                password_hash
            ) VALUES (
                :username,
                :password_hash
            )'
        );

        $statement->execute([
            'username' => $username,
            'password_hash' => $passwordHash,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updatePasswordHash(
        int $id,
        string $passwordHash
    ): bool {
        $statement = $this->pdo->prepare(
            'UPDATE admin_users
             SET password_hash = :password_hash
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
        ]);

        return $statement->rowCount() > 0;
    }
}
