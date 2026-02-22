<?php

namespace App\Hinata\Model;

/**
 * YouTube Data API v3 クライアント + oEmbed ユーティリティ
 * 物理パス: haitaka/private/apps/Hinata/Model/YouTubeApiClient.php
 */
class YouTubeApiClient {
    private string $apiKey = '';
    private string $baseUrl = 'https://www.googleapis.com/youtube/v3';

    /** プリセットチャンネル（日向坂46公式） */
    public const PRESET_CHANNELS = [
        'UCR0V48DJyWbwEAdxLL5FjxA' => '日向坂46公式チャンネル',
        'UCOB24f8lQBCnVqPZXOkVpOg' => '日向坂ちゃんねる',
    ];

    public function __construct() {
        $this->apiKey = $_ENV['YOUTUBE_API_KEY'] ?? '';
        if (empty($this->apiKey)) {
            $envPath = __DIR__ . '/../../../.env';
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (str_starts_with(trim($line), '#')) continue;
                    if (str_contains($line, '=')) {
                        [$key, $val] = explode('=', $line, 2);
                        if (trim($key) === 'YOUTUBE_API_KEY') {
                            $this->apiKey = trim($val);
                            break;
                        }
                    }
                }
            }
        }
    }

    public function isConfigured(): bool {
        return !empty($this->apiKey);
    }

    /**
     * チャンネルの「アップロード動画」プレイリストIDを取得
     */
    public function getUploadsPlaylistId(string $channelId): ?string {
        $data = $this->apiRequest('/channels', [
            'part' => 'contentDetails',
            'id'   => $channelId,
        ]);
        return $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? null;
    }

    /**
     * プレイリスト内の動画一覧を取得（チャンネル動画一覧に使用）
     * quota cost: 1 unit per call
     */
    public function getPlaylistItems(string $playlistId, int $maxResults = 25, ?string $pageToken = null): ?array {
        $params = [
            'part'       => 'snippet',
            'playlistId' => $playlistId,
            'maxResults' => min($maxResults, 50),
        ];
        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $data = $this->apiRequest('/playlistItems', $params);
        if (!$data || !isset($data['items'])) return null;

        $videos = [];
        foreach ($data['items'] as $item) {
            $snippet = $item['snippet'] ?? [];
            $videoId = $snippet['resourceId']['videoId'] ?? null;
            if (!$videoId) continue;

            $videos[] = [
                'video_id'      => $videoId,
                'title'         => $snippet['title'] ?? '',
                'description'   => $snippet['description'] ?? '',
                'thumbnail_url' => $snippet['thumbnails']['medium']['url']
                                   ?? $snippet['thumbnails']['default']['url']
                                   ?? "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg",
                'published_at'  => $snippet['publishedAt'] ?? null,
                'channel_title' => $snippet['channelTitle'] ?? '',
                'media_type'    => self::detectMediaType($snippet),
            ];
        }

        $videos = $this->enrichMediaTypes($videos);

        return [
            'videos'         => $videos,
            'next_page_token' => $data['nextPageToken'] ?? null,
            'prev_page_token' => $data['prevPageToken'] ?? null,
            'total_results'  => $data['pageInfo']['totalResults'] ?? 0,
        ];
    }

    /**
     * キーワード検索（quota cost: 100 units per call）
     */
    public function searchVideos(string $query, int $maxResults = 25, ?string $pageToken = null, ?string $channelId = null): ?array {
        $params = [
            'part'       => 'snippet',
            'type'       => 'video',
            'q'          => $query,
            'maxResults' => min($maxResults, 50),
            'order'      => 'date',
        ];
        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }
        if ($channelId) {
            $params['channelId'] = $channelId;
        }

        $data = $this->apiRequest('/search', $params);
        if (!$data || !isset($data['items'])) return null;

        $videos = [];
        foreach ($data['items'] as $item) {
            $snippet = $item['snippet'] ?? [];
            $videoId = $item['id']['videoId'] ?? null;
            if (!$videoId) continue;

            $videos[] = [
                'video_id'      => $videoId,
                'title'         => $snippet['title'] ?? '',
                'description'   => $snippet['description'] ?? '',
                'thumbnail_url' => $snippet['thumbnails']['medium']['url']
                                   ?? $snippet['thumbnails']['default']['url']
                                   ?? "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg",
                'published_at'  => $snippet['publishedAt'] ?? null,
                'channel_title' => $snippet['channelTitle'] ?? '',
                'media_type'    => self::detectMediaType($snippet),
            ];
        }

        $videos = $this->enrichMediaTypes($videos);

        return [
            'videos'         => $videos,
            'next_page_token' => $data['nextPageToken'] ?? null,
            'prev_page_token' => $data['prevPageToken'] ?? null,
            'total_results'  => $data['pageInfo']['totalResults'] ?? 0,
        ];
    }

    /**
     * 動画IDリストから詳細情報を一括取得（videos.list / quota cost: 1 unit per call）
     * 最大50件ずつバッチ処理。完全な description を返す。
     * contentDetails.duration で Shorts (≤60s) を正確に判定。
     *
     * @return array<string, array> videoId => ['title','description','thumbnail_url','published_at','channel_title','media_type']
     */
    public function getVideoDetails(array $videoIds): array {
        $result = [];
        foreach (array_chunk($videoIds, 50) as $chunk) {
            $data = $this->apiRequest('/videos', [
                'part' => 'snippet,contentDetails,liveStreamingDetails',
                'id'   => implode(',', $chunk),
            ]);
            if (!$data || empty($data['items'])) continue;

            foreach ($data['items'] as $item) {
                $id = $item['id'];
                $snippet = $item['snippet'] ?? [];
                $contentDetails = $item['contentDetails'] ?? [];
                $liveDetails = $item['liveStreamingDetails'] ?? null;
                $result[$id] = [
                    'title'         => $snippet['title'] ?? '',
                    'description'   => $snippet['description'] ?? '',
                    'thumbnail_url' => $snippet['thumbnails']['medium']['url']
                                       ?? $snippet['thumbnails']['default']['url']
                                       ?? "https://img.youtube.com/vi/{$id}/mqdefault.jpg",
                    'published_at'  => $snippet['publishedAt'] ?? null,
                    'channel_title' => $snippet['channelTitle'] ?? '',
                    'media_type'    => self::detectMediaTypeAdvanced($snippet, $contentDetails, $liveDetails),
                ];
            }
        }
        return $result;
    }

    /**
     * oEmbed で動画情報を取得（APIキー不要）
     *
     * @param string $url YouTube動画URL
     * @return array|null  ['title', 'author_name', 'thumbnail_url'] or null
     */
    public static function fetchOembed(string $url): ?array {
        $oembedUrl = 'https://www.youtube.com/oembed?format=json&url=' . urlencode($url);

        $ctx = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 10, 'ignore_errors' => true],
        ]);
        $response = @file_get_contents($oembedUrl, false, $ctx);
        if ($response === false) return null;

        $data = json_decode($response, true);
        if (!$data || !isset($data['title'])) return null;

        return [
            'title'         => $data['title'],
            'description'   => '',
            'author_name'   => $data['author_name'] ?? '',
            'thumbnail_url' => $data['thumbnail_url'] ?? '',
        ];
    }

    /**
     * 動画リストに videos.list の contentDetails を使った正確な media_type を付与する。
     * playlistItems / search の結果に対して呼び出す（追加 quota: 1 unit per 50件）。
     */
    private function enrichMediaTypes(array $videos): array {
        $ids = array_map(fn($v) => $v['video_id'], $videos);
        if (empty($ids)) return $videos;

        $details = [];
        foreach (array_chunk($ids, 50) as $chunk) {
            $data = $this->apiRequest('/videos', [
                'part' => 'contentDetails,liveStreamingDetails',
                'id'   => implode(',', $chunk),
            ]);
            if (!$data || empty($data['items'])) continue;
            foreach ($data['items'] as $item) {
                $details[$item['id']] = [
                    'contentDetails'       => $item['contentDetails'] ?? [],
                    'liveStreamingDetails' => $item['liveStreamingDetails'] ?? null,
                ];
            }
        }

        foreach ($videos as &$v) {
            $vid = $v['video_id'];
            if (isset($details[$vid])) {
                $snippet = ['title' => $v['title'], 'liveBroadcastContent' => 'none'];
                $v['media_type'] = self::detectMediaTypeAdvanced(
                    $snippet,
                    $details[$vid]['contentDetails'],
                    $details[$vid]['liveStreamingDetails']
                );
            }
        }
        unset($v);
        return $videos;
    }

    /**
     * snippet + contentDetails + liveStreamingDetails から media_type を正確に判定
     *
     * - liveStreamingDetails が存在 → live（アーカイブ含む）
     * - duration ≤ 60秒 → short
     * - それ以外 → video
     */
    private static function detectMediaTypeAdvanced(array $snippet, array $contentDetails, ?array $liveDetails): string {
        if ($liveDetails !== null) {
            return 'live';
        }

        $live = $snippet['liveBroadcastContent'] ?? 'none';
        if ($live === 'live' || $live === 'upcoming') {
            return 'live';
        }

        $duration = $contentDetails['duration'] ?? '';
        if ($duration !== '' && self::parseDurationSeconds($duration) <= 60) {
            return 'short';
        }

        $title = $snippet['title'] ?? '';
        if (preg_match('/#shorts?\b/i', $title)) {
            return 'short';
        }

        return 'video';
    }

    /**
     * snippet 情報だけで media_type を推定（フォールバック用）
     */
    private static function detectMediaType(array $snippet): string {
        $live = $snippet['liveBroadcastContent'] ?? 'none';
        if ($live === 'live' || $live === 'upcoming') {
            return 'live';
        }
        $title = $snippet['title'] ?? '';
        if (preg_match('/#shorts?\b/i', $title)) {
            return 'short';
        }
        return 'video';
    }

    /**
     * ISO 8601 duration (PT1M30S etc.) を秒数に変換
     */
    private static function parseDurationSeconds(string $iso): int {
        if (!preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/', $iso, $m)) {
            return 9999;
        }
        return (int)($m[1] ?? 0) * 3600 + (int)($m[2] ?? 0) * 60 + (int)($m[3] ?? 0);
    }

    private function apiRequest(string $endpoint, array $params): ?array {
        if (!$this->isConfigured()) return null;

        $params['key'] = $this->apiKey;
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "Accept: application/json\r\n",
                'timeout' => 15,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            error_log('YouTube API request failed: ' . $url);
            return null;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('YouTube API JSON parse error: ' . json_last_error_msg());
            return null;
        }

        if (isset($data['error'])) {
            error_log('YouTube API error: ' . json_encode($data['error']));
            return null;
        }

        return $data;
    }
}
