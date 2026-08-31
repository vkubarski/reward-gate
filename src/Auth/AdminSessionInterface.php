<?php

declare(strict_types=1);

namespace RewardGate\Auth;

interface AdminSessionInterface
{
    public function login(int $adminId): void;

    public function logout(): void;

    public function isAuthenticated(): bool;

    public function getAdminId(): ?int;
}
