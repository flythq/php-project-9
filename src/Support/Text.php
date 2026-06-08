<?php

namespace Hexlet\Code\Support;

class Text
{
    public static function preview(string $text = null, int $limit = 200): ?string
    {
        if ($text === null) {
            return null;
        }

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit) . '...';
    }
}
