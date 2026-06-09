<?php

namespace Hexlet\Code\Support;

use PDO;
use RuntimeException;

class Url
{
    public static function getById(PDO $conn, int $id): array|false
    {
        $sql = "SELECT *
                FROM urls
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getByName(PDO $conn, string $name): array|false
    {
        $sql = "SELECT *
                FROM urls
                WHERE name = ?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$name]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create(PDO $conn, string $name, string $createdAt): int
    {
        $sql = "INSERT INTO urls (name, created_at)
                VALUES (:name, :created_at)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            'name' => $name,
            'created_at' => $createdAt,
        ]);

        return (int) $conn->lastInsertId();
    }

    public static function getAll(PDO $conn): array
    {
        $sql = "SELECT
                u.id,
                u.name,
                uc.status_code,
                uc.created_at AS last_check_at
                FROM urls u
                LEFT JOIN (
                    SELECT DISTINCT ON (url_id)
                    url_id,
                    status_code,
                    created_at
                    FROM url_checks
                    ORDER BY url_id, created_at DESC
                ) uc ON uc.url_id = u.id
                ORDER BY u.created_at DESC";

        $stmt = $conn->query($sql);

        if ($stmt === false) {
            throw new RuntimeException('Query failed');
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
