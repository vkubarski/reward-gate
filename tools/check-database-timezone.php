<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dbConfig = require __DIR__ . '/../config/database.php';

$pdo = \RewardGate\Database\Connection::create(
    $dbConfig
);

$result = $pdo->query(
    'SELECT
        NOW() AS database_now,
        UTC_TIMESTAMP() AS utc_now,
        @@session.time_zone AS session_timezone,
        @@global.time_zone AS global_timezone,
        @@system_time_zone AS system_timezone,
        TIMESTAMPDIFF(
            SECOND,
            UTC_TIMESTAMP(),
            NOW()
        ) AS offset_seconds'
)->fetch();

print_r($result);
