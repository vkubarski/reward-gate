<?php

declare(strict_types=1);

namespace RewardGate\Security;

interface CsrfTokenInterface
{
    public function get(): string;

    public function validate(?string $token): bool;
}
