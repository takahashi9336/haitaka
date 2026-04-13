<?php
/**
 * Debug log endpoint (same-origin).
 * NOTE: instrument only, do not log secrets/PII.
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

$sessionId = (string)($_GET['sessionId'] ?? '');
$layer = (string)($_GET['layer'] ?? '');
$msg = (string)($_GET['msg'] ?? '');
$extra = [];
foreach (['legId', 'originLen', 'destinationLen', 'mode'] as $k) {
    if (isset($_GET[$k])) {
        $extra[$k] = $_GET[$k];
    }
}

// Only accept our session to avoid accidental noise.
if ($sessionId !== '572306') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ignored']);
    exit;
}

try {
    // keep debug logs out of repo root
    // repo root: www/live_trip/api -> (api -> live_trip -> www -> root) -> one more to repo root
    $debugLogPath = dirname(__DIR__, 4) . '/.cursor/debug-572306.log';
    $payload = [
        'sessionId' => '572306',
        'runId' => 'pre-fix',
        'hypothesisId' => 'H5',
        'location' => 'www/live_trip/api/debug_log.php',
        'message' => $msg !== '' ? $msg : 'clientEvent',
        'data' => [
            'layer' => $layer,
            'extra' => $extra,
        ],
        'timestamp' => (int) floor(microtime(true) * 1000),
    ];
    @file_put_contents($debugLogPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
} catch (\Throwable $e) {
    // noop
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok']);
exit;

