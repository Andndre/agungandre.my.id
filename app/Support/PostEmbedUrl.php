<?php

namespace App\Support;

class PostEmbedUrl
{
    public static function parts(string $url): ?array
    {
        $parts = parse_url($url);

        if (! filter_var($url, FILTER_VALIDATE_URL)
            || ! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])) {
            return null;
        }

        return $parts;
    }
}
