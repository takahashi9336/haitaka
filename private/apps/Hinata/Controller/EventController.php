<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\EventModel;
use App\Hinata\Model\EventSeriesModel;
use App\Hinata\Model\MeetGreetModel;
use App\Hinata\Model\MeetGreetReportModel;
use App\Hinata\Model\MemberModel;
use App\Hinata\Model\SetlistModel;
use Core\Auth;
use Core\Database;
use Core\Logger;
use App\Hinata\Model\MediaAssetModel;
use App\Hinata\Service\EventRelatedLinkService;
use App\Hinata\Helper\MemberGroupHelper;

/**
 * 日向坂イベント制御コントローラ
 * 物理パス: haitaka/private/apps/Hinata/Controller/EventController.php
 */
class EventController {

    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function index(): void {
        $this->auth->requireLogin();

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
        // 日向坂ポータル内の管理者（admin / hinata_admin）のみ許可
        (new HinataAuth($this->auth))->requireHinataAdmin('/hinata/');

        $memberModel = new MemberModel();
        $eventModel = new EventModel();

        /** 出演者選択用：卒業生含む全文＋ hn_member_images を image_url に反映済み */
        $members = $memberModel->getAllWithColors();
        $y = (int)date('Y');
        $listFrom = ($y - 1) . '-01-01';
        $listTo = ($y + 1) . '-12-31';
        $allForRecent = $eventModel->getEventsForCalendar($listFrom, $listTo);
        $today = date('Y-m-d');
        $recentUpcoming = [];
        $recentPast = [];
        foreach ($allForRecent as $row) {
            $d = $row['event_date'] ?? '';
            if ($d === '' || $d < $today) {
                $recentPast[] = $row;
            } else {
                $recentUpcoming[] = $row;
            }
        }
        usort($recentUpcoming, static function ($a, $b) {
            return strcmp($a['event_date'] ?? '', $b['event_date'] ?? '');
        });
        usort($recentPast, static function ($a, $b) {
            return strcmp($b['event_date'] ?? '', $a['event_date'] ?? '');
        });
        $miniCalEvents = $eventModel->getEventsForCalendar(($y - 1) . '-01-01', ($y + 1) . '-12-31');

        $eventSeriesList = (new EventSeriesModel())->allByNameAsc();

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

            $relatedRaw = $input['related_links'] ?? [];
            if (is_string($relatedRaw)) {
                $relatedArr = json_decode($relatedRaw, true);
                $relatedArr = is_array($relatedArr) ? $relatedArr : [];
            } elseif (is_array($relatedRaw)) {
                $relatedArr = $relatedRaw;
            } else {
                $relatedArr = [];
            }

            $mediaModel = new MediaAssetModel();
            try {
                $prepared = EventRelatedLinkService::normalizePayload($relatedArr, $mediaModel);
            } catch (\InvalidArgumentException $e) {
                throw new \Exception($e->getMessage());
            }

            $seriesId = null;
            $seriesRaw = $input['series_id'] ?? null;
            if ($seriesRaw !== null && $seriesRaw !== '') {
                $sid = (int)$seriesRaw;
                if ($sid > 0) {
                    $sm = new EventSeriesModel();
                    if (!$sm->find($sid)) {
                        throw new \Exception('無効な系列です');
                    }
                    $seriesId = $sid;
                }
            }

            $eventData = [
                'event_name'         => $input['event_name'],
                'event_date'         => $input['event_date'],
                'category'           => $input['category'],
                'series_id'          => $seriesId,
                'mg_rounds'          => $mgRounds,
                'event_place'        => $input['event_place'] ?? '',
                'event_place_address'=> ($v = trim((string)($input['event_place_address'] ?? ''))) !== '' ? $v : null,
                'latitude'           => ($v = trim((string)($input['latitude'] ?? ''))) !== '' ? $v : null,
                'longitude'          => ($v = trim((string)($input['longitude'] ?? ''))) !== '' ? $v : null,
                'place_id'           => ($v = trim((string)($input['place_id'] ?? ''))) !== '' ? $v : null,
                'event_info'         => $input['event_info'] ?? '',
                'event_url'          => $prepared['event_url'],
                'event_hashtag'      => ($h = trim(preg_replace('/^#+/', '', trim($input['event_hashtag'] ?? '')))) !== '' ? $h : null,
                'collaboration_urls' => $prepared['collaboration_urls_json'],
                'related_links'      => $prepared['related_links_json'],
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

            $pdo->prepare('DELETE FROM hn_event_members WHERE event_id = ?')->execute([$eventId]);
            $rawMemberIds = isset($input['member_ids']) && is_array($input['member_ids']) ? $input['member_ids'] : [];
            $seen = [];
            $pokaId = MemberGroupHelper::POKA_MEMBER_ID;
            if (!empty($rawMemberIds)) {
                $stmt = $pdo->prepare('INSERT INTO hn_event_members (event_id, member_id) VALUES (?, ?)');
                foreach ($rawMemberIds as $mid) {
                    $mid = (int)$mid;
                    if ($mid <= 0 || $mid === $pokaId || isset($seen[$mid])) {
                        continue;
                    }
                    $seen[$mid] = true;
                    $stmt->execute([$eventId, $mid]);
                }
            }

            EventRelatedLinkService::syncYoutubeMovie(
                $pdo,
                $eventId,
                $prepared['first_youtube_normalized'],
                (string)($input['event_name'] ?? ''),
                $mediaModel
            );

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

    /** 系列マスタ追加API（日向坂管理者のみ） */
    public function saveEventSeriesJson(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->auth->check() || !(new HinataAuth($this->auth))->isHinataAdmin()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
            return;
        }
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $name = trim((string)($input['name'] ?? ''));
            $seriesModel = new EventSeriesModel();
            $id = $seriesModel->createByName($name);
            $row = $seriesModel->find($id);
            echo json_encode([
                'status' => 'success',
                'id' => $id,
                'name' => (string)($row['name'] ?? $name),
            ], JSON_UNESCAPED_UNICODE);
        } catch (\InvalidArgumentException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            Logger::info('saveEventSeriesJson error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => '系列の作成に失敗しました'], JSON_UNESCAPED_UNICODE);
        }
    }

    /** 系列マスタ削除API（日向坂管理者のみ）: 参照0件のみ削除 */
    public function deleteEventSeriesJson(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->auth->check() || !(new HinataAuth($this->auth))->isHinataAdmin()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
            return;
        }
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new \InvalidArgumentException('IDが不正です');
            }

            $seriesModel = new EventSeriesModel();
            $row = $seriesModel->find($id);
            if (!$row) {
                throw new \InvalidArgumentException('系列が見つかりません');
            }

            $pdo = Database::connect();
            $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM hn_events WHERE series_id = ?');
            $stmt->execute([$id]);
            $c = (int)($stmt->fetchColumn() ?: 0);
            if ($c > 0) {
                throw new \InvalidArgumentException('この系列はイベント ' . $c . ' 件から参照されています。先にイベント側の系列を解除してください。');
            }

            $ok = $pdo->prepare('DELETE FROM hn_event_series WHERE id = ?')->execute([$id]);
            if (!$ok) {
                throw new \RuntimeException('削除に失敗しました');
            }

            Logger::info('hn_event_series delete id=' . $id . ' by=' . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        } catch (\InvalidArgumentException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            Logger::info('deleteEventSeriesJson error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => '削除に失敗しました'], JSON_UNESCAPED_UNICODE);
        }
    }
}