<?php

declare(strict_types=1);

namespace RewardGate\Service;

interface AdminAuthServiceInterface
{
    public function authenticate(
        string $username,
        string $password
    ): ?array;
}
