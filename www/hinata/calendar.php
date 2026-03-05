<?php
/**
 * iCal カレンダー出力（イベント・応募締め切り）
 * イベント開催日と応募締め切りを iCal 形式で出力。
 * ログイン必須。Google Calendar の「URLで予定を追加」または「ファイルをインポート」で利用可能。
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use App\Hinata\Model\EventModel;
use App\Hinata\Model\EventApplicationModel;
use Core\Database;

$auth = new Auth();
if (!$auth->check()) {
    header('Location: /login.php?redirect=' . urlencode('/hinata/calendar.php'));
    exit;
}

$events = [];
$deadlines = [];

try {
    $eventModel = new EventModel();
    $start = date('Y-m-01');
    $end = date('Y-m-d', strtotime('+6 months'));
    $events = $eventModel->getEventsForCalendar($start, $end);
} catch (\Throwable $e) {}

try {
    $appModel = new EventApplicationModel();
    $pdo = Database::connect();
    $sql = "SELECT ea.*, e.event_name, e.event_date FROM hn_event_applications ea JOIN hn_events e ON e.id = ea.event_id
            WHERE ea.application_deadline >= NOW() AND ea.application_deadline <= DATE_ADD(NOW(), INTERVAL 6 MONTH)
            ORDER BY ea.application_deadline ASC";
    $deadlines = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

header('Content-Type: text/calendar; charset=UTF-8');
header('Content-Disposition: inline; filename="hinata-portal.ics"');

$lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//HinataPortal//MyPlatform//JA',
    'CALSCALE:GREGORIAN',
    'X-WR-CALNAME:日向坂ポータル',
];

foreach ($events as $e) {
    $date = $e['event_date'] ?? '';
    if (!$date) continue;
    $uid = 'event-' . $e['id'] . '@hinata.portal';
    $summary = $e['event_name'] ?? 'イベント';
    $desc = trim(($e['event_info'] ?? '') . "\n" . ($e['event_place'] ?? ''));
    $url = $e['event_url'] ?? '';
    $dtStart = date('Ymd', strtotime($date));
    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:' . $uid;
    $lines[] = 'DTSTART;VALUE=DATE:' . $dtStart;
    $lines[] = 'DTEND;VALUE=DATE:' . $dtStart;
    $lines[] = 'SUMMARY:' . escapeIcal($summary);
    if ($desc) $lines[] = 'DESCRIPTION:' . escapeIcal($desc);
    if ($url) $lines[] = 'URL:' . escapeIcal($url);
    $lines[] = 'END:VEVENT';
}

foreach ($deadlines as $d) {
    $dt = $d['application_deadline'] ?? '';
    if (!$dt) continue;
    $uid = 'deadline-' . $d['id'] . '@hinata.portal';
    $summary = ($d['event_name'] ?? '') . ($d['round_name'] ? ' ' . $d['round_name'] : '') . ' 応募締切';
    $url = $d['application_url'] ?? '';
    $dtStr = date('Ymd\THis', strtotime($dt));
    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:' . $uid;
    $lines[] = 'DTSTART:' . $dtStr;
    $lines[] = 'DTEND:' . $dtStr;
    $lines[] = 'SUMMARY:' . escapeIcal($summary);
    if ($url) $lines[] = 'URL:' . escapeIcal($url);
    $lines[] = 'END:VEVENT';
}

$lines[] = 'END:VCALENDAR';

function escapeIcal(string $s): string {
    $s = str_replace(["\r\n", "\r", "\n"], '\\n', $s);
    $s = preg_replace('/[,;\\\\]/', '\\\\$0', $s);
    return $s;
}

echo implode("\r\n", $lines);
