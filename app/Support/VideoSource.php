<?php

namespace App\Support;

class VideoSource
{
    private const YOUTUBE_HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'youtu.be',
        'www.youtu.be',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
    ];

    public static function youtubeId(string $value): ?string
    {
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value) === 1) {
            return $value;
        }

        $parts = parse_url($value);

        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');

        if (! in_array($host, self::YOUTUBE_HOSTS, true)) {
            return null;
        }

        parse_str($parts['query'] ?? '', $query);
        $videoId = $query['v'] ?? null;

        if (! is_string($videoId) && in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $videoId = trim($parts['path'] ?? '', '/');
            $videoId = explode('/', $videoId)[0];
        }

        if (! is_string($videoId) || preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) !== 1) {
            preg_match('~/(?:embed|shorts|live)/([A-Za-z0-9_-]{11})(?:/|$)~', $parts['path'] ?? '', $matches);
            $videoId = $matches[1] ?? null;
        }

        return is_string($videoId) && preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) === 1
            ? $videoId
            : null;
    }

    public static function supportsUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return false;
        }

        if (in_array(strtolower($parts['host'] ?? ''), self::YOUTUBE_HOSTS, true)) {
            return self::youtubeId($url) !== null;
        }

        $extension = strtolower(pathinfo($parts['path'] ?? '', PATHINFO_EXTENSION));

        return in_array($extension, ['mp4', 'm4v', 'webm', 'ogv', 'ogg'], true);
    }
}
