<?php

namespace Hexlet\Code\Support;

use PDO;

class Database
{
    public static function connect(): PDO
    {
        $params = parse_url($_ENV['DATABASE_URL']);

        $port = $params['port'] ?? '5432';
        $host = $params['host'] ?? '';
        $user = $params['user'] ?? '';
        $password = $params['pass'] ?? '';
        $name = ltrim($params['path'] ?? '', '/');

        $dsn = "pgsql:host={$host};port={$port};dbname={$name}"; //sslmode=require

        return new PDO(
            $dsn,
            $user,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}
