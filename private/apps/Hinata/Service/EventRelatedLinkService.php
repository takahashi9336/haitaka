<?php

namespace App\Hinata\Service;

use App\Hinata\Model\MediaAssetModel;
use PDO;

/**
 * イベント「関連リンク」の正規化・種別判定・非正規化（event_url / collaboration_urls / hn_event_movies）。
 * クライアントと同一優先度: youtube > tokusetsu > collab > other。
 */
class EventRelatedLinkService {
    public const KINDS = ['youtube', 'tokusetsu', 'collab', 'other'];

    public const MAX_LINKS = 20;

    /** @return list<string> */
    public static function parseTokusetsuDomainsFromEnv(): array {
        $raw = getenv('HINATA_EVENT_TOKUSETSU_DOMAINS');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * 正規化: https 化・ホスト小文字・末尾スラッシュ除去（ルートは path 省略）・utm_* クエリ除去。
     */
    public static function normalizeUrl(string $url): ?string {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }
        $host = strtolower((string)$parts['host']);
        $rawPath = $parts['path'] ?? '';
        if ($rawPath === '' || $rawPath === '/') {
            $path = '';
        } else {
            $norm = '/' . trim($rawPath, '/');
            $path = $norm === '/' ? '' : $norm;
        }

        $queryParts = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $parsedQuery);
            if (is_array($parsedQuery)) {
                foreach ($parsedQuery as $k => $v) {
                    if (preg_match('/^utm_/i', (string)$k)) {
                        continue;
                    }
                    $queryParts[$k] = $v;
                }
            }
        }
        $qs = http_build_query($queryParts);
        $scheme = 'https';
        $out = "{$scheme}://{$host}{$path}";
        if ($qs !== '') {
            $out .= '?' . $qs;
        }
        if (!empty($parts['fragment'])) {
            $out .= '#' . $parts['fragment'];
        }

        return $out;
    }

    public static function isYoutubeUrl(MediaAssetModel $mediaModel, string $normalizedUrl): bool {
        $p = $mediaModel->parseUrl($normalizedUrl);

        return $p !== null && ($p['platform'] ?? '') === 'youtube';
    }

    /** @param list<string> $tokusetsuExtraDomains */
    public static function hostIsTokusetsu(string $host, array $tokusetsuExtraDomains): bool {
        if ($host === 'hinatazaka46.com' || str_ends_with($host, '.hinatazaka46.com')) {
            return true;
        }
        foreach ($tokusetsuExtraDomains as $d) {
            $d = strtolower(trim((string)$d));
            if ($d === '') {
                continue;
            }
            if ($host === $d || str_ends_with($host, '.' . $d)) {
                return true;
            }
        }

        return false;
    }

    private static function isYoutubeHost(string $host): bool {
        return $host === 'youtu.be'
            || preg_match('/(^|\.)youtube\.com$/', $host) === 1
            || preg_match('/(^|\.)youtube-nocookie\.com$/', $host) === 1;
    }

    /**
     * @param list<string> $tokusetsuExtraDomains
     */
    public static function classifyKind(MediaAssetModel $mediaModel, string $normalizedUrl, array $tokusetsuExtraDomains = []): string {
        if (self::isYoutubeUrl($mediaModel, $normalizedUrl)) {
            return 'youtube';
        }
        $parts = parse_url($normalizedUrl);
        if ($parts === false || empty($parts['host'])) {
            return 'other';
        }
        $host = strtolower((string)$parts['host']);
        if (self::hostIsTokusetsu($host, $tokusetsuExtraDomains)) {
            return 'tokusetsu';
        }
        $path = strtolower((string)($parts['path'] ?? ''));
        foreach (['/collab', '/campaign', '/cp/', '/special'] as $token) {
            if (str_contains($path, $token)) {
                return 'collab';
            }
        }
        if (!self::isYoutubeHost($host) && !self::hostIsTokusetsu($host, $tokusetsuExtraDomains)) {
            return 'collab';
        }

        return 'other';
    }

    /**
     * @param array<int, mixed> $incoming
     *
     * @return array{
     *   links: list<array{url:string, kind:string, manual_override:bool}>,
     *   event_url: string,
     *   collaboration_urls_json: ?string,
     *   related_links_json: string,
     *   first_youtube_normalized: ?string,
     * }
     */
    public static function normalizePayload(array $incoming, MediaAssetModel $mediaModel): array {
        /** @var list<array<string, mixed>> */
        $incoming = array_values(array_filter($incoming, static function ($row): bool {
            return is_array($row);
        }));
        $count = count($incoming);
        if ($count > self::MAX_LINKS) {
            throw new \InvalidArgumentException('関連リンクは最大' . self::MAX_LINKS . '件までです');
        }

        $extra = self::parseTokusetsuDomainsFromEnv();
        /** @var list<array{url:string, kind:string, manual_override:bool}> */
        $links = [];

        foreach ($incoming as $row) {
            $rawUrl = isset($row['url']) ? trim((string)$row['url']) : '';
            if ($rawUrl === '') {
                continue;
            }
            $normalized = self::normalizeUrl($rawUrl);
            if ($normalized === null) {
                throw new \InvalidArgumentException('無効なURLです: ' . $rawUrl);
            }

            $manual = !empty($row['manual_override']);
            if ($manual) {
                $kind = strtolower(trim((string)($row['kind'] ?? '')));
                if (!in_array($kind, self::KINDS, true)) {
                    $kind = 'other';
                }
            } else {
                $kind = self::classifyKind($mediaModel, $normalized, $extra);
            }

            $links[] = [
                'url'             => $normalized,
                'kind'            => $kind,
                'manual_override' => $manual,
            ];
        }

        $eventUrl = '';
        foreach ($links as $L) {
            if ($L['kind'] === 'tokusetsu') {
                $eventUrl = $L['url'];
                break;
            }
        }

        $collaborationUrls = [];
        foreach ($links as $L) {
            if ($L['kind'] === 'collab' || $L['kind'] === 'other') {
                $collaborationUrls[] = $L['url'];
            }
        }
        $collaborationUrlsJson = $collaborationUrls === [] ? null : json_encode(array_values($collaborationUrls), JSON_UNESCAPED_UNICODE);

        $firstYoutube = null;
        foreach ($links as $L) {
            if ($L['kind'] === 'youtube' && self::isYoutubeUrl($mediaModel, $L['url'])) {
                $firstYoutube = $L['url'];
                break;
            }
        }

        return [
            'links'                       => $links,
            'event_url'                   => $eventUrl,
            'collaboration_urls_json'    => $collaborationUrlsJson,
            'related_links_json'         => json_encode($links, JSON_UNESCAPED_UNICODE),
            'first_youtube_normalized'   => $firstYoutube,
        ];
    }

    public static function syncYoutubeMovie(
        PDO $pdo,
        int $eventId,
        ?string $firstYoutubeNormalized,
        string $eventTitle,
        MediaAssetModel $mediaModel,
    ): void {
        $pdo->prepare('DELETE FROM hn_event_movies WHERE event_id = ?')->execute([$eventId]);

        if ($firstYoutubeNormalized === null || trim($firstYoutubeNormalized) === '') {
            return;
        }

        $parsed = $mediaModel->parseUrl($firstYoutubeNormalized);
        if ($parsed === null || ($parsed['platform'] ?? '') !== 'youtube') {
            return;
        }

        $assetId = $mediaModel->findOrCreateAsset(
            $parsed['platform'],
            $parsed['media_key'],
            $parsed['sub_key'],
            $eventTitle
        );

        if ($assetId) {
            $mediaModel->findOrCreateMetadata((int)$assetId, 'Event');
            $stmt = $pdo->prepare('INSERT INTO hn_event_movies (event_id, movie_id) VALUES (?, ?)');
            $stmt->execute([$eventId, $assetId]);
        }
    }

    /**
     * レガシー列から編集用チップ状態を復元する（順: 特設枠として event_url → YouTube(video_key) → collaboration_urls）。
     *
     * @param array<string, mixed> $eventRow GET 済みイベント行（video_key が JOIN で付いている想定でも可）
     *
     * @return list<array{url:string, kind:string, manual_override:bool}>
     */
    public static function buildLegacyLinksForEditor(array $eventRow, MediaAssetModel $mediaModel): array {
        $existing = $eventRow['related_links'] ?? null;
        if (is_string($existing) && trim($existing) !== '') {
            try {
                /** @var mixed $decoded */
                $decoded = json_decode($existing, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    if ($decoded === []) {
                        return [];
                    }
                    /** @var list<array{url:string, kind:string, manual_override:bool}> $out */
                    $out = [];
                    foreach ($decoded as $item) {
                        if (!is_array($item) || empty($item['url'])) {
                            continue;
                        }
                        $ku = strtolower((string)($item['kind'] ?? 'other'));
                        if (!in_array($ku, self::KINDS, true)) {
                            $ku = 'other';
                        }
                        $out[] = [
                            'url'             => (string)$item['url'],
                            'kind'            => $ku,
                            'manual_override' => !empty($item['manual_override']),
                        ];
                    }

                    return $out;
                }
            } catch (\Throwable) {
                // fall through to legacy synthesis
            }
        }

        $extra = self::parseTokusetsuDomainsFromEnv();
        /** @var list<array{url:string, kind:string, manual_override:bool}> */
        $links = [];

        $eventUrlRaw = isset($eventRow['event_url']) ? trim((string)$eventRow['event_url']) : '';
        if ($eventUrlRaw !== '') {
            $n = self::normalizeUrl($eventUrlRaw);
            if ($n !== null) {
                $links[] = [
                    'url'             => $n,
                    'kind'            => 'tokusetsu',
                    'manual_override' => false,
                ];
            }
        }

        $vk = isset($eventRow['video_key']) ? trim((string)$eventRow['video_key']) : '';
        if ($vk !== '') {
            $yt = 'https://www.youtube.com/watch?v=' . rawurlencode($vk);
            $links[] = [
                'url'             => self::normalizeUrl($yt) ?? $yt,
                'kind'            => 'youtube',
                'manual_override' => false,
            ];
        }

        $rawCollab = $eventRow['collaboration_urls'] ?? null;
        $decodedCollab = null;
        if (is_array($rawCollab)) {
            $decodedCollab = $rawCollab;
        } elseif (is_string($rawCollab) && trim($rawCollab) !== '') {
            try {
                /** @phpstan-ignore-next-line */
                $decodedCollab = json_decode($rawCollab, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                $decodedCollab = [];
            }
        }
        if (is_array($decodedCollab)) {
            foreach ($decodedCollab as $u) {
                if (!is_string($u) || trim($u) === '') {
                    continue;
                }
                $n = self::normalizeUrl(trim($u));
                if ($n === null) {
                    continue;
                }
                $links[] = [
                    'url'             => $n,
                    'kind'            => self::classifyKind($mediaModel, $n, $extra),
                    'manual_override' => false,
                ];
            }
        }

        return array_slice($links, 0, self::MAX_LINKS);
    }
}
