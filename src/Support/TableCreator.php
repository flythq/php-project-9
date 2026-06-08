<?php

namespace Hexlet\Code\Support;

use PDO;
use RuntimeException;

class TableCreator
{
    public static function run(PDO $conn): void
    {
        $databaseFilePath = dirname(__DIR__, 2) . '/database.sql';
        $sql = file_get_contents($databaseFilePath);

        if ($sql === false) {
            throw new RuntimeException('Unable to read database file');
        }

        $conn->exec($sql);
    }
}
