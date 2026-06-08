<?php

namespace Hexlet\Code\Support;

use PDO;

class UrlCheck
{
    public static function getByUrlId(PDO $conn, int $urlId): array
    {
        $sql = "SELECT *
                FROM url_checks
                WHERE url_id = ?
                ORDER BY id DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$urlId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(
        PDO $conn,
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

        $stmt = $conn->prepare($sql);

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
