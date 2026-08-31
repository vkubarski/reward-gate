<?php

declare(strict_types=1);

namespace RewardGate\Service;

use RewardGate\Repository\AdminUserRepositoryInterface;

final class AdminAuthService implements AdminAuthServiceInterface
{
    public function __construct(
        private AdminUserRepositoryInterface $adminUserRepository
    ) {
    }

    public function authenticate(
        string $username,
        string $password
    ): ?array {
        $adminUser = $this->adminUserRepository->findByUsername(
            $username
        );

        if ($adminUser === null) {
            return null;
        }

        if (!password_verify(
            $password,
            $adminUser['password_hash']
        )) {
            return null;
        }

        if (password_needs_rehash(
            $adminUser['password_hash'],
            PASSWORD_DEFAULT
        )) {
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $this->adminUserRepository->updatePasswordHash(
                (int)$adminUser['id'],
                $passwordHash
            );
        }

        return $adminUser;
    }
}
