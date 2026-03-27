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

    private const URL_CURIOSITY = 'https://news.google.com/rss/search?q=科学+話題&hl=ja&gl=JP&ceid=JP:ja';
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
     * 今日の好奇心ブースト用に、科学・話題系 RSS からランダムで1件返す。
     * @return array{title: string, url: string, pubDate: string}|null
     */
    public function getCuriosityItem(): ?array {
        $items = $this->getCachedOrFetch('curiosity', self::URL_CURIOSITY);
        if (empty($items)) {
            return null;
        }

        $recent = $this->filterItemsWithinDays($items, self::RECENT_DAYS_DEFAULT);
        if (empty($recent)) {
            // キャッシュが生きているが中身が古い場合があるため、1回だけ強制再取得する
            $fresh = $this->getCachedOrFetch('curiosity', self::URL_CURIOSITY, null, true);
            $recent = $this->filterItemsWithinDays($fresh ?? [], self::RECENT_DAYS_DEFAULT);
        }
        if (empty($recent)) {
            return null;
        }

        $idx = array_rand($recent);
        return $recent[$idx];
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
