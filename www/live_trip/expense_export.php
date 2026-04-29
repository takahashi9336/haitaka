<?php
declare(strict_types=1);

require_once __DIR__ . '/../../private/bootstrap.php';

use App\LiveTrip\Model\TripPlanModel;
use Core\Auth;

$auth = new Auth();
$auth->requireLogin();
$auth->requireAdmin();

require_once __DIR__ . '/../../private/apps/LiveTrip/Views/lib/expense_summary.php';

$trip_id = (int)($_GET['trip_id'] ?? 0);
$current_user_id = (int)($_SESSION['user']['id'] ?? 0);

if ($trip_id <= 0 || $current_user_id <= 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('bad request');
}

$trip = (new TripPlanModel())->findForUser($trip_id, $current_user_id);
if (!$trip) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('forbidden');
}

$summary = build_expense_summary($trip_id);

$cat_label = [
    'ticket' => 'チケット',
    'hotel' => '宿泊',
    'transport' => '交通',
    'goods' => 'グッズ',
    'food' => '飲食',
    'other' => 'その他',
];
$source_label = [
    'manual' => '手入力',
    'transport' => '移動タブ自動連携',
    'hotel' => '宿泊タブ自動連携',
];

$filename = sprintf('expense_trip%d_%s.csv', $trip_id, date('Ymd_His'));
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
if ($out === false) {
    http_response_code(500);
    exit;
}

fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['日付', 'カテゴリ', 'ラベル', 'メモ', '金額', '由来']);

foreach (($summary['items'] ?? []) as $it) {
    $catKey = (string)($it['category'] ?? 'other');
    $srcKey = (string)($it['source'] ?? '');
    fputcsv($out, [
        (string)($it['date'] ?? ''),
        $cat_label[$catKey] ?? $catKey,
        (string)($it['title'] ?? ''),
        (string)($it['sub'] ?? ''),
        (int)($it['amount'] ?? 0),
        $source_label[$srcKey] ?? $srcKey,
    ]);
}

fputcsv($out, []);
fputcsv($out, ['合計', '', '', '', (int)($summary['total'] ?? 0), '']);
fclose($out);
