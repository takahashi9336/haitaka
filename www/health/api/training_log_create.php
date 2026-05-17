<?php
/**
 * Health: トレーニング実施履歴 作成 API
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Health\Model\TrainingDuration;
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

    $menuItemId = (int) ($input['menu_item_id'] ?? 0);
    if ($menuItemId <= 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'メニューを選択してください'], JSON_UNESCAPED_UNICODE);
        exit;
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

    $repsOverride = null;
    if (array_key_exists('reps', $input)) {
        $repsOverride = (int) $input['reps'];
        if ($repsOverride < 1) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => '回数は1以上を指定してください'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $durationOverride = null;
    if (array_key_exists('duration_sec', $input)
        || array_key_exists('duration_min', $input)
        || array_key_exists('duration_sec_part', $input)) {
        $durationResult = TrainingDuration::parseFromInput($input, true, null);
        if (!$durationResult['ok']) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => $durationResult['message']], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $durationOverride = $durationResult['sec'];
    }

    $model = new TrainingLogModel();
    $ok = $model->createFromMenuItem($menuItemId, $performedAt, $repsOverride, $durationOverride);
    if (!$ok) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'メニューが見つからないか、記録に失敗しました'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['status' => 'success', 'id' => (int) $model->lastInsertId()], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System Error'], JSON_UNESCAPED_UNICODE);
}
