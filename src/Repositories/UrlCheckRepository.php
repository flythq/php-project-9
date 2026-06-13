<?php

namespace Hexlet\Code\Repositories;

use PDO;
use RuntimeException;

class UrlCheckRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getByUrlId(int $urlId): array
    {
        $sql = "SELECT *
                FROM url_checks
                WHERE url_id = ?
                ORDER BY id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$urlId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastChecks(): array
    {
        $sql = "SELECT DISTINCT ON (url_id)
                url_id,
                status_code,
                created_at
                FROM url_checks
                ORDER BY url_id, created_at DESC";

        $stmt = $this->conn->query($sql);

        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare SQL');
        }

        $checks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $checksByUrlId = [];

        foreach ($checks as $check) {
            $checksByUrlId[$check['url_id']] = [
                'status_code' => $check['status_code'],
                'created_at' => $check['created_at'],
            ];
        }

        return $checksByUrlId;
    }

    public function create(
        int $urlId,
        int $statusCode,
        ?string $h1,
        ?string $title,
        ?string $description,
        string $createdAt
    ): void {
        $sql = "INSERT INTO url_checks
                (
                    url_id,
                    status_code,
                    h1,
                    title,
                    description,
                    created_at
                )
                VALUES
                (
                    :urlId,
                    :statusCode,
                    :h1,
                    :title,
                    :description,
                    :createdAt
                )";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            'urlId' => $urlId,
            'statusCode' => $statusCode,
            'h1' => $h1,
            'title' => $title,
            'description' => $description,
            'createdAt' => $createdAt,
        ]);
    }
}
