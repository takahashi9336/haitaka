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
        $items = [];

        // 公式サイト構造: div.p-schedule__list-group ごとに日付+アイテム一覧
        //   日付: div.c-schedule__date--list > <span>日数字</span><b>曜日</b>
        //   アイテム: ul > li > a[href*="/media/detail/"]
        $groups = $xpath->query("//div[contains(@class, 'p-schedule__list-group')]");
        if ($groups && $groups->length > 0) {
            foreach ($groups as $group) {
                $dateSpan = $xpath->query(".//div[contains(@class, 'c-schedule__date--list')]/span", $group)->item(0);
                $dayNum = $dateSpan ? trim($dateSpan->textContent) : null;
                if (!$dayNum || !ctype_digit($dayNum)) continue;
                $day = str_pad($dayNum, 2, '0', STR_PAD_LEFT);

                $links = $xpath->query(".//a[contains(@href, '/media/detail/')]", $group);
                foreach ($links as $a) {
                    $item = $this->parseScheduleLink($a, $year, $month, $day);
                    if ($item !== null) {
                        $items[] = $item;
                    }
                }
            }
        }

        // list-group構造が見つからない場合: DOMウォークでフォールバック
        if (empty($items)) {
            $currentDay = null;
            $body = $xpath->query("//div[contains(@class, 'l-maincontents--schedule')]")->item(0)
                 ?? $xpath->query("//div[@id='content']")->item(0)
                 ?? $doc->getElementsByTagName('body')->item(0);
            if ($body) {
                $this->walkNodes($body, $xpath, $items, $currentDay, $year, $month);
            }
        }

        if (empty($items)) {
            $this->errors[] = "No schedule items found for {$ym}";
        }

        return $items;
    }

    /**
     * フォールバック: DOM ツリーを走査して日付コンテキストを保持しながらスケジュールを抽出
     */
    private function walkNodes(\DOMNode $node, \DOMXPath $xpath, array &$items, ?string &$currentDay, string $year, string $month): void
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = trim($child->textContent);
                if (preg_match('/^(\d{1,2})[日月火水木金土]/', $text, $dm)) {
                    $currentDay = str_pad($dm[1], 2, '0', STR_PAD_LEFT);
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                /** @var \DOMElement $el */
                $el = $child;
                $text = trim($el->textContent);

                if (preg_match('/^(\d{1,2})[日月火水木金土]?$/', $text, $dm) && mb_strlen($text) < 10) {
                    $currentDay = str_pad($dm[1], 2, '0', STR_PAD_LEFT);
                }

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

        $category = '';
        $timeText = null;
        $title = '';

        // 公式サイト構造: a > div.p-schedule__head (カテゴリ+時間) + p.c-schedule__text (タイトル)
        $doc = $a->ownerDocument;
        $xpath = new \DOMXPath($doc);

        $catEl = $xpath->query(".//div[contains(@class, 'c-schedule__category')]", $a)->item(0);
        if ($catEl) {
            $category = trim($catEl->textContent);
        }

        $timeEl = $xpath->query(".//div[contains(@class, 'c-schedule__time')]", $a)->item(0);
        if ($timeEl) {
            $timeText = trim($timeEl->textContent) ?: null;
        }

        $textEl = $xpath->query(".//p[contains(@class, 'c-schedule__text')]", $a)->item(0);
        if ($textEl) {
            $title = trim($textEl->textContent);
        }

        // 構造化要素が取れなかった場合: テキスト全体からパース
        if (!$title) {
            $text = trim($a->textContent);
            $title = $text;
            $cats = ['テレビ','ラジオ','雑誌','配信','イベント','WEB連載','放送','誕生日','グッズ','チケット','リリース','その他'];
            foreach ($cats as $c) {
                if (str_starts_with($text, $c)) {
                    $category = $category ?: $c;
                    $remaining = trim(mb_substr($text, mb_strlen($c)));
                    if (preg_match('/^(\d{1,2}:\d{2}～?(?:\d{1,2}:\d{2})?\s*)/u', $remaining, $tm)) {
                        $timeText = $timeText ?: trim($tm[1]);
                        $title = trim(substr($remaining, strlen($tm[0])));
                    } else {
                        $title = $remaining;
                    }
                    break;
                }
            }
        }

        $date = sprintf('%s-%02d-%s', $year, (int)$month, $day);
        $detailUrl = str_contains($href, 'http') ? $href : self::BASE_URL . $href;

        // 同一 detail が複数日にまたがる場合（ラジオ等）でも日付ごとに別レコードにする
        $scheduleCodeWithDate = $scheduleCode . '_' . $date;

        return [
            'schedule_code' => $scheduleCodeWithDate,
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

    /**
     * 詳細ページからメンバーを取得（一覧のタイトルにメンバー名が無い場合の補完用）
     * @param string $detailUrl 詳細ページURL
     * @param array $nameMap 正規化名 => member_id のマップ
     * @return int[] member_id の配列
     */
    public function fetchDetailMemberIds(string $detailUrl, array $nameMap): array
    {
        $url = str_contains($detailUrl, 'http') ? $detailUrl : self::BASE_URL . $detailUrl;
        $html = $this->fetch($url);
        if ($html === null || $html === '') return [];

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        // メインコンテンツ内のartistリンクに限定（ヘッダーナビのメンバー一覧を除外）
        $links = $xpath->query("//*[contains(@class, 'l-maincontents') or contains(@class, 'p-media')]//a[contains(@href, '/artist/')]");
        if (!$links || $links->length === 0) {
            $links = $xpath->query("//a[contains(@href, '/artist/')]");
        }
        if (!$links || $links->length === 0) return [];

        $ids = [];
        foreach ($links as $a) {
            $name = trim($a->textContent ?? '');
            if ($name === '') continue;
            $normalized = str_replace([' ', '　'], '', $name);
            if (isset($nameMap[$normalized])) {
                $ids[] = $nameMap[$normalized];
            }
        }
        return array_unique($ids);
    }
}
