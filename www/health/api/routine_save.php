<?php
/**
 * Health: ルーティン構造一括保存 API
 * 編集画面の「保存」で、フェーズとアイテムを丸ごと差し替える
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Database;
use App\Health\Model\RoutineModel;
use App\Health\Model\RoutinePhaseModel;
use App\Health\Model\RoutineItemModel;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new \Exception('Invalid input');
    }

    $routineId = (int)($input['routine_id'] ?? 0);
    $phases    = $input['phases'] ?? [];

    if ($routineId <= 0 || !is_array($phases)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => '入力が不正です'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $routineModel = new RoutineModel();
    $routine = $routineModel->find($routineId);
    if (!$routine) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'ルーティンが見つかりません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (isset($input['name'])) {
        $name = trim((string)$input['name']);
        if ($name !== '') {
            $patch = ['name' => $name];
            if (isset($input['type']) && in_array($input['type'], ['平日', '休日'], true)) {
                $patch['type'] = $input['type'];
            }
            if (isset($input['theme']) && in_array($input['theme'], ['morning', 'night'], true)) {
                $patch['theme'] = $input['theme'];
            }
            $routineModel->update($routineId, $patch);
        }
    }

    $pdo = Database::connect();
    $userId = $_SESSION['user']['id'];

    $pdo->beginTransaction();
    try {
        $stmtDelItems = $pdo->prepare(
            "DELETE ri FROM hl_routine_items ri
             INNER JOIN hl_routine_phases rp ON ri.phase_id = rp.id
             WHERE rp.routine_id = :rid AND ri.user_id = :uid"
        );
        $stmtDelItems->execute(['rid' => $routineId, 'uid' => $userId]);

        $stmtDelPhases = $pdo->prepare(
            "DELETE FROM hl_routine_phases WHERE routine_id = :rid AND user_id = :uid"
        );
        $stmtDelPhases->execute(['rid' => $routineId, 'uid' => $userId]);

        $stmtPhase = $pdo->prepare(
            "INSERT INTO hl_routine_phases (user_id, routine_id, label, sort_order) VALUES (:uid, :rid, :label, :sort)"
        );
        $stmtItem = $pdo->prepare(
            "INSERT INTO hl_routine_items (user_id, phase_id, content, sort_order) VALUES (:uid, :pid, :content, :sort)"
        );

        foreach ($phases as $pi => $phase) {
            $label = trim((string)($phase['label'] ?? ''));
            if ($label === '') continue;

            $stmtPhase->execute([
                'uid'   => $userId,
                'rid'   => $routineId,
                'label' => $label,
                'sort'  => $pi,
            ]);
            $phaseId = (int)$pdo->lastInsertId();

            $items = $phase['items'] ?? [];
            if (!is_array($items)) continue;

            foreach ($items as $ii => $item) {
                $content = trim((string)($item['content'] ?? (is_string($item) ? $item : '')));
                if ($content === '') continue;

                $stmtItem->execute([
                    'uid'     => $userId,
                    'pid'     => $phaseId,
                    'content' => $content,
                    'sort'    => $ii,
                ]);
            }
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'System Error'], JSON_UNESCAPED_UNICODE);
}
