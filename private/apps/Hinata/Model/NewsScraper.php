<?php

namespace App\Hinata\Model;

/**
 * 日向坂46 公式サイト ニューススクレイパー
 * https://www.hinatazaka46.com/s/official/news/list
 */
class NewsScraper
{
    private const BASE_URL = 'https://www.hinatazaka46.com';
    private const LIST_PATH = '/s/official/news/list';
    private const REQUEST_DELAY_US = 500000;

    private array $errors = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 指定月のニュース一覧を取得
     * @param string $ym YYYYMM形式
     */
    public function scrapeMonth(string $ym): array
    {
        $url = self::BASE_URL . self::LIST_PATH . '?ima=0000&dy=' . $ym;
        $html = $this->fetch($url);
        if ($html === null) return [];
        return $this->parseNewsPage($html, $ym);
    }

    private function parseNewsPage(string $html, string $ym): array
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        // 公式サイトのニュースリストは <li> 内に <a> がある形式
        $links = $xpath->query("//ul[contains(@class, 'p-news__list')]//a");
        if ($links === false || $links->length === 0) {
            // フォールバック: p-news を含む任意の a タグ
            $links = $xpath->query("//a[contains(@href, '/news/detail/')]");
        }
        if ($links === false || $links->length === 0) {
            $this->errors[] = "No news items found for {$ym}";
            return [];
        }

        $items = [];
        foreach ($links as $a) {
            $item = $this->parseNewsLink($a);
            if ($item !== null) {
                $items[] = $item;
            }
        }
        return $items;
    }

    private function parseNewsLink(\DOMElement $a): ?array
    {
        $href = $a->getAttribute('href');
        if (!preg_match('/\/news\/detail\/([A-Za-z0-9]+)/', $href, $m)) {
            return null;
        }
        $articleCode = $m[1];
        $text = trim($a->textContent);

        // テキスト形式: "2026.02.01 メディア タイトル文..."
        $date = null;
        $category = '';
        $title = $text;

        if (preg_match('/^(\d{4}\.\d{1,2}\.\d{1,2})\s+/', $text, $dm)) {
            $date = str_replace('.', '-', $dm[1]);
            $date = preg_replace_callback('/-(\d)(?=-|$)/', fn($x) => '-0' . $x[1], $date);
            $remaining = trim(substr($text, strlen($dm[0])));
            // カテゴリ (先頭の日本語ワード) を抽出
            $cats = ['メディア','イベント','グッズ','チケット','リリース','ファンクラブ','ミート＆グリート','オーディション','その他'];
            foreach ($cats as $c) {
                if (str_starts_with($remaining, $c)) {
                    $category = $c;
                    $title = trim(mb_substr($remaining, mb_strlen($c)));
                    break;
                }
            }
            if ($category === '') {
                $title = $remaining;
            }
        }

        if ($date === null) return null;

        $detailUrl = self::BASE_URL . $href;
        if (!str_contains($href, 'http')) {
            $detailUrl = self::BASE_URL . $href;
        } else {
            $detailUrl = $href;
        }

        return [
            'article_code'  => $articleCode,
            'published_date' => $date,
            'category'       => $category,
            'title'          => $title,
            'detail_url'     => $detailUrl,
        ];
    }

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
