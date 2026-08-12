<?php

$host = 'localhost';
$database = 'reward_gate';
$username = 'reward_gate';
$password = '0d4769CRVjOpYBckeUfWLuvuR6jq7nS6AhvZKcl3abI=';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
    );

    $stmt = $pdo->query('SELECT DATABASE() AS database_name, VERSION() AS version');
    $result = $stmt->fetch();

    echo '<pre>';
    echo 'Database: ' . htmlspecialchars($result['database_name']) . PHP_EOL;
    echo 'Version: ' . htmlspecialchars($result['version']) . PHP_EOL;
    echo 'Connection: OK' . PHP_EOL;
    echo '</pre>';
} catch (PDOException $e) {
    http_response_code(500);

    echo '<pre>';
    echo 'Database connection failed.' . PHP_EOL;
    echo htmlspecialchars($e->getMessage()) . PHP_EOL;
    echo '</pre>';
}
