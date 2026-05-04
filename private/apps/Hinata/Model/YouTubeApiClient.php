<?php

namespace App\Hinata\Model;

use Core\Logger;

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
        '@hinakoiofficial' => 'ひなこいYouTubeチャンネル',
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
        $ctx = $this->getUploadsPlaylistContext($channelId);
        return $ctx['playlist_id'] ?? null;
    }

    /**
     * 解決済み channelId とアップロード用プレイリスト ID をまとめて取得（resolve を二重に呼ばない用途）
     *
     * @return array{channel_id: string, playlist_id: string}|null
     */
    public function getUploadsPlaylistContext(string $channelInput): ?array {
        $resolved = $this->resolveChannelId($channelInput);
        if (!$resolved) {
            return null;
        }
        $data = $this->apiRequest('/channels', [
            'part' => 'contentDetails',
            'id'   => $resolved,
        ]);
        if (!$data || empty($data['items'][0])) {
            return null;
        }
        $playlistId = $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? null;
        if (!is_string($playlistId) || $playlistId === '') {
            return null;
        }

        return [
            'channel_id' => $resolved,
            'playlist_id' => $playlistId,
        ];
    }

    /**
     * チャンネル指定（channelId / @handle / URL）を channelId(UC...) に解決する。
     * - "UC..." はそのまま返す
     * - "@handle" or "https://www.youtube.com/@handle" は forHandle で解決
     */
    public function resolveChannelId(string $channelInput): ?string {
        $s = trim($channelInput);
        if ($s === '') return null;

        // Already a channelId
        if (preg_match('/^UC[A-Za-z0-9_-]{20,}$/', $s)) {
            return $s;
        }

        // URL -> @handle
        if (preg_match('#youtube\.com/@([^/?\s]+)#iu', $s, $m)) {
            $s = '@' . $m[1];
        }

        // @handle
        // - YouTube の @handle は英数字が多いが、日本語などのケースも受け入れて試す
        if (preg_match('/^@([^\/\s]+)$/u', $s, $m)) {
            $handle = $m[1];

            // 1) forHandle で解決（最優先）
            $data = $this->apiRequest('/channels', [
                'part' => 'id',
                'forHandle' => $handle,
            ]);
            $id = $data['items'][0]['id'] ?? null;
            if (is_string($id) && $id !== '') {
                return $id;
            }

            // 2) フォールバック: YouTube ページ HTML から channelId を抽出（API クォータ不要）
            // 日本語など forHandle が効かないケースを想定
            $pageUrl = 'https://www.youtube.com/@' . rawurlencode($handle);
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'header' => "User-Agent: Mozilla/5.0\r\nAccept-Language: ja,en;q=0.8\r\n",
                ],
            ]);
            $html = @file_get_contents($pageUrl, false, $ctx);
            if (is_string($html) && $html !== '') {
                if (preg_match('/\"channelId\":\"(UC[A-Za-z0-9_-]{20,})\"/', $html, $hm)) {
                    return $hm[1];
                }
            }

            // 3) 最終フォールバック: 検索（quota cost が重いので最後の手段）
            $sData = $this->apiRequest('/search', [
                'part' => 'snippet',
                'type' => 'channel',
                'q' => $handle,
                'maxResults' => 1,
            ]);
            $cid = $sData['items'][0]['id']['channelId'] ?? null;
            if (is_string($cid) && $cid !== '') {
                return $cid;
            }
        }

        return null;
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

        return $this->formatPlaylistItemsResponse($data);
    }

    /**
     * playlistItems.list の生 JSON を getPlaylistItems と同形に整形（並列取得後の解釈用）
     */
    public function formatPlaylistItemsResponse(?array $data): ?array {
        if (!$data || !isset($data['items'])) {
            return null;
        }

        $videos = [];
        foreach ($data['items'] as $item) {
            $snippet = $item['snippet'] ?? [];
            $videoId = $snippet['resourceId']['videoId'] ?? null;
            if (!$videoId) {
                continue;
            }

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
     * 複数リクエストを最大 $maxConcurrency 本まで同時に実行（playlistItems の並列化用）
     *
     * @param list<array{endpoint: string, params?: array<string, mixed>}> $requests
     * @return list<?array> 入力と同じ順の生 JSON（失敗時 null）
     */
    public function apiRequestConcurrent(array $requests, int $maxConcurrency = 2): array {
        $n = count($requests);
        if ($n === 0) {
            return [];
        }
        if (!$this->isConfigured()) {
            return array_fill(0, $n, null);
        }
        if (!function_exists('curl_multi_init')) {
            $out = [];
            foreach ($requests as $req) {
                $out[] = $this->apiRequest($req['endpoint'], $req['params'] ?? []);
            }

            return $out;
        }

        $maxConcurrency = max(1, $maxConcurrency);
        $results = array_fill(0, $n, null);
        $mh = curl_multi_init();
        if ($mh === false) {
            foreach ($requests as $i => $req) {
                $results[$i] = $this->apiRequest($req['endpoint'], $req['params'] ?? []);
            }

            return $results;
        }

        /** @var array<int, \CurlHandle> $active */
        $active = [];
        /** @var array<int, int> $curlIdToIndex */
        $curlIdToIndex = [];
        $nextIndex = 0;

        $startNext = function () use (&$active, &$curlIdToIndex, &$nextIndex, &$results, $mh, $requests, $n, $maxConcurrency): void {
            while (count($active) < $maxConcurrency && $nextIndex < $n) {
                $i = $nextIndex++;
                $ch = $this->createCurlHandleForRequest($requests[$i]['endpoint'], $requests[$i]['params'] ?? []);
                if ($ch === false) {
                    $results[$i] = null;

                    continue;
                }
                $curlId = (int) $ch;
                $active[$i] = $ch;
                $curlIdToIndex[$curlId] = $i;
                curl_multi_add_handle($mh, $ch);
            }
        };

        $startNext();

        do {
            while (($mrc = curl_multi_exec($mh, $running)) === CURLM_CALL_MULTI_PERFORM) {
            }
            if ($mrc !== CURLM_OK) {
                foreach ($active as $chErr) {
                    curl_multi_remove_handle($mh, $chErr);
                    curl_close($chErr);
                }
                $active = [];
                $curlIdToIndex = [];
                break;
            }
            if ($running) {
                curl_multi_select($mh, 1.0);
            }
            while ($info = curl_multi_info_read($mh)) {
                if ($info['msg'] !== CURLMSG_DONE) {
                    continue;
                }
                /** @var \CurlHandle $ch */
                $ch = $info['handle'];
                $cid = (int) $ch;
                $reqIndex = $curlIdToIndex[$cid] ?? null;
                $body = curl_multi_getcontent($ch);
                $errno = (int) ($info['result'] ?? CURLE_OK);
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
                if ($reqIndex !== null) {
                    unset($active[$reqIndex], $curlIdToIndex[$cid]);
                    $results[$reqIndex] = ($errno === 0) ? $this->parseApiJsonBody($body) : null;
                }
                $startNext();
            }
        } while ($running || $active !== [] || $nextIndex < $n);

        curl_multi_close($mh);

        return $results;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function createCurlHandleForRequest(string $endpoint, array $params): \CurlHandle|false {
        if (!$this->isConfigured()) {
            return false;
        }
        $params['key'] = $this->apiKey;
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);
        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        return $ch;
    }

    private function parseApiJsonBody(?string $response): ?array {
        if ($response === null || $response === '') {
            return null;
        }
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('YouTube API JSON parse error: ' . json_last_error_msg());

            return null;
        }
        if (isset($data['error'])) {
            Logger::error('YouTube API error: ' . json_encode($data['error']));

            return null;
        }

        return $data;
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
     * - duration ≤ 180秒 → short（Shorts の上限が 3 分のケースに対応）
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
        if ($duration !== '' && self::parseDurationSeconds($duration) <= 180) {
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
            Logger::error('YouTube API request failed: ' . $url);
            return null;
        }

        return $this->parseApiJsonBody($response);
    }
}
