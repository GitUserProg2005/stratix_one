<?php

namespace App\Enums;

enum ScraperCompressionLevel: string
{
    case ScriptStyle = 'script_style';
    case Extended = 'extended';
    case TextOnly = 'text_only';

    /** @return list<string> */
    public function tagsToRemove(): array
    {
        return match ($this) {
            self::Extended => ['script', 'style', 'head', 'header', 'footer', 'nav', 'aside'],
            self::TextOnly => ['script', 'style', 'head', 'header', 'footer', 'nav', 'aside', 'img', 'table', 'tbody', 'thead', 'tfoot', 'tr', 'td', 'th'],
            self::ScriptStyle => ['script', 'style'],
        };
    }

    public function stripsNoiseByClass(): bool
    {
        return $this !== self::ScriptStyle;
    }

    public function isTextOnly(): bool
    {
        return $this === self::TextOnly;
    }
}
