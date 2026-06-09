<?php

namespace Tests;

use Hexlet\Code\Support\Text;
use PHPUnit\Framework\TestCase;

class TextTest extends  TestCase
{
    public function testPreviewTruncatesLongText(): void
    {
        $text = str_repeat('a', 250);

        $result = Text::preview($text);

        $this->assertEquals(
            str_repeat('a', 200) . '...',
            $result
        );
    }
}