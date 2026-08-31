<?php

declare(strict_types=1);

namespace RewardGate\Security;

interface VisitorIdInterface
{
    public function get(): string;
}
