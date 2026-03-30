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

        usort($trips, function (array $a, array $b) use ($sort): int {
            $da = $this->parseEventDateRange((string)($a['event_date'] ?? ''))['start'] ?? (string)($a['event_date'] ?? '');
            $db = $this->parseEventDateRange((string)($b['event_date'] ?? ''))['start'] ?? (string)($b['event_date'] ?? '');
            $cmp = strcmp($da, $db);
            return $sort === 'date_asc' ? $cmp : -$cmp;
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

