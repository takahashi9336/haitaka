<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\EventModel;
use App\Hinata\Model\MeetGreetModel;
use App\Hinata\Model\MeetGreetReportModel;
use App\Hinata\Model\MemberModel;
use App\Hinata\Model\SetlistModel;
use Core\Auth;
use Core\Database;
use Core\Logger;
use Core\MediaAssetModel;

/**
 * 日向坂イベント制御コントローラ
 * 物理パス: haitaka/private/apps/Hinata/Controller/EventController.php
 */
class EventController {

    public function index(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $eventModel = new EventModel();
        $mgModel = new MeetGreetModel();
        
        // 開始は先月から、終了は無制限(2099年)に設定
        $start = date('Y-m-01', strtotime('-1 month'));
        $end = '2099-12-31'; 
        $events = $eventModel->getEventsForCalendar($start, $end);

        // MG/RMGイベントにユーザーのスロット情報を紐付け
        $eventSlots = [];
        foreach ($events as $e) {
            if (in_array((int)$e['category'], [2, 3], true)) {
                $slots = $mgModel->getSlotsByEventId((int)$e['id']);
                if (empty($slots)) {
                    $slots = $mgModel->getSlotsByDate($e['event_date']);
                }
                if (!empty($slots)) {
                    $eventSlots[$e['id']] = $slots;
                }
            }
        }

        $allSlotIds = [];
        foreach ($eventSlots as $slots) {
            foreach ($slots as $s) {
                $allSlotIds[] = (int)$s['id'];
            }
        }
        $reportModel = new MeetGreetReportModel();
        $ticketUsedSums = $reportModel->sumTicketUsedBySlotIds($allSlotIds);

        $attendedEventIds = [];
        try {
            $setlistModel = new SetlistModel();
            $attendedEventIds = $setlistModel->getAttendedEventIds();
        } catch (\Exception $e) {}

        $nextEvent = $eventModel->getNextEvent();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/event_index.php';
    }

    public function admin(): void {
        $auth = new Auth();
        // 日向坂ポータル内の管理者（admin / hinata_admin）のみ許可
        $auth->requireHinataAdmin('/hinata/');

        $memberModel = new MemberModel();
        $eventModel = new EventModel();

        $members = $memberModel->getActiveMembersWithColors();
        $events = $eventModel->getEventsForCalendar(date('Y-01-01'), date('Y-12-31'));

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/event_admin.php';
    }

    public function save(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['event_name']) || empty($input['event_date'])) {
                throw new \Exception('必須項目が不足しています');
            }

            $eventModel = new EventModel();
            $pdo = Database::connect();
            $pdo->beginTransaction();

            $cat = (int)($input['category'] ?? 0);
            $mgRounds = ($cat === 2 || $cat === 3) && !empty($input['mg_rounds'])
                ? (int)$input['mg_rounds']
                : null;

            $eventData = [
                'event_name'  => $input['event_name'],
                'event_date'  => $input['event_date'],
                'category'    => $input['category'],
                'mg_rounds'   => $mgRounds,
                'event_place' => $input['event_place'] ?? '',
                'event_info'  => $input['event_info'] ?? '',
                'event_url'   => $input['event_url'] ?? '',
            ];

            if (!empty($input['id'])) {
                $eventId = (int)$input['id'];
                $eventModel->update($eventId, $eventData + [
                    'update_user' => $_SESSION['user']['id_name'] ?? '',
                ]);
            } else {
                $eventModel->create($eventData + [
                    'update_user' => $_SESSION['user']['id_name'] ?? '',
                ]);
                $eventId = (int)$pdo->lastInsertId();
            }

            $pdo->prepare("DELETE FROM hn_event_members WHERE event_id = ?")->execute([$eventId]);
            if (($input['cast_type'] ?? '') === 'individual' && !empty($input['member_ids'])) {
                $stmt = $pdo->prepare("INSERT INTO hn_event_members (event_id, member_id) VALUES (?, ?)");
                foreach ($input['member_ids'] as $mid) { $stmt->execute([$eventId, $mid]); }
            }

            if (!empty($input['youtube_url'])) {
                $mediaModel = new MediaAssetModel();
                $parsed = $mediaModel->parseUrl($input['youtube_url']);
                if ($parsed && $parsed['platform'] === 'youtube') {
                    $title = $input['event_name'] ?? '';
                    $assetId = $mediaModel->findOrCreateAsset(
                        $parsed['platform'],
                        $parsed['media_key'],
                        $parsed['sub_key'],
                        $title
                    );

                    if ($assetId) {
                        $mediaModel->findOrCreateMetadata($assetId, 'Event');

                        $pdo->prepare("DELETE FROM hn_event_movies WHERE event_id = ?")->execute([$eventId]);
                        $stmt = $pdo->prepare("INSERT INTO hn_event_movies (event_id, movie_id) VALUES (?, ?)");
                        $stmt->execute([$eventId, $assetId]);
                    }
                }
            }

            $pdo->commit();
            Logger::info("hn_events save id={$eventId} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function delete(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) throw new \Exception('ID missing');
            $id = (int)$input['id'];
            (new EventModel())->delete($id);
            Logger::info("hn_events delete id={$id} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}