<?php
declare(strict_types=1);

use Core\Database;
use App\LiveTrip\Model\ExpenseModel;
use App\LiveTrip\Model\HotelStayModel;
use App\LiveTrip\Model\TransportLegModel;
use App\LiveTrip\Model\TripPlanEventModel;

/**
 * STEP1: 費用集計レイヤー（費用タブのKPI/チャート用の前処理）
 *
 * NOTE:
 * - lt_expenses には日付/時刻カラムが無いため、manual の日別計上日は「遠征開始日」に寄せる。
 * - 現状のUIは「費用タブ入力 + 移動(amount) + 宿泊(price)」を合算しているため、本集計も同じ合算ルールに合わせる。
 */
function build_expense_summary(int $trip_id): array
{
    $tripId = $trip_id > 0 ? $trip_id : 0;
    if ($tripId <= 0) {
        return [
            'total' => 0,
            'count' => 0,
            'days' => 0,
            'avg_per_day' => 0,
            'categories' => [],
            'daily' => [],
            'items' => [],
        ];
    }

    // 対象データ取得
    $expenses = (new ExpenseModel())->getByTripPlanId($tripId);
    $transportLegs = (new TransportLegModel())->getByTripPlanId($tripId);
    $hotelStays = (new HotelStayModel())->getByTripPlanId($tripId);

    // 遠征日の推定（イベント日 + 移動日 + 宿泊チェックイン/アウト の min/max）
    $userId = (int)($_SESSION['user']['id'] ?? 0);
    $eventDates = [];
    if ($userId > 0) {
        try {
            $events = (new TripPlanEventModel())->getByTripPlanId($tripId, $userId);
            $eventDates = array_values(array_filter(array_map(
                fn($e) => (string)($e['event_date'] ?? ''),
                $events
            )));
        } catch (\Throwable $e) { /* noop */ }
    }

    $candidates = [];
    foreach ($eventDates as $d) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) $candidates[] = $d;
    }
    foreach ($transportLegs as $tl) {
        $d = (string)($tl['departure_date'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) $candidates[] = $d;
    }
    foreach ($hotelStays as $h) {
        $in = (string)($h['check_in'] ?? '');
        $out = (string)($h['check_out'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $in)) $candidates[] = $in;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $out)) $candidates[] = $out;
    }
    sort($candidates);
    $startDate = $candidates[0] ?? '';
    $endDate = $candidates ? $candidates[count($candidates) - 1] : '';

    $days = 0;
    if ($startDate !== '' && $endDate !== '') {
        try {
            $ds = new DateTimeImmutable($startDate, new DateTimeZone('Asia/Tokyo'));
            $de = new DateTimeImmutable($endDate, new DateTimeZone('Asia/Tokyo'));
            if ($de >= $ds) $days = (int)$ds->diff($de)->days + 1;
        } catch (\Throwable $e) { $days = 0; }
    }

    // カテゴリ正規化
    $categoryDefs = [
        'ticket' => 'チケット',
        'hotel' => '宿泊',
        'transport' => '交通',
        'goods' => 'グッズ',
        'food' => '飲食',
        'other' => 'その他',
    ];
    $normalizeCategory = function (string $raw) use ($categoryDefs): string {
        $k = trim($raw);
        if ($k === '') return 'other';
        if (isset($categoryDefs[$k])) return $k;
        // 互換（日本語入力が混じる場合）
        $map = [
            '交通費' => 'transport',
            'ホテル代' => 'hotel',
            '宿泊' => 'hotel',
            'チケット' => 'ticket',
            '食費' => 'food',
            '飲食' => 'food',
            'グッズ' => 'goods',
            'グッズ・物販' => 'goods',
            '物販' => 'goods',
            'その他' => 'other',
        ];
        return $map[$k] ?? 'other';
    };

    $items = [];

    // manual（費用タブ入力）
    foreach ($expenses as $ex) {
        $amount = (int)($ex['amount'] ?? 0);
        if ($amount <= 0) continue;
        $catKey = $normalizeCategory((string)($ex['category'] ?? 'other'));
        $memo = trim((string)($ex['memo'] ?? ''));
        $title = $memo !== '' ? $memo : ($categoryDefs[$catKey] ?? '費用');
        $items[] = [
            'id' => (int)($ex['id'] ?? 0),
            'category' => $catKey,
            'title' => $title,
            'sub' => '',
            'amount' => $amount,
            'date' => $startDate, // lt_expenses に日付が無いので開始日に寄せる
            'time' => '',
            'source' => 'manual',
            'editable' => true,
            'edit_url' => null,
        ];
    }

    // transport（移動タブ）
    foreach ($transportLegs as $tl) {
        $amount = (int)($tl['amount'] ?? 0);
        if ($amount <= 0) continue;
        $title = trim((string)($tl['transport_type'] ?? '') . ' ' . (string)($tl['route_memo'] ?? ''));
        $dep = trim((string)($tl['departure'] ?? ''));
        $arr = trim((string)($tl['arrival'] ?? ''));
        $sub = trim(($dep !== '' || $arr !== '') ? ($dep . ($arr !== '' ? ' → ' . $arr : '')) : '');
        $date = (string)($tl['departure_date'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = $startDate;
        $time = trim((string)($tl['scheduled_time'] ?? ''));
        $items[] = [
            'id' => (int)($tl['id'] ?? 0),
            'category' => 'transport',
            'title' => $title !== '' ? $title : '移動',
            'sub' => $sub,
            'amount' => $amount,
            'date' => $date,
            'time' => $time,
            'source' => 'transport',
            'editable' => false,
            'edit_url' => sprintf(
                '/live_trip/show.php?id=%d#transport-%d',
                $tripId,
                (int)($tl['id'] ?? 0)
            ),
        ];
    }

    // hotel（宿泊タブ）
    foreach ($hotelStays as $h) {
        $amount = (int)($h['price'] ?? 0);
        if ($amount <= 0) continue;
        $date = (string)($h['check_in'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = $startDate;
        $title = trim((string)($h['hotel_name'] ?? ''));
        $sub = trim((string)($h['address'] ?? ''));
        $items[] = [
            'id' => (int)($h['id'] ?? 0),
            'category' => 'hotel',
            'title' => $title !== '' ? $title : '宿泊',
            'sub' => $sub,
            'amount' => $amount,
            'date' => $date,
            'time' => '',
            'source' => 'hotel',
            'editable' => false,
            'edit_url' => sprintf(
                '/live_trip/show.php?id=%d#hotel-%d',
                $tripId,
                (int)($h['id'] ?? 0)
            ),
        ];
    }

    // total / count
    $total = 0;
    foreach ($items as $it) $total += (int)($it['amount'] ?? 0);
    $count = count($items);

    // categories
    $catAgg = [];
    foreach (array_keys($categoryDefs) as $k) {
        $catAgg[$k] = ['key' => $k, 'label' => $categoryDefs[$k], 'amount' => 0, 'count' => 0, 'ratio' => 0.0];
    }
    foreach ($items as $it) {
        $k = (string)($it['category'] ?? 'other');
        if (!isset($catAgg[$k])) {
            $catAgg[$k] = ['key' => $k, 'label' => $categoryDefs[$k] ?? $k, 'amount' => 0, 'count' => 0, 'ratio' => 0.0];
        }
        $catAgg[$k]['amount'] += (int)($it['amount'] ?? 0);
        $catAgg[$k]['count'] += 1;
    }
    foreach ($catAgg as &$c) {
        $c['ratio'] = $total > 0 ? ((float)$c['amount'] / (float)$total) : 0.0;
    }
    unset($c);
    $categories = array_values($catAgg);

    // daily（start..end の全日を埋める）
    $dailyMap = [];
    if ($startDate !== '' && $endDate !== '') {
        try {
            $ds = new DateTimeImmutable($startDate, new DateTimeZone('Asia/Tokyo'));
            $de = new DateTimeImmutable($endDate, new DateTimeZone('Asia/Tokyo'));
            for ($d = $ds; $d <= $de; $d = $d->modify('+1 day')) {
                $key = $d->format('Y-m-d');
                $dailyMap[$key] = 0;
            }
        } catch (\Throwable $e) { /* noop */ }
    }
    foreach ($items as $it) {
        $d = (string)($it['date'] ?? '');
        if ($d === '') continue;
        if (!isset($dailyMap[$d])) $dailyMap[$d] = 0;
        $dailyMap[$d] += (int)($it['amount'] ?? 0);
    }
    ksort($dailyMap);
    $daily = [];
    foreach ($dailyMap as $d => $amt) $daily[] = ['date' => $d, 'amount' => (int)$amt];

    $avg = ($days > 0) ? (int)intval($total / $days) : 0;

    return [
        'total' => (int)$total,
        'count' => (int)$count,
        'days' => (int)$days,
        'avg_per_day' => (int)$avg,
        'categories' => $categories,
        'daily' => $daily,
        'items' => $items,
    ];
}

/**
 * lt_trip_plans が user_id を持たないため、メンバー紐付けは lt_trip_members 経由。
 * 「過去」の制約: 当該遠征の日別レンジ先頭日（＝開始日）より前に終わった遠征のみ。
 *
 * @return int[] trip_plan.id（終了が古い順、最大20件）
 */
function fetch_past_trip_ids_for_user(int $user_id, int $trip_id): array
{
    if ($user_id <= 0 || $trip_id <= 0) {
        return [];
    }

    $selfSummary = build_expense_summary($trip_id);
    $daily = $selfSummary['daily'] ?? [];
    if ($daily === []) {
        return [];
    }
    $currentStart = (string)($daily[0]['date'] ?? '');

    $pdo = Database::connect();
    $sql = 'SELECT tp.id FROM lt_trip_plans tp
            INNER JOIN lt_trip_members m ON m.trip_plan_id = tp.id AND m.user_id = :uid
            WHERE tp.id <> :tid';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uid' => $user_id, 'tid' => $trip_id]);

    $candidates = [];
    while ($row = $stmt->fetch()) {
        $tid = (int)($row['id'] ?? 0);
        if ($tid <= 0) {
            continue;
        }
        $s = build_expense_summary($tid);
        $pd = $s['daily'] ?? [];
        if ($pd === []) {
            continue;
        }
        $last = end($pd);
        $tripEnd = (string)($last['date'] ?? '');
        if ($tripEnd !== '' && $tripEnd < $currentStart) {
            $candidates[] = ['id' => $tid, 'end' => $tripEnd];
        }
    }

    usort($candidates, static function (array $a, array $b): int {
        return strcmp($b['end'], $a['end']);
    });

    $ids = [];
    foreach (array_slice($candidates, 0, 20) as $c) {
        $ids[] = (int)$c['id'];
    }
    return $ids;
}

/**
 * 同一ユーザーの過去遠征（=現在の遠征より前に終了した遠征）の費用統計を返す。
 *
 * @return array{
 *   sample_size:int,
 *   median_total:?int,
 *   median_avg_per_day:?int,
 *   median_count:?int,
 *   self_total:int,
 *   self_avg_per_day:int,
 *   self_count:int,
 *   diff_total_pct:?float,
 *   diff_avg_per_day_pct:?float,
 *   diff_count_pct:?float
 * }
 */
function build_expense_compare(int $trip_id, int $user_id): array
{
    $self = build_expense_summary($trip_id);
    $self_total = (int)($self['total'] ?? 0);
    $self_avg = (int)($self['avg_per_day'] ?? 0);
    $self_count = (int)($self['count'] ?? 0);

    $past_trip_ids = fetch_past_trip_ids_for_user($user_id, $trip_id);

    $totals = [];
    $avgs = [];
    $counts = [];
    foreach ($past_trip_ids as $tid) {
        $s = build_expense_summary($tid);
        if (!empty($s['total'])) {
            $totals[] = (int)$s['total'];
            $avgs[] = (int)($s['avg_per_day'] ?? 0);
            $counts[] = (int)($s['count'] ?? 0);
        }
    }

    $medianFn = static function (array $a): ?int {
        if ($a === []) {
            return null;
        }
        $a = array_values($a);
        sort($a, SORT_NUMERIC);
        $n = count($a);
        $mid = intdiv($n, 2);
        if ($n % 2 === 1) {
            return (int)$a[$mid];
        }
        return (int)round(($a[$mid - 1] + $a[$mid]) / 2);
    };

    $pctDiff = static function (int $self, ?int $base): ?float {
        if ($base === null || $base === 0) {
            return null;
        }
        return round((($self - $base) / $base) * 100, 1);
    };

    $median_total = $medianFn($totals);
    $median_avg = $medianFn($avgs);
    $median_count = $medianFn($counts);

    $nSamples = count($totals);

    if ($nSamples === 0) {
        return [
            'sample_size' => 0,
            'median_total' => null,
            'median_avg_per_day' => null,
            'median_count' => null,
            'self_total' => $self_total,
            'self_avg_per_day' => $self_avg,
            'self_count' => $self_count,
            'diff_total_pct' => null,
            'diff_avg_per_day_pct' => null,
            'diff_count_pct' => null,
        ];
    }

    return [
        'sample_size' => $nSamples,
        'median_total' => $median_total,
        'median_avg_per_day' => $median_avg,
        'median_count' => $median_count,
        'self_total' => $self_total,
        'self_avg_per_day' => $self_avg,
        'self_count' => $self_count,
        'diff_total_pct' => $pctDiff($self_total, $median_total),
        'diff_avg_per_day_pct' => $pctDiff($self_avg, $median_avg),
        'diff_count_pct' => $pctDiff($self_count, $median_count),
    ];
}

