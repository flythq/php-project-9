<?php

namespace Hexlet\Code\Repositories;

use PDO;
use RuntimeException;

class UrlRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getById(int $id): array|false
    {
        $sql = "SELECT *
                FROM urls
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByName(string $name): array|false
    {
        $sql = "SELECT *
                FROM urls
                WHERE name = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$name]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(string $name, string $createdAt): int
    {
        $sql = "INSERT INTO urls (name, created_at)
                VALUES (:name, :created_at)";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            'name' => $name,
            'created_at' => $createdAt,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function getAll(): array
    {
        $sql = "SELECT id, name, created_at
                FROM urls
                ORDER BY created_at DESC";

        $stmt = $this->conn->query($sql);

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare SQL');
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
