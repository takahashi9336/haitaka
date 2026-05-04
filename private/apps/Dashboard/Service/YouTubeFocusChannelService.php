<?php

namespace App\Dashboard\Service;

use App\Hinata\Model\YouTubeApiClient;

/**
 * ダッシュボード「YouTube 集中視聴」用: 環境変数で指定したチャンネルごとに
 * 通常動画（Short 以外）または Shorts の直近 N 件を取得する。
 */
class YouTubeFocusChannelService {
    private const PER_CHANNEL_LIMIT = 3;
    private const PLAYLIST_PAGE_SIZE = 50;
    /** 1チャンネルあたり走査する playlistItems 呼び出しの上限 */
    private const MAX_PLAYLIST_PAGES = 5;
    /** playlistItems.list の同時実行数（チャンネル間） */
    private const FETCH_PARALLELISM = 2;
    private const CACHE_TTL_SECONDS = 1800; // 30 分（画面文言と一致）

    private string $cacheDir;

    public function __construct() {
        $this->cacheDir = dirname(__DIR__, 3) . '/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * @return array{
     *   configured: bool,
     *   api_configured: bool,
     *   cached: bool,
     *   channels: list<array{
     *     input_spec: string,
     *     mode: string,
     *     mode_label: string,
     *     channel_id: ?string,
     *     channel_title: string,
     *     error: ?string,
     *     videos: list<array<string, mixed>>
     *   }>
     * }
     */
    public function getFeed(bool $forceRefresh = false): array {
        $raw = trim((string) ($_ENV['DASHBOARD_YOUTUBE_FOCUS_CHANNELS'] ?? ''));
        $yt = new YouTubeApiClient();
        $apiOk = $yt->isConfigured();

        $empty = [
            'configured' => $raw !== '',
            'api_configured' => $apiOk,
            'cached' => false,
            'channels' => [],
        ];

        if ($raw === '') {
            return $empty;
        }
        if (!$apiOk) {
            return $empty;
        }

        $cacheKey = 'dashboard_youtube_focus_' . hash('sha256', $raw);
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';

        if (!$forceRefresh && is_file($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL_SECONDS) {
            $rawJson = @file_get_contents($cacheFile);
            if ($rawJson !== false) {
                $decoded = json_decode($rawJson, true);
                if (is_array($decoded) && isset($decoded['channels']) && is_array($decoded['channels'])) {
                    $decoded['cached'] = true;
                    $decoded['configured'] = true;
                    $decoded['api_configured'] = true;
                    return $decoded;
                }
            }
        }

        $entries = $this->parseEnvEntries($raw);
        if ($entries === []) {
            return [
                'configured' => false,
                'api_configured' => $apiOk,
                'cached' => false,
                'channels' => [],
            ];
        }

        $channels = $this->fetchAllFocusChannels($yt, $entries);

        $payload = [
            'configured' => true,
            'api_configured' => true,
            'cached' => false,
            'channels' => $channels,
        ];
        @file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $payload;
    }

    /**
     * 環境変数の各チャンネルを最大 {@see PER_CHANNEL_LIMIT} 件まで取得。
     * アップロード用プレイリストの解決は直列、playlistItems は最大 {@see FETCH_PARALLELISM} 並列。
     *
     * @param list<array{spec: string, mode: string}> $entries
     * @return list<array<string, mixed>>
     */
    private function fetchAllFocusChannels(YouTubeApiClient $yt, array $entries): array {
        /** @var list<array<string, mixed>> $slots */
        $slots = [];
        foreach ($entries as $entry) {
            $mode = $entry['mode'];
            $spec = $entry['spec'];
            $modeLabel = $mode === 'short' ? 'Shorts' : '通常動画';
            $base = [
                'input_spec' => $spec,
                'mode' => $mode,
                'mode_label' => $modeLabel,
                'channel_id' => null,
                'channel_title' => '',
                'error' => null,
                'videos' => [],
            ];
            $ctx = $yt->getUploadsPlaylistContext($spec);
            if ($ctx === null) {
                $base['error'] = 'チャンネルを解決できませんでした（ID・@ハンドル・URLを確認してください）。';
                $slots[] = [
                    'base' => $base,
                    'done' => true,
                    'playlist_id' => null,
                    'mode' => $mode,
                    'picked' => [],
                    'page_token' => null,
                    'pages' => 0,
                    'channel_title' => '',
                ];

                continue;
            }
            $base['channel_id'] = $ctx['channel_id'];
            $slots[] = [
                'base' => $base,
                'done' => false,
                'playlist_id' => $ctx['playlist_id'],
                'mode' => $mode,
                'picked' => [],
                'page_token' => null,
                'pages' => 0,
                'channel_title' => '',
            ];
        }

        while (true) {
            $pending = [];
            foreach ($slots as $i => $slot) {
                if (!empty($slot['done']) || $slot['playlist_id'] === null) {
                    continue;
                }
                if (count($slot['picked']) >= self::PER_CHANNEL_LIMIT) {
                    $slots[$i]['done'] = true;

                    continue;
                }
                if ($slot['pages'] >= self::MAX_PLAYLIST_PAGES) {
                    $slots[$i]['done'] = true;

                    continue;
                }
                $pending[] = $i;
            }
            if ($pending === []) {
                break;
            }
            $slice = array_slice($pending, 0, self::FETCH_PARALLELISM);
            $requests = [];
            foreach ($slice as $i) {
                $s = $slots[$i];
                $params = [
                    'part' => 'snippet',
                    'playlistId' => $s['playlist_id'],
                    'maxResults' => min(self::PLAYLIST_PAGE_SIZE, 50),
                ];
                if (!empty($s['page_token'])) {
                    $params['pageToken'] = $s['page_token'];
                }
                $requests[] = ['endpoint' => '/playlistItems', 'params' => $params];
            }
            $raws = $yt->apiRequestConcurrent($requests, self::FETCH_PARALLELISM);
            foreach ($slice as $k => $i) {
                $raw = $raws[$k] ?? null;
                $slotRef = &$slots[$i];
                $this->applyPlaylistBatchToSlot($slotRef, $raw, $yt);
            }
            unset($slotRef);
        }

        $channels = [];
        foreach ($slots as $slot) {
            $b = $slot['base'];
            if ($slot['playlist_id'] === null) {
                $channels[] = $b;

                continue;
            }
            $resolved = $b['channel_id'] ?? null;
            $b['channel_title'] = $slot['channel_title'] !== ''
                ? $slot['channel_title']
                : (is_string($resolved) && $resolved !== '' ? $resolved : $b['input_spec']);
            $b['videos'] = array_slice($slot['picked'], 0, self::PER_CHANNEL_LIMIT);
            if ($b['videos'] === [] && $b['error'] === null) {
                $b['error'] = $slot['mode'] === 'short'
                    ? '直近のアップロードに Shorts が見つかりませんでした。'
                    : '条件に合う動画が見つかりませんでした（Shorts のみの並びの可能性があります）。';
            }
            $channels[] = $b;
        }

        return $channels;
    }

    /**
     * @param array<string, mixed> $slot in/out（fetchAllFocusChannels のスロット1件）
     */
    private function applyPlaylistBatchToSlot(array &$slot, ?array $raw, YouTubeApiClient $yt): void {
        $mode = (string) ($slot['mode'] ?? 'video');
        $batch = $yt->formatPlaylistItemsResponse($raw);
        if ($batch === null) {
            if ($slot['picked'] === [] && (int) ($slot['pages'] ?? 0) === 0) {
                $slot['base']['error'] = 'YouTube API から一覧を取得できませんでした。';
            }
            $slot['done'] = true;

            return;
        }
        $videos = $batch['videos'] ?? [];
        if ($videos === []) {
            $slot['done'] = true;

            return;
        }
        foreach ($videos as $v) {
            if ($slot['channel_title'] === '' && !empty($v['channel_title'])) {
                $slot['channel_title'] = (string) $v['channel_title'];
            }
            $mt = $v['media_type'] ?? 'video';
            if ($mode === 'short') {
                if ($mt === 'short') {
                    $slot['picked'][] = $v;
                }
            } elseif ($mt !== 'short') {
                $slot['picked'][] = $v;
            }
            if (count($slot['picked']) >= self::PER_CHANNEL_LIMIT) {
                $slot['done'] = true;

                return;
            }
        }
        $pageToken = $batch['next_page_token'] ?? null;
        if ($pageToken === null || $pageToken === '') {
            $slot['done'] = true;
            $slot['page_token'] = null;

            return;
        }
        $slot['page_token'] = $pageToken;
        $slot['pages'] = (int) ($slot['pages'] ?? 0) + 1;
    }

    /**
     * @return list<array{spec: string, mode: string}>
     */
    private function parseEnvEntries(string $raw): array {
        $parts = preg_split('/\s*,\s*/', $raw) ?: [];
        $out = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $mode = 'video';
            $spec = $part;
            $pipePos = strrpos($part, '|');
            if ($pipePos !== false && $pipePos < strlen($part) - 1) {
                $right = strtolower(trim(substr($part, $pipePos + 1)));
                $left = trim(substr($part, 0, $pipePos));
                // ありがちなタイプミスも吸収（shrots / shrot）
                if ($right === 'shrots' || $right === 'shrot') {
                    $right = 'short';
                }
                if ($left !== '' && ($right === 'short' || $right === 'shorts' || $right === 'video')) {
                    $spec = $left;
                    $mode = ($right === 'short' || $right === 'shorts') ? 'short' : 'video';
                }
            }
            $out[] = ['spec' => $spec, 'mode' => $mode];
        }
        return $out;
    }

}
