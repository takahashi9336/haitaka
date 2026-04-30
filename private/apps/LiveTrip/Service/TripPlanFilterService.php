<?php

namespace App\LiveTrip\Service;

class TripPlanFilterService {
    /**
     * @param array<int, array<string,mixed>> $trips
     * @return array<int, array<string,mixed>>
     */
    public function filterAndSort(array $trips, string $period, string $sort, ?string $today = null): array {
        $today = $today ?? date('Y-m-d');

        $trips = array_values(array_filter($trips, function (array $t) use ($period, $today): bool {
            $ed = (string)($t['event_date'] ?? '');
            if ($ed === '') {
                return $period === 'all';
            }
            $range = $this->parseEventDateRange($ed);
            $lastDate = $range['end'] ?? $range['start'] ?? $ed;
            $isUpcoming = $lastDate >= $today;
            return match ($period) {
                'upcoming' => $isUpcoming,
                'past' => !$isUpcoming,
                default => true,
            };
        }));

        usort($trips, function (array $a, array $b) use ($sort, $period, $today): int {
            $da = $this->parseEventDateRange((string)($a['event_date'] ?? ''))['start'] ?? (string)($a['event_date'] ?? '');
            $db = $this->parseEventDateRange((string)($b['event_date'] ?? ''))['start'] ?? (string)($b['event_date'] ?? '');

            // event_date 未設定は最後へ
            if ($da === '' && $db !== '') return 1;
            if ($db === '' && $da !== '') return -1;
            if ($da === '' && $db === '') return 0;

            // 「新しい順」= 今から近い順（periodにより意味を調整）
            if ($sort === 'date_desc') {
                $ta = strtotime($da);
                $tb = strtotime($db);
                $tt = strtotime($today);
                if ($ta !== false && $tb !== false && $tt !== false) {
                    if ($period === 'upcoming') {
                        // 近い未来が先（早い日付が先）
                        $cmp = $ta <=> $tb;
                        if ($cmp !== 0) return $cmp;
                    } elseif ($period === 'past') {
                        // 直近の過去が先（遅い日付が先）
                        $cmp = $tb <=> $ta;
                        if ($cmp !== 0) return $cmp;
                    } else {
                        // すべて: 今日からの距離が近い順
                        $distA = abs((int)round(($ta - $tt) / 86400));
                        $distB = abs((int)round(($tb - $tt) / 86400));
                        $cmp = $distA <=> $distB;
                        if ($cmp !== 0) return $cmp;
                    }
                }
                // フォールバック: 文字列比較（昇順）
                $cmp = strcmp($da, $db);
                return $cmp !== 0 ? $cmp : 0;
            }

            // 「古い順」: 開始日が古い順（昇順）
            $cmp = strcmp($da, $db);
            return $cmp !== 0 ? $cmp : 0;
        });

        return $trips;
    }

    /**
     * event_date の「YYYY-MM-DD」または「YYYY-MM-DD〜YYYY-MM-DD」をパース
     * @return array{start:?string,end:?string}
     */
    public function parseEventDateRange(string $eventDate): array {
        $eventDate = trim($eventDate);
        if ($eventDate === '') return ['start' => null, 'end' => null];

        if (strpos($eventDate, '〜') === false) {
            return ['start' => $eventDate, 'end' => null];
        }

        $pos = strpos($eventDate, '〜');
        $start = trim(substr($eventDate, 0, $pos));
        $end = trim(substr($eventDate, $pos + 3));
        return [
            'start' => $start !== '' ? $start : null,
            'end' => $end !== '' ? $end : null,
        ];
    }
}

