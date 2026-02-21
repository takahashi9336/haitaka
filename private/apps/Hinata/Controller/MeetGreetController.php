<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\MeetGreetModel;
use App\Hinata\Model\MemberModel;
use App\Hinata\Model\EventModel;
use Core\Auth;

class MeetGreetController {

    /**
     * 一覧表示
     */
    public function index(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $model = new MeetGreetModel();
        $memberModel = new MemberModel();
        $eventModel = new EventModel();

        $groupedSlots = $model->getGroupedByDate();
        $members = $memberModel->getActiveMembersWithColors();
        $mgEvents = $eventModel->getMgEventsForMatching();

        require_once __DIR__ . '/../Views/meetgreet.php';
    }

    /**
     * 予定一括登録API
     */
    public function import(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $eventDate = $input['event_date'] ?? null;
            $slots = $input['slots'] ?? [];

            if (!$eventDate || empty($slots)) {
                throw new \Exception('日付またはスロットデータが不足しています');
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
                throw new \Exception('日付の形式が不正です');
            }

            $eventId = !empty($input['event_id']) ? (int)$input['event_id'] : null;

            $model = new MeetGreetModel();
            $count = $model->bulkInsert($eventDate, $slots, $eventId);

            echo json_encode([
                'status'  => 'success',
                'message' => "{$count}件のミーグリ予定を登録しました",
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * レポ保存API
     */
    public function saveReport(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $report = $input['report'] ?? '';

            if (!$id) {
                throw new \Exception('スロットIDが指定されていません');
            }

            $model = new MeetGreetModel();
            $model->update((int)$id, [
                'report'     => $report,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 削除API（スロット単体 or 日付一括）
     */
    public function delete(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            $model = new MeetGreetModel();

            if (!empty($input['event_date'])) {
                $model->deleteByDate($input['event_date']);
                echo json_encode(['status' => 'success', 'message' => '日付のスロットを削除しました']);
            } elseif (!empty($input['id'])) {
                $model->delete((int)$input['id']);
                echo json_encode(['status' => 'success', 'message' => 'スロットを削除しました']);
            } else {
                throw new \Exception('削除対象が指定されていません');
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
