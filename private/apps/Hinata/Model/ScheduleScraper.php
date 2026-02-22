<?php

namespace App\Hinata\Model;

/**
 * 日向坂46 公式サイト スケジュールスクレイパー
 * https://www.hinatazaka46.com/s/official/media/list
 */
class ScheduleScraper
{
    private const BASE_URL = 'https://www.hinatazaka46.com';
    private const LIST_PATH = '/s/official/media/list';
    private const REQUEST_DELAY_US = 500000;

    private array $errors = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 指定月のスケジュール一覧を取得
     * @param string $ym YYYYMM形式
     */
    public function scrapeMonth(string $ym): array
    {
        $url = self::BASE_URL . self::LIST_PATH . '?ima=0000&dy=' . $ym;
        $html = $this->fetch($url);
        if ($html === null) return [];
        return $this->parseSchedulePage($html, $ym);
    }

    private function parseSchedulePage(string $html, string $ym): array
    {
        $year  = substr($ym, 0, 4);
        $month = ltrim(substr($ym, 4, 2), '0');

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        $links = $xpath->query("//a[contains(@href, '/media/detail/')]");
        if ($links === false || $links->length === 0) {
            $this->errors[] = "No schedule items found for {$ym}";
            return [];
        }

        // 日付コンテキスト: リンクの祖先から日付を推定
        // 公式サイトは日付ヘッダの後にアイテムが並ぶ構造
        // テキストパースでフォールバック
        $items = [];
        $currentDay = null;

        // 全テキストからパースするアプローチ
        // HTML内で日付パターン (数字+曜日) を探し、それ以降のリンクにその日付を適用
        $body = $xpath->query("//div[contains(@class, 'p-schedule')]")->item(0)
             ?? $xpath->query("//div[@id='content']")->item(0)
             ?? $doc->getElementsByTagName('body')->item(0);

        if ($body) {
            $this->walkNodes($body, $xpath, $items, $currentDay, $year, $month);
        }

        // walkNodesで取れなかった場合はフォールバック
        if (empty($items)) {
            foreach ($links as $a) {
                $item = $this->parseScheduleLink($a, $year, $month, '01');
                if ($item !== null) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * DOM ツリーを走査して日付コンテキストを保持しながらスケジュールを抽出
     */
    private function walkNodes(\DOMNode $node, \DOMXPath $xpath, array &$items, ?string &$currentDay, string $year, string $month): void
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = trim($child->textContent);
                // 日付パターン: "1日", "2月", "15日" (日の後に曜日) or 単に数字
                if (preg_match('/^(\d{1,2})[日月火水木金土]/', $text, $dm)) {
                    $currentDay = str_pad($dm[1], 2, '0', STR_PAD_LEFT);
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $el = $child;
                $text = trim($el->textContent);

                // 日付ヘッダ要素を検出
                if (preg_match('/^(\d{1,2})[日月火水木金土]/', $text, $dm) && mb_strlen($text) < 10) {
                    $currentDay = str_pad($dm[1], 2, '0', STR_PAD_LEFT);
                }

                // リンク要素を検出
                if ($el->tagName === 'a' && $currentDay !== null) {
                    $href = $el->getAttribute('href');
                    if (str_contains($href, '/media/detail/')) {
                        $item = $this->parseScheduleLink($el, $year, $month, $currentDay);
                        if ($item !== null) {
                            $items[] = $item;
                        }
                        continue;
                    }
                }

                $this->walkNodes($el, $xpath, $items, $currentDay, $year, $month);
            }
        }
    }

    private function parseScheduleLink(\DOMElement $a, string $year, string $month, string $day): ?array
    {
        $href = $a->getAttribute('href');
        if (!preg_match('/\/media\/detail\/(\d+)/', $href, $m)) {
            return null;
        }
        $scheduleCode = $m[1];
        $text = trim($a->textContent);

        $category = '';
        $timeText = null;
        $title = $text;

        $cats = ['テレビ','ラジオ','雑誌','配信','イベント','WEB連載','放送','誕生日','グッズ','チケット','リリース','その他'];
        foreach ($cats as $c) {
            if (str_starts_with($text, $c)) {
                $category = $c;
                $remaining = trim(mb_substr($text, mb_strlen($c)));
                // 時間パターン: "24:40～" or "8:30～"
                if (preg_match('/^(\d{1,2}:\d{2}～?(?:\d{1,2}:\d{2})?\s*)/u', $remaining, $tm)) {
                    $timeText = trim($tm[1]);
                    $title = trim(substr($remaining, strlen($tm[0])));
                } else {
                    $title = $remaining;
                }
                break;
            }
        }

        $date = sprintf('%s-%02d-%s', $year, (int)$month, $day);
        $detailUrl = str_contains($href, 'http') ? $href : self::BASE_URL . $href;

        return [
            'schedule_code' => $scheduleCode,
            'schedule_date' => $date,
            'category'      => $category,
            'time_text'     => $timeText,
            'title'         => $title,
            'detail_url'    => $detailUrl,
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
