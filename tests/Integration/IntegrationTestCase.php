<?php

declare(strict_types=1);

namespace RewardGate\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $dbConfig = require __DIR__
                . '/../../config/database.test.php';

        $this->pdo = new PDO(
            "mysql:host={$dbConfig['host']};"
                . "dbname={$dbConfig['database']};"
                . "charset={$dbConfig['charset']}",
            $dbConfig['username'],
            $dbConfig['password'],
            [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
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
