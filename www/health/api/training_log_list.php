<?php
/**
 * Health: トレーニング実施履歴 一覧 API
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Health\Model\TrainingLogModel;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (!is_array($input)) {
        $input = $_GET;
    }

    $from = isset($input['from']) ? trim((string) $input['from']) : null;
    $to = isset($input['to']) ? trim((string) $input['to']) : null;

    if ($from !== null && $from !== '') {
        $dt = \DateTime::createFromFormat('Y-m-d', $from);
        if (!$dt || $dt->format('Y-m-d') !== $from) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => '開始日が不正です'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if ($to !== null && $to !== '') {
        $dt = \DateTime::createFromFormat('Y-m-d', $to);
        if (!$dt || $dt->format('Y-m-d') !== $to) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => '終了日が不正です'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if ($from === null || $from === '') {
        $from = (new \DateTimeImmutable('today'))->modify('-3 months')->format('Y-m-d');
    }

    $model = new TrainingLogModel();
    $items = $model->getLogs($from, $to ?: null);
    echo json_encode(['status' => 'success', 'items' => $items], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System Error'], JSON_UNESCAPED_UNICODE);
}
