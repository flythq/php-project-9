<?php

namespace Hexlet\Code\Support;

class Text
{
    public static function preview(?string $text, int $limit = 200): ?string
    {
        if ($text === null) {
            return null;
        }

        return mb_strlen($text) > $limit
            ? mb_substr($text, 0, $limit) . '...'
            : $text;
    }
}
