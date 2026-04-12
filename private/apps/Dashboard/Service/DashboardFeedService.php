<?php

namespace App\Dashboard\Service;

/**
 * ダッシュボード用 RSS 取得サービス
 * 好奇心ブースト・AI関連（Google News RSS）、パレオな男（Blogger RSS）を取得し、1時間キャッシュする。
 */
class DashboardFeedService {

    private const CACHE_TTL_SECONDS = 3600; // 1時間
    private const PALEO_MAX_ITEMS = 3;
    private const RECENT_DAYS_DEFAULT = 7;
    /** 好奇心ブーストで「直近に出した URL」を覚えておく最大件数（重複回避用） */
    private const CURIOSITY_SHOWN_URL_MAX = 24;

    /**
     * 好奇心ブースト用: 単一の「科学+話題」だと同種トレンド（健康・自動車・スポーツ等）に偏りやすいため、
     * テーマを分けたクエリから日付・ユーザー単位で決定的に1つ試す。
     *
     * @var list<string>
     */
    private const CURIOSITY_SEARCH_QUERIES = [
        '宇宙 天文学 最新',
        '心理学 認知科学 研究',
        '脳科学 神経 研究',
        'デザイン 美学',
        '建築 空間 デザイン',
        '食文化 料理 発見',
        '伝統工芸 ものづくり',
        '言語 語源 文化',
        '映画 音楽 カルチャー',
        'アイドル エンタメ 業界',
    ];

    /** 上記テーマだけだと7日以内が空になりやすいので、最後の手段として広めの検索にフォールバックする */
    private const CURIOSITY_FALLBACK_QUERY = '科学 最新';
    // AI関連記事用: 「生成AI」をキーワードにした Google News RSS
    private const URL_AI = 'https://news.google.com/rss/search?q=%E7%94%9F%E6%88%90AI&hl=ja&gl=JP&ceid=JP:ja';
    private const URL_PALEO = 'https://yuchrszk.blogspot.com/feeds/posts/default?alt=rss';

    private string $cacheDir;
    private string $logPath;

    public function __construct() {
        // private/apps/Dashboard/Service から 3階層上で private、4階層上でプロジェクトルート
        $this->cacheDir = dirname(__DIR__, 3) . '/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
        // デバッグログはプロジェクトルート直下に出力
        $this->logPath = dirname(__DIR__, 4) . '/debug-b51db5.log';
    }

    /**
     * 今日の好奇心ブースト用に、科学・話題系 RSS から1件返す。
     * 同一ユーザー・同一日（JST）は同じ記事（リロードしても変わらない）。直近表示URLは除外する。
     *
     * @return array{title: string, url: string, pubDate: string}|null
     */
    public function getCuriosityItem(int $userId): ?array {
        $dateKey = $this->getCuriosityDateKeyJst();
        $baseSeed = $dateKey . '|' . $userId . '|curiosity';
        $excluded = $this->getCuriosityExcludeUrlsPriorDays($userId, $dateKey);

        $indices = range(0, count(self::CURIOSITY_SEARCH_QUERIES) - 1);
        usort($indices, function (int $a, int $b) use ($baseSeed): int {
            $ca = crc32($baseSeed . '|theme|' . $a);
            $cb = crc32($baseSeed . '|theme|' . $b);
            return ($ca <=> $cb) ?: ($a <=> $b);
        });

        foreach ($indices as $qi) {
            $q = self::CURIOSITY_SEARCH_QUERIES[$qi];
            $pickSeed = $baseSeed . '|q' . $qi;
            $picked = $this->pickRecentFromCuriosityFeed('curiosity_q' . $qi, $q, $pickSeed, $excluded);
            if ($picked !== null) {
                $this->recordCuriosityShownUrl($userId, (string) $picked['url'], $dateKey);
                return $picked;
            }
        }

        $picked = $this->pickRecentFromCuriosityFeed(
            'curiosity_fallback',
            self::CURIOSITY_FALLBACK_QUERY,
            $baseSeed . '|fallback',
            $excluded
        );
        if ($picked !== null) {
            $this->recordCuriosityShownUrl($userId, (string) $picked['url'], $dateKey);
        }
        return $picked;
    }

    private function getCuriosityDateKeyJst(): string {
        $tz = new \DateTimeZone('Asia/Tokyo');
        return (new \DateTimeImmutable('now', $tz))->format('Y-m-d');
    }

