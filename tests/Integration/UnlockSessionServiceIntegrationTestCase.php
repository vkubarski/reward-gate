<?php

declare(strict_types=1);

namespace RewardGate\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use RewardGate\Database\Connection;

abstract class UnlockSessionServiceIntegrationTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $dbConfig = require __DIR__
                . '/../../config/database.test.php';

        $this->pdo = Connection::create(
            $dbConfig
        );
    }

    protected function tearDown(): void
    {
        $this->pdo->exec(
            'DELETE FROM unlock_completions'
        );

        $this->pdo->exec(
            'DELETE FROM unlock_sessions'
        );

        $this->pdo->exec(
            'DELETE FROM campaigns'
        );

        parent::tearDown();
    }
}
