<?php

declare(strict_types=1);

namespace RewardGate\Database;

use PDO;

final class Connection
{
    public static function create(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['database'],
            $config['charset']
        );

        $pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $pdo->exec(
            "SET time_zone = '+00:00'"
        );

        return $pdo;
    }
}
