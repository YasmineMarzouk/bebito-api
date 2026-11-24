<?php

namespace Modules\Core\Support;

final class RichText
{
    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? '';
        $html = strip_tags($html, '<p><br><strong><b><ul><ol><li>');
        $html = preg_replace_callback(
            '/<(\/)?([a-z0-9]+)(?:\s[^>]*)?>/i',
            static fn (array $matches): string => '<'.($matches[1] ?? '').strtolower($matches[2]).'>',
            $html,
        ) ?? '';

        return str_replace(['<b>', '</b>'], ['<strong>', '</strong>'], trim($html)) ?: null;
    }
}