    /**
     * 今日より前の日に表示した URL のみ返す（同日再読込で候補から消えないようにする）。
     *
     * @return list<string>
     */
    private function getCuriosityExcludeUrlsPriorDays(int $userId, string $todayKey): array {
        $entries = $this->readCuriosityShownEntries($userId);
        $out = [];
        foreach ($entries as $row) {
            if (!is_array($row)) {
                continue;
            }
            $d = (string) ($row['d'] ?? '');
            $u = (string) ($row['u'] ?? '');
            if ($u === '' || $d === '') {
                continue;
            }
            if ($d !== $todayKey) {
                $out[] = $u;
            }
        }
        return $out;
    }

    /**
     * @return list<array{d: string, u: string}>
     */
    private function readCuriosityShownEntries(int $userId): array {
        $file = $this->cacheDir . '/dashboard_curiosity_shown_' . $userId . '.json';
        if (!is_file($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $row) {
            if (is_array($row) && isset($row['d'], $row['u']) && is_string($row['d']) && is_string($row['u'])) {
                $out[] = ['d' => $row['d'], 'u' => $row['u']];
            } elseif (is_string($row) && $row !== '') {
                // 旧形式（URL のみ）: 日付不明のため除外対象に含めない
                continue;
            }
        }
        return $out;
    }

    private function recordCuriosityShownUrl(int $userId, string $url, string $todayKey): void {
        $file = $this->cacheDir . '/dashboard_curiosity_shown_' . $userId . '.json';
        $merged = [['d' => $todayKey, 'u' => $url]];
        foreach ($this->readCuriosityShownEntries($userId) as $row) {
            if (($row['u'] ?? '') === $url) {
                continue;
            }
            $merged[] = $row;
        }
        $merged = array_slice($merged, 0, self::CURIOSITY_SHOWN_URL_MAX);
        @file_put_contents($file, json_encode($merged, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 指定クエリの RSS から直近 N 日の記事を1件選ぶ。取れなければ null。
     *
     * @param list<string> $excludedUrls
     * @return array{title: string, url: string, pubDate: string}|null
     */
    private function pickRecentFromCuriosityFeed(
        string $cacheKey,
        string $searchQuery,
        string $pickSeed,
        array $excludedUrls = []
    ): ?array {
        $feedUrl = $this->buildGoogleNewsRssUrl($searchQuery);
        $items = $this->getCachedOrFetch($cacheKey, $feedUrl);
        if (empty($items)) {
            return null;
        }

        $recent = $this->filterItemsWithinDays($items, self::RECENT_DAYS_DEFAULT);
        if (empty($recent)) {
            $fresh = $this->getCachedOrFetch($cacheKey, $feedUrl, null, true);
            $recent = $this->filterItemsWithinDays($fresh ?? [], self::RECENT_DAYS_DEFAULT);
        }
        if (empty($recent)) {
            return null;
        }

        $pool = $this->filterOutExcludedUrls($recent, $excludedUrls);
        if (empty($pool)) {
            $pool = $recent;
        }

        $idx = $this->stableIndexFromSeed($pickSeed, count($pool));
        return $pool[$idx];
    }

    /**
     * @param array<int, array{title: string, url: string, pubDate: string}> $items
     * @param list<string> $excludedUrls
     * @return array<int, array{title: string, url: string, pubDate: string}>
     */
    private function filterOutExcludedUrls(array $items, array $excludedUrls): array {
        if ($excludedUrls === []) {
            return array_values($items);
        }
        $set = array_fill_keys($excludedUrls, true);
        $out = [];
        foreach ($items as $it) {
            $u = (string) ($it['url'] ?? '');
            if ($u !== '' && !isset($set[$u])) {
                $out[] = $it;
            }
        }
        return $out;
    }

    private function stableIndexFromSeed(string $seed, int $count): int {
        if ($count <= 0) {
            return 0;
        }
        $h = crc32($seed);
        $mod = $count;
        return ((int) ($h % $mod) + $mod) % $mod;
    }

    /**
     * AI関連 RSS から1件返す（先頭、またはランダム）。
     * @return array{title: string, url: string, pubDate: string}|null
     */
    public function getAiItem(): ?array {
        // 生成AIキーワード専用のキャッシュキーを使用（旧aiキャッシュと切り離す）
        $items = $this->getCachedOrFetch('ai_gen', self::URL_AI);
        if (empty($items)) {
            return null;
        }
        $recent = $this->filterItemsWithinDays($items, self::RECENT_DAYS_DEFAULT);
        if (empty($recent)) {
            $fresh = $this->getCachedOrFetch('ai_gen', self::URL_AI, null, true);
            $recent = $this->filterItemsWithinDays($fresh ?? [], self::RECENT_DAYS_DEFAULT);
        }
        if (empty($recent)) {
            return null;
        }
        $idx = array_rand($recent);
        return $recent[$idx];
    }

    /**
     * パレオな男ブログの直近 N 件を返す。
     * @return array<int, array{title: string, url: string, pubDate: string}>
     */
    public function getPaleoItems(): array {
        $items = $this->getCachedOrFetch('paleo', self::URL_PALEO, self::PALEO_MAX_ITEMS);
        return is_array($items) ? array_slice($items, 0, self::PALEO_MAX_ITEMS) : [];
    }

    /**
     * キャッシュがあればそれを返し、なければ RSS を取得してキャッシュして返す。
     * @param string $key キャッシュキー（curiosity / ai / paleo）
     * @param string $feedUrl RSS URL
     * @param int|null $maxItems 最大件数（paleo 用。null の場合は全件キャッシュ）
     * @param bool $forceRefresh true の場合キャッシュを無視して再取得
     * @return array<int, array{title: string, url: string, pubDate: string}>|null
     */
    private function getCachedOrFetch(string $key, string $feedUrl, ?int $maxItems = null, bool $forceRefresh = false): ?array {
        $cacheFile = $this->cacheDir . '/dashboard_feed_' . $key . '.json';
        // #region agent log
        $this->logDebug('H1', 'getCachedOrFetch_called', [
            'key' => $key,
            'feedUrl' => $feedUrl,
            'cacheFileExists' => is_file($cacheFile),
        ]);
        // #endregion agent log
        if (!$forceRefresh && is_file($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL_SECONDS) {
            $raw = @file_get_contents($cacheFile);
            if ($raw !== false) {
                $decoded = json_decode($raw, true);
                // 空配列キャッシュは「前回取得失敗」とみなし、再取得する
                if (is_array($decoded) && !empty($decoded)) {
                    // #region agent log
                    $this->logDebug('H1', 'cache_hit', [
                        'key' => $key,
                        'itemCount' => count($decoded),
                    ]);
                    // #endregion agent log
                    return $decoded;
                }
            }
        }

        $items = $this->fetchAndParseRss($feedUrl);
        if ($items === null || empty($items)) {
            // #region agent log
            $this->logDebug('H1', 'fetch_failed_or_empty', [
                'key' => $key,
            ]);
            // #endregion agent log
            return null;
        }
        if ($maxItems !== null && count($items) > $maxItems) {
            $items = array_slice($items, 0, $maxItems);
        }
        @file_put_contents($cacheFile, json_encode($items, JSON_UNESCAPED_UNICODE));
        return $items;
    }

    private function buildGoogleNewsRssUrl(string $q): string {
        // Google News の従来URLは語間が「+」。RFC3986（スペースを%20）だと取得に失敗することがあるためデフォルト方式を使う。
        return 'https://news.google.com/rss/search?' . http_build_query([
            'q' => $q,
            'hl' => 'ja',
            'gl' => 'JP',
            'ceid' => 'JP:ja',
        ]);
    }

    /**
     * pubDate が直近 N 日以内の item のみに絞る（解釈できない日付は除外）。
     * @param array<int, array{title: string, url: string, pubDate: string}> $items
     * @return array<int, array{title: string, url: string, pubDate: string}>
     */
    private function filterItemsWithinDays(array $items, int $days): array {
        $cutoff = time() - ($days * 86400);
        $out = [];
        foreach ($items as $it) {
            $pub = trim((string)($it['pubDate'] ?? ''));
            if ($pub === '') {
                continue;
            }
            $ts = strtotime($pub);
            if ($ts === false) {
                continue;
            }
            if ($ts >= $cutoff) {
                $out[] = $it;
            }
        }
        return $out;
    }

    /**
     * RSS URL を取得し、item を [title, url, pubDate] の配列に正規化する。
     * @return array<int, array{title: string, url: string, pubDate: string}>|null
     */
    private function fetchAndParseRss(string $url): ?array {
        $xml = $this->fetchWithUserAgent($url);
        if ($xml === null || $xml === '') {
            // #region agent log
            $this->logDebug('H2', 'fetch_xml_empty', [
                'url' => $url,
            ]);
            // #endregion agent log
            return null;
        }

        libxml_use_internal_errors(true);
        $sx = @simplexml_load_string($xml);
        libxml_clear_errors();
        if ($sx === false) {
            // #region agent log
            $this->logDebug('H2', 'simplexml_parse_failed', [
                'url' => $url,
            ]);
            // #endregion agent log
            return null;
        }

        $items = [];

        // RSS 2.0: <channel><item>
        if (isset($sx->channel->item)) {
            foreach ($sx->channel->item as $item) {
                $entry = $this->normalizeRssItem($item);
                if ($entry !== null) {
                    $items[] = $entry;
                }
            }
        }

        // Atom: <feed><entry> （デフォルト名前空間付きの場合も考慮）
        if (empty($items)) {
            $namespaces = $sx->getNamespaces(true);
            // デフォルト名前空間（Atom）がある場合
            if (isset($namespaces[''])) {
                $atomRoot = $sx->children($namespaces['']);
                if (isset($atomRoot->entry)) {
                    foreach ($atomRoot->entry as $entryNode) {
                        $entry = $this->normalizeAtomEntry($entryNode);
                        if ($entry !== null) {
                            $items[] = $entry;
                        }
                    }
                }
            } elseif (isset($sx->entry)) {
                // 名前空間なしの Atom 風構造
                foreach ($sx->entry as $entryNode) {
                    $entry = $this->normalizeAtomEntry($entryNode);
                    if ($entry !== null) {
                        $items[] = $entry;
                    }
                }
            }
        }

        // #region agent log
        $this->logDebug('H2', 'parsed_items', [
            'url' => $url,
            'itemCount' => count($items),
        ]);
        // #endregion agent log

        return $items ?: null;
    }

    /**
     * SimpleXML の item を共通形式に変換。
     * @param \SimpleXMLElement $item
     * @return array{title: string, url: string, pubDate: string}|null
     */
    private function normalizeRssItem(\SimpleXMLElement $item): ?array {
        $title = trim((string)($item->title ?? ''));
        $link = trim((string)($item->link ?? ''));
        if ($title === '' || $link === '') {
            return null;
        }
        $pubDate = trim((string)($item->pubDate ?? ''));
        return [
            'title'   => $title,
            'url'     => $link,
            'pubDate' => $pubDate,
        ];
    }

    /**
     * Atom の entry を共通形式に変換。
     * @param \SimpleXMLElement $entry
     * @return array{title: string, url: string, pubDate: string}|null
     */
    private function normalizeAtomEntry(\SimpleXMLElement $entry): ?array {
        $title = trim((string)($entry->title ?? ''));

        $link = '';
        if (isset($entry->link)) {
            // <link href="..."> または <link rel="alternate" href="...">
            foreach ($entry->link as $linkNode) {
                $href = (string)($linkNode['href'] ?? '');
                if ($href !== '') {
                    $link = trim($href);
                    break;
                }
            }
        }

        if ($title === '' || $link === '') {
            return null;
        }

        $pubDate = '';
        if (isset($entry->updated)) {
            $pubDate = trim((string)$entry->updated);
        } elseif (isset($entry->published)) {
            $pubDate = trim((string)$entry->published);
        }

        return [
            'title'   => $title,
            'url'     => $link,
            'pubDate' => $pubDate,
        ];
    }

    private function fetchWithUserAgent(string $url): ?string {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 15,
                'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; rv:91.0) Gecko/20100101 Firefox/91.0\r\nAccept: application/rss+xml, application/xml, text/xml\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        return $body !== false ? $body : null;
    }

    // #region agent log
    private function logDebug(string $hypothesisId, string $message, array $data = []): void {
        $payload = [
            'sessionId' => 'b51db5',
            'id' => 'log_' . uniqid('', true),
            'timestamp' => (int)(microtime(true) * 1000),
            'location' => 'DashboardFeedService.php',
            'message' => $message,
            'data' => $data,
            'runId' => 'ai_investigation',
            'hypothesisId' => $hypothesisId,
        ];
        $line = json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n";
        @file_put_contents($this->logPath, $line, FILE_APPEND);
    }
    // #endregion agent log
}
