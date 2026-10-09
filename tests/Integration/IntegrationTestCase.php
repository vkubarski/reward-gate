<?php

declare(strict_types=1);

namespace RewardGate\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use RewardGate\Database\Connection;

abstract class IntegrationTestCase extends TestCase
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

        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        parent::tearDown();
    }
}
