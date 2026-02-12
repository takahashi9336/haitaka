<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\EventModel;
use App\Hinata\Model\MemberModel;
use Core\Auth;
use Core\Database;
use Core\MediaAssetModel;

/**
 * 日向坂イベント制御コントローラ
 * 物理パス: haitaka/private/apps/Hinata/Controller/EventController.php
 */
class EventController {

    public function index(): void {
        $auth = new Auth();
        if (!$auth->check()) { header('Location: /login.php'); exit; }

        $eventModel = new EventModel();
        
        // 開始は先月から、終了は無制限(2099年)に設定
        $start = date('Y-m-01', strtotime('-1 month'));
        $end = '2099-12-31'; 
        $events = $eventModel->getEventsForCalendar($start, $end);

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/event_index.php';
    }

    public function admin(): void {
        $auth = new Auth();
        if (!$auth->check()) { header('Location: /login.php'); exit; }
        if (($_SESSION['user']['role'] ?? '') !== 'admin') { die('権限がありません'); }

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

            $eventData = [
                'event_name'  => $input['event_name'],
                'event_date'  => $input['event_date'],
                'category'    => $input['category'],
                'event_place' => $input['event_place'] ?? '',
                'event_info'  => $input['event_info'] ?? '',
                'event_url'   => $input['event_url'] ?? '',
            ];

            if (!empty($input['id'])) {
                $eventId = (int)$input['id'];
                $eventModel->update($eventId, $eventData);
            } else {
                $eventModel->create($eventData);
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
                        // 任意：イベント用メタデータも保持しておく（後続機能拡張を見越して）
                        $metaId = $mediaModel->findOrCreateMetadata(
                            $assetId,
                            'Event',
                            $input['event_date'] ?? null
                        );

                        // イベントとメディアアセットの紐付け（movie_id は com_media_assets.id として扱う）
                        $stmt = $pdo->prepare("INSERT INTO hn_event_movies (event_id, movie_id) VALUES (?, ?)");
                        $stmt->execute([$eventId, $assetId]);
                    }
                }
            }

            $pdo->commit();
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
            (new EventModel())->delete((int)$input['id']);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}