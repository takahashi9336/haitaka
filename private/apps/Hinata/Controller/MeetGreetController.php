<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\MeetGreetModel;
use App\Hinata\Model\MeetGreetReportModel;
use App\Hinata\Model\MeetGreetReportMessageModel;
use App\Hinata\Model\MeetGreetReportAvatarModel;
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

        $allSlotIds = [];
        foreach ($groupedSlots as $slots) {
            foreach ($slots as $s) {
                $allSlotIds[] = (int)$s['id'];
            }
        }
        $reportModel = new MeetGreetReportModel();
        $reportCounts = $reportModel->countBySlotIds($allSlotIds);
        $ticketUsedSums = $reportModel->sumTicketUsedBySlotIds($allSlotIds);

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

    // ========== チャット形式レポ ==========

    /**
     * レポページ表示
     */
    public function reportPage(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $slotId = (int)($_GET['slot_id'] ?? 0);
        $eventId = (int)($_GET['event_id'] ?? 0);

        $memberModel = new MemberModel();

        if (!$slotId) {
            $members = $memberModel->getAllMembersWithColors();
            $eventModel = new EventModel();
            $eventData = $eventId ? $eventModel->find($eventId) : null;
            $mgEvents = $eventModel->getAllMgEvents();
            require_once __DIR__ . '/../Views/meetgreet_report_new.php';
            return;
        }

        $members = $memberModel->getActiveMembersWithColors();

        $slotModel = new MeetGreetModel();
        $slot = $slotModel->find($slotId);
        if (!$slot) {
            header('Location: /hinata/meetgreet.php');
            exit;
        }

        $member = $slot['member_id'] ? $memberModel->getMemberDetail((int)$slot['member_id']) : null;

        $avatarModel = new MeetGreetReportAvatarModel();
        $memberImage = $member ? $avatarModel->resolveAvatar((int)$slot['member_id'], $member) : null;

        $reportModel = new MeetGreetReportModel();
        $reports = $reportModel->getReportsBySlotId($slotId);

        $messageModel = new MeetGreetReportMessageModel();
        $reportIds = array_column($reports, 'id');
        $messagesMap = $messageModel->getMessagesByReportIds(array_map('intval', $reportIds));

        require_once __DIR__ . '/../Views/meetgreet_report.php';
    }

    /**
     * レポ作成API
     */
    public function createReportApi(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $slotId = (int)($input['slot_id'] ?? 0);
            $ticketUsed = (int)($input['ticket_used'] ?? 1);
            $nickname = $input['my_nickname'] ?? null;

            if (!$slotId) {
                throw new \Exception('スロットIDが指定されていません');
            }
            if ($ticketUsed < 1) $ticketUsed = 1;

            $model = new MeetGreetReportModel();
            $id = $model->createReport($slotId, $ticketUsed, $nickname);

            echo json_encode([
                'status' => 'success',
                'id'     => $id,
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * レポ更新API（ticket_used, my_nickname）
     */
    public function updateReportApi(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            if (!$id) {
                throw new \Exception('レポIDが指定されていません');
            }

            $data = [];
            if (isset($input['ticket_used'])) {
                $data['ticket_used'] = max(1, (int)$input['ticket_used']);
            }
            if (array_key_exists('my_nickname', $input)) {
                $data['my_nickname'] = $input['my_nickname'];
            }
            $data['updated_at'] = date('Y-m-d H:i:s');

            $model = new MeetGreetReportModel();
            $model->update($id, $data);

            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * レポ削除API
     */
    public function deleteReportApi(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            if (!$id) {
                throw new \Exception('レポIDが指定されていません');
            }

            $model = new MeetGreetReportModel();
            $model->delete($id);

            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * アバター画像アップロードAPI
     */
    public function uploadAvatarApi(): void {
        header('Content-Type: application/json');
        try {
            $memberId = (int)($_POST['member_id'] ?? 0);
            if (!$memberId) {
                throw new \Exception('メンバーIDが指定されていません');
            }
            if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception('ファイルのアップロードに失敗しました');
            }

            $file = $_FILES['avatar'];
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, MeetGreetReportAvatarModel::ALLOWED_TYPES, true)) {
                throw new \Exception('JPEG/PNG/WebPのみアップロード可能です');
            }

            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            };

            $model = new MeetGreetReportAvatarModel();
            $relDir = $model->getUploadDir($memberId);
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? (dirname(__DIR__, 3) . '/www');
            $filename = 'avatar_' . time() . '.' . $ext;
            $destPath = $docRoot . '/' . $relDir . '/' . $filename;

            if (!MeetGreetReportAvatarModel::resizeImage($file['tmp_name'], $destPath, $mime)) {
                throw new \Exception('画像のリサイズに失敗しました');
            }

            $imagePath = $relDir . '/' . $filename;
            $model->saveAvatar($memberId, $imagePath);

            echo json_encode([
                'status'     => 'success',
                'image_path' => '/' . $imagePath,
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * スロット作成API（レポ新規登録用）
     */
    public function createSlotApi(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $memberId = (int)($input['member_id'] ?? 0);
            $eventDate = $input['event_date'] ?? '';
            $slotName = trim($input['slot_name'] ?? '1部');

            if (!$memberId) throw new \Exception('メンバーを選択してください');
            if (!$eventDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
                throw new \Exception('日付の形式が不正です');
            }

            $eventId = !empty($input['event_id']) ? (int)$input['event_id'] : null;
            $ticketCount = (int)($input['ticket_count'] ?? 0);

            $model = new MeetGreetModel();
            $model->bulkInsert($eventDate, [[
                'slot_name'       => $slotName,
                'start_time'      => null,
                'end_time'        => null,
                'member_id'       => $memberId,
                'member_name_raw' => null,
                'ticket_count'    => $ticketCount,
            ]], $eventId);

            $lastId = $model->lastInsertId();

            echo json_encode([
                'status'  => 'success',
                'slot_id' => $lastId,
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * イベントに紐づくスロット一覧取得API
     */
    public function getSlotsByEventApi(): void {
        header('Content-Type: application/json');
        try {
            $eventId = (int)($_GET['event_id'] ?? 0);
            if (!$eventId) {
                throw new \Exception('イベントIDが指定されていません');
            }

            $model = new MeetGreetModel();
            $slots = $model->getSlotsByEventId($eventId);

            $slotIds = array_map(fn($s) => (int)$s['id'], $slots);
            $reportCounts = [];
            if ($slotIds) {
                $reportModel = new MeetGreetReportModel();
                $reportCounts = $reportModel->countBySlotIds($slotIds);
            }

            $result = array_map(fn($s) => [
                'id'           => (int)$s['id'],
                'slot_name'    => $s['slot_name'],
                'member_id'    => $s['member_id'] ? (int)$s['member_id'] : null,
                'member_name'  => $s['member_name'] ?? null,
                'report_count' => $reportCounts[(int)$s['id']] ?? 0,
            ], $slots);

            echo json_encode(['status' => 'success', 'slots' => $result]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * メッセージ一括保存API
     */
    public function saveReportMessagesApi(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $reportId = (int)($input['report_id'] ?? 0);
            $messages = $input['messages'] ?? [];

            if (!$reportId) {
                throw new \Exception('レポIDが指定されていません');
            }

            $model = new MeetGreetReportMessageModel();
            $count = $model->bulkSave($reportId, $messages);

            echo json_encode([
                'status'  => 'success',
                'count'   => $count,
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
