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

        // TTL 切れ後: 各チャンネルは playlistItems 1 回（最大50件）で「先頭の該当1件」の video_id を取得し、
        // キャッシュ先頭と一致すれば 3 件の追加取得は行わない。一致しない・キャッシュなしは従来どおり 3 件取得。
        if (!$forceRefresh && is_file($cacheFile)) {
            $staleJson = @file_get_contents($cacheFile);
            if ($staleJson !== false) {
                $stale = json_decode($staleJson, true);
                if (is_array($stale) && isset($stale['channels']) && is_array($stale['channels'])) {
                    if ($this->canReuseStaleChannels($yt, $entries, $stale['channels'])) {
                        $stale['cached'] = true;
                        $stale['configured'] = true;
                        $stale['api_configured'] = true;
                        @touch($cacheFile);
                        return $stale;
                    }
                }
            }
        }

        $channels = [];
        foreach ($entries as $entry) {
            $channels[] = $this->fetchOneChannel($yt, $entry['spec'], $entry['mode']);
        }

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
     * 各エントリについて、アップロード一覧の先頭ページからモードに合う最初の1動画の ID を取得する（playlistItems 1 回）。
     *
     * @param list<array{spec: string, mode: string}> $entries
     * @param list<array<string, mixed>> $staleChannels
     */
    private function canReuseStaleChannels(YouTubeApiClient $yt, array $entries, array $staleChannels): bool {
        foreach ($entries as $entry) {
            $spec = $entry['spec'];
            $mode = $entry['mode'];
            $cached = $this->findCachedChannel($staleChannels, $spec, $mode);
            if ($cached === null) {
                return false;
            }
            $cachedVideos = $cached['videos'] ?? [];
            if (!is_array($cachedVideos) || $cachedVideos === []) {
                return false;
            }
            $cachedFirstId = (string) (($cachedVideos[0]['video_id'] ?? '') ?: '');
            if ($cachedFirstId === '') {
                return false;
            }

            $probeId = $this->probeNewestMatchingVideoId($yt, $spec, $mode);
            if ($probeId === null || $probeId === '') {
                return false;
            }
            if ($probeId !== $cachedFirstId) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<array<string, mixed>> $staleChannels
     */
    private function findCachedChannel(array $staleChannels, string $spec, string $mode): ?array {
        foreach ($staleChannels as $ch) {
            if (!is_array($ch)) {
                continue;
            }
            if (($ch['input_spec'] ?? '') === $spec && ($ch['mode'] ?? '') === $mode) {
                return $ch;
            }
        }

        return null;
    }

    /**
     * アップロードプレイリストの先頭ページ（最大 {@see PLAYLIST_PAGE_SIZE} 件）から、モードに合う最初の動画 ID を返す。
     * 先頭ページに該当がなければ null（フル取得へ）。
     */
    private function probeNewestMatchingVideoId(YouTubeApiClient $yt, string $spec, string $mode): ?string {
        $playlistId = $yt->getUploadsPlaylistId($spec);
        if ($playlistId === null || $playlistId === '') {
            return null;
        }

        $batch = $yt->getPlaylistItems($playlistId, self::PLAYLIST_PAGE_SIZE, null);
        if ($batch === null) {
            return null;
        }
        $videos = $batch['videos'] ?? [];
        if (!is_array($videos) || $videos === []) {
            return null;
        }

        foreach ($videos as $v) {
            if (!is_array($v)) {
                continue;
            }
            $mt = $v['media_type'] ?? 'video';
            if ($mode === 'short') {
                if ($mt === 'short') {
                    $id = (string) ($v['video_id'] ?? '');
                    return $id !== '' ? $id : null;
                }
            } elseif ($mt !== 'short') {
                $id = (string) ($v['video_id'] ?? '');
                return $id !== '' ? $id : null;
            }
        }

        return null;
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

    /**
     * @return array{
     *   input_spec: string,
     *   mode: string,
     *   mode_label: string,
     *   channel_id: ?string,
     *   channel_title: string,
     *   error: ?string,
     *   videos: list<array<string, mixed>>
     * }
     */
    private function fetchOneChannel(YouTubeApiClient $yt, string $spec, string $mode): array {
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

        $playlistId = $yt->getUploadsPlaylistId($spec);
        if ($playlistId === null || $playlistId === '') {
            $base['error'] = 'チャンネルを解決できませんでした（ID・@ハンドル・URLを確認してください）。';
            return $base;
        }

        $resolved = $yt->resolveChannelId($spec);
        $base['channel_id'] = $resolved;

        $picked = [];
        $channelTitle = '';
        $pageToken = null;
        $pages = 0;

        while (count($picked) < self::PER_CHANNEL_LIMIT && $pages < self::MAX_PLAYLIST_PAGES) {
            $batch = $yt->getPlaylistItems($playlistId, self::PLAYLIST_PAGE_SIZE, $pageToken);
            if ($batch === null) {
                if ($picked === [] && $pages === 0) {
                    $base['error'] = 'YouTube API から一覧を取得できませんでした。';
                }
                break;
            }
            $videos = $batch['videos'] ?? [];
            if ($videos === []) {
                break;
            }
            foreach ($videos as $v) {
                if ($channelTitle === '' && !empty($v['channel_title'])) {
                    $channelTitle = (string) $v['channel_title'];
                }
                $mt = $v['media_type'] ?? 'video';
                if ($mode === 'short') {
                    if ($mt === 'short') {
                        $picked[] = $v;
                    }
                } else {
                    if ($mt !== 'short') {
                        $picked[] = $v;
                    }
                }
                if (count($picked) >= self::PER_CHANNEL_LIMIT) {
                    break 2;
                }
            }
            $pageToken = $batch['next_page_token'] ?? null;
            if ($pageToken === null || $pageToken === '') {
                break;
            }
            $pages++;
        }

        $base['channel_title'] = $channelTitle !== '' ? $channelTitle : ($resolved ?? $spec);
        $base['videos'] = array_slice($picked, 0, self::PER_CHANNEL_LIMIT);

        if ($base['videos'] === [] && $base['error'] === null) {
            $base['error'] = $mode === 'short'
                ? '直近のアップロードに Shorts が見つかりませんでした。'
                : '条件に合う動画が見つかりませんでした（Shorts のみの並びの可能性があります）。';
        }

        return $base;
    }
}
