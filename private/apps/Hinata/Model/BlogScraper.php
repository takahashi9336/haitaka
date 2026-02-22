<?php

namespace App\Hinata\Model;

/**
 * 日向坂46 公式ブログスクレイパー
 * 物理パス: haitaka/private/apps/Hinata/Model/BlogScraper.php
 *
 * 公式サイト (hinatazaka46.com) のメンバーブログをスクレイプし、
 * 構造化データとして返す。DOMDocument + DOMXPath を使用。
 */
class BlogScraper
{
    private const BASE_URL = 'https://www.hinatazaka46.com';
    private const LIST_PATH = '/s/official/diary/member/list';
    private const REQUEST_DELAY_US = 500000; // 0.5秒

    private array $errors = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 全メンバーの最新ブログ記事を取得
     */
    public function scrapeLatest(int $pages = 1): array
    {
        $articles = [];
        for ($p = 0; $p < $pages; $p++) {
            $url = self::BASE_URL . self::LIST_PATH . '?ima=0000';
            if ($p > 0) {
                $url .= '&page=' . $p . '&cd=member';
            }
            $html = $this->fetch($url);
            if ($html === null) break;

            $parsed = $this->parseListPage($html);
            if (empty($parsed)) break;
            $articles = array_merge($articles, $parsed);

            if ($p < $pages - 1) {
                usleep(self::REQUEST_DELAY_US);
            }
        }
        return $articles;
    }

    /**
     * 特定メンバーのブログ記事を取得
     */
    public function scrapeMember(int $ct, int $pages = 1): array
    {
        $articles = [];
        for ($p = 0; $p < $pages; $p++) {
            $url = self::BASE_URL . self::LIST_PATH . '?ima=0000&ct=' . $ct;
            if ($p > 0) {
                $url .= '&page=' . $p . '&cd=member';
            }
            $html = $this->fetch($url);
            if ($html === null) break;

            $parsed = $this->parseListPage($html);
            if (empty($parsed)) break;
            $articles = array_merge($articles, $parsed);

            if ($p < $pages - 1) {
                usleep(self::REQUEST_DELAY_US);
            }
        }
        return $articles;
    }

    /**
     * 一覧ページ HTML をパースして記事配列を返す
     */
    private function parseListPage(string $html): array
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->query("//div[contains(@class, 'p-blog-article')]");
        if ($nodes === false || $nodes->length === 0) {
            return [];
        }

        $articles = [];
        foreach ($nodes as $node) {
            $article = $this->parseArticle($xpath, $node);
            if ($article !== null) {
                $articles[] = $article;
            }
        }
        return $articles;
    }

    /**
     * 1 記事の DOM ノードをパースして連想配列を返す
     */
    private function parseArticle(\DOMXPath $xpath, \DOMElement $el): ?array
    {
        $titleNode = $xpath->query(".//div[contains(@class, 'c-blog-article__title')]", $el)->item(0);
        $dateNode  = $xpath->query(".//div[contains(@class, 'c-blog-article__date')]", $el)->item(0);
        $nameNode  = $xpath->query(".//div[contains(@class, 'c-blog-article__name')]", $el)->item(0);
        $bodyNode  = $xpath->query(".//div[contains(@class, 'c-blog-article__text')]", $el)->item(0);
        $linkNode  = $xpath->query(".//a[contains(@class, 'c-button-blog-detail')]", $el)->item(0);

        if (!$linkNode) return null;

        $detailHref = $linkNode->getAttribute('href');
        $articleId  = $this->extractArticleId($detailHref);
        if ($articleId === null) return null;

        $title      = $titleNode ? trim($titleNode->textContent) : '';
        $memberName = $nameNode ? trim($nameNode->textContent) : '';
        $dateStr    = $dateNode ? trim($dateNode->textContent) : '';
        $publishedAt = $this->parseDate($dateStr);

        $bodyHtml = '';
        $bodyText = '';
        $thumbnailUrl = null;
        if ($bodyNode) {
            $bodyHtml = $this->innerHTML($bodyNode);
            $bodyText = trim($bodyNode->textContent);

            $imgNode = $xpath->query(".//img", $bodyNode)->item(0);
            if ($imgNode) {
                $thumbnailUrl = $imgNode->getAttribute('src');
            }
        }

        $detailUrl = self::BASE_URL . $detailHref;

        return [
            'article_id'    => $articleId,
            'title'         => $title,
            'member_name'   => $memberName,
            'body_html'     => $bodyHtml,
            'body_text'     => mb_substr($bodyText, 0, 5000),
            'thumbnail_url' => $thumbnailUrl,
            'published_at'  => $publishedAt,
            'detail_url'    => $detailUrl,
        ];
    }

    /**
     * 詳細ページ href から article_id を抽出
     * 例: /s/official/diary/detail/68048?ima=0000&cd=member → 68048
     */
    private function extractArticleId(string $href): ?int
    {
        if (preg_match('/diary\/detail\/(\d+)/', $href, $m)) {
            return (int)$m[1];
        }
        return null;
    }

    /**
     * "2026.2.22 20:27" → "2026-02-22 20:27:00"
     */
    private function parseDate(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/(\d{4})\.(\d{1,2})\.(\d{1,2})\s+(\d{1,2}):(\d{2})/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d %02d:%02d:00', $m[1], $m[2], $m[3], $m[4], $m[5]);
        }
        return date('Y-m-d H:i:s');
    }

    /**
     * DOMElement の innerHTML を取得
     */
    private function innerHTML(\DOMElement $el): string
    {
        $inner = '';
        foreach ($el->childNodes as $child) {
            $inner .= $el->ownerDocument->saveHTML($child);
        }
        return trim($inner);
    }

    /**
     * 公式ブログページからメンバー ct 一覧を自動取得
     * @return array ['メンバー名' => ct値, ...]
     */
    public function discoverMemberCts(): array
    {
        $url = self::BASE_URL . self::LIST_PATH . '?ima=0000';
        $html = $this->fetch($url);
        if ($html === null) return [];

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        // メンバーフィルターのリンクから ct= パラメータを抽出
        $links = $xpath->query("//a[contains(@href, 'ct=')]");
        $ctMap = [];
        if ($links) {
            foreach ($links as $a) {
                $href = $a->getAttribute('href');
                $name = trim($a->textContent);
                if (preg_match('/[?&]ct=(\d+)/', $href, $m) && $name !== '') {
                    $normalized = str_replace([' ', '　'], '', $name);
                    $ctMap[$normalized] = (int)$m[1];
                }
            }
        }
        return $ctMap;
    }

    /**
     * URL からHTMLを取得
     */
    private function fetch(string $url): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => implode("\r\n", [
                    'User-Agent: Mozilla/5.0 (compatible; HaitakaPlatform/1.0)',
                    'Accept: text/html,application/xhtml+xml',
                    'Accept-Language: ja',
                ]),
                'timeout' => 15,
            ],
        ]);
        $html = @file_get_contents($url, false, $ctx);
        if ($html === false) {
            $this->errors[] = "Failed to fetch: {$url}";
            return null;
        }
        return $html;
    }
}
