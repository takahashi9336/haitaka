<?php
/**
 * Health: HIITセッション実施履歴 作成 API
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
        throw new \Exception('Invalid input');
    }

    $performedAt = trim((string) ($input['performed_at'] ?? ''));
    if ($performedAt === '') {
        $performedAt = date('Y-m-d');
    }
    $dt = \DateTime::createFromFormat('Y-m-d', $performedAt);
    if (!$dt || $dt->format('Y-m-d') !== $performedAt) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => '実施日が不正です'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $model = new TrainingLogModel();
    $result = $model->createHiitSession($performedAt);
    if (!$result['ok']) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => '登録メニューがありません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'id' => (int) $model->lastInsertId(),
        'duration_sec' => $result['duration_sec'],
        'exercise_count' => $result['exercise_count'],
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System Error'], JSON_UNESCAPED_UNICODE);
}
