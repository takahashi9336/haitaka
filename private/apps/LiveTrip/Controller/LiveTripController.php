<?php

namespace App\LiveTrip\Controller;

use App\LiveTrip\Model\TripPlanModel;
use App\LiveTrip\Model\TripPlanEventModel;
use App\LiveTrip\Model\TripMemberModel;
use App\LiveTrip\Model\LtEventModel;
use App\LiveTrip\Model\ExpenseModel;
use App\LiveTrip\Model\HotelStayModel;
use App\LiveTrip\Model\TransportLegModel;
use App\LiveTrip\Model\TimelineItemModel;
use App\LiveTrip\Model\ChecklistItemModel;
use App\LiveTrip\Model\MyListModel;
use App\LiveTrip\Model\MyListItemModel;
use App\Hinata\Model\EventModel as HinataEventModel;
use Core\Auth;
use Core\Logger;

/**
 * 遠征管理コントローラ
 * 試験運用: Admin のみアクセス可
 */
class LiveTripController {

    private function requireAccess(): void {
        $auth = new Auth();
        $auth->requireLogin();
        $auth->requireAdmin();
    }

    public function index(): void {
        $this->requireAccess();
        $userId = (int) $_SESSION['user']['id'];
        $tripPlanModel = new TripPlanModel();
        $trips = $tripPlanModel->getMyTrips($userId);
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/index.php';
    }

    public function show(): void {
        $this->requireAccess();
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: /live_trip/');
            exit;
        }
        $userId = (int) $_SESSION['user']['id'];
        $tripPlanModel = new TripPlanModel();
        $tripPlanEventModel = new TripPlanEventModel();
        $trip = $tripPlanModel->findForUser($id, $userId);
        if (!$trip) {
            header('Location: /live_trip/');
            exit;
        }
        $events = [];
        try {
            $events = $tripPlanEventModel->getByTripPlanId($id, $userId);
        } catch (\Throwable $e) { /* テーブル未作成時 */ }
        $trip = TripPlanModel::enrichTripWithEvents($trip, $events);
        $expenseModel = new ExpenseModel();
        $hotelModel = new HotelStayModel();
        $transportModel = new TransportLegModel();
        $expenses = $expenseModel->getByTripPlanId($id);
        $hotelStays = $hotelModel->getByTripPlanId($id);
        $transportLegs = $transportModel->getByTripPlanId($id);
        $timelineItems = [];
        $checklistItems = [];
        try {
            $timelineItems = (new TimelineItemModel())->getByTripPlanId($id);
            $checklistItems = (new ChecklistItemModel())->getByTripPlanId($id);
        } catch (\Throwable $e) { /* テーブル未作成時 */ }
        $mergedTimeline = $this->mergeTimelineWithTransport($timelineItems, $transportLegs, $events);
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/show.php';
    }

    public function shiori(): void {
        $this->requireAccess();
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) { header('Location: /live_trip/'); exit; }
        $userId = (int) $_SESSION['user']['id'];
        $tripPlanModel = new TripPlanModel();
        $tripPlanEventModel = new TripPlanEventModel();
        $trip = $tripPlanModel->findForUser($id, $userId);
        if (!$trip) { header('Location: /live_trip/'); exit; }
        $events = [];
        try {
            $events = $tripPlanEventModel->getByTripPlanId($id, $userId);
        } catch (\Throwable $e) { }
        $trip = TripPlanModel::enrichTripWithEvents($trip, $events);
        $hotelModel = new HotelStayModel();
        $transportModel = new TransportLegModel();
        $hotelStays = $hotelModel->getByTripPlanId($id);
        $transportLegs = $transportModel->getByTripPlanId($id);
        $timelineItems = [];
        $checklistItems = [];
        try {
            $timelineItems = (new TimelineItemModel())->getByTripPlanId($id);
            $checklistItems = (new ChecklistItemModel())->getByTripPlanId($id);
        } catch (\Throwable $e) { }
        $mergedTimeline = $this->mergeTimelineWithTransport($timelineItems, $transportLegs, $events);
        require_once __DIR__ . '/../Views/shiori.php';
    }

    public function createForm(): void {
        $this->requireAccess();
        $hinataEventModel = new HinataEventModel();
        $ltEventModel = new LtEventModel();
        $hinataEvents = $hinataEventModel->getAllUpcomingEvents();
        $ltEvents = $ltEventModel->getForSelect();
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/form.php';
    }

    public function store(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /live_trip/');
            exit;
        }
        $eventsInput = $_POST['events'] ?? [];
        if (!is_array($eventsInput)) {
            $eventsInput = [];
        }
        $validEvents = [];
        foreach ($eventsInput as $i => $ev) {
            $type = $ev['event_type'] ?? '';
            $hnId = !empty($ev['hn_event_id']) ? (int) $ev['hn_event_id'] : null;
            $ltId = !empty($ev['lt_event_id']) ? (int) $ev['lt_event_id'] : null;
            if ($type === 'hinata' && $hnId) {
                $validEvents[] = ['event_type' => 'hinata', 'hn_event_id' => $hnId, 'lt_event_id' => null, 'seat_info' => trim($ev['seat_info'] ?? ''), 'impression' => trim($ev['impression'] ?? '')];
            } elseif ($type === 'generic' && $ltId) {
                $validEvents[] = ['event_type' => 'generic', 'hn_event_id' => null, 'lt_event_id' => $ltId, 'seat_info' => trim($ev['seat_info'] ?? ''), 'impression' => trim($ev['impression'] ?? '')];
            }
        }
        if (empty($validEvents)) {
            $_SESSION['flash_error'] = 'イベントを1件以上選択してください';
            header('Location: /live_trip/create.php');
            exit;
        }
        $tripPlanModel = new TripPlanModel();
        $tripId = $tripPlanModel->createWithMember(['impression' => trim($_POST['impression'] ?? '')], (int) $_SESSION['user']['id']);
        $tpeModel = new TripPlanEventModel();
        $userId = (int) $_SESSION['user']['id'];
        foreach ($validEvents as $i => $ev) {
            $tpeModel->create([
                'trip_plan_id' => $tripId,
                'event_type' => $ev['event_type'],
                'hn_event_id' => $ev['hn_event_id'],
                'lt_event_id' => $ev['lt_event_id'],
                'sort_order' => $i,
                'seat_info' => $ev['event_type'] === 'generic' ? ($ev['seat_info'] ?: null) : null,
                'impression' => $ev['event_type'] === 'generic' ? ($ev['impression'] ?: null) : null,
            ]);
            if ($ev['event_type'] === 'hinata' && $ev['hn_event_id']) {
                $this->upsertHinataEventStatus($userId, $ev['hn_event_id'], $ev['seat_info'] ?: null, $ev['impression'] ?: null);
            }
        }
        Logger::info("live_trip created id={$tripId} by=" . ($_SESSION['user']['id_name'] ?? ''));
        header('Location: /live_trip/show.php?id=' . $tripId);
        exit;
    }

    public function editForm(): void {
        $this->requireAccess();
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: /live_trip/');
            exit;
        }
        $userId = (int) $_SESSION['user']['id'];
        $tripPlanModel = new TripPlanModel();
        $trip = $tripPlanModel->findForUser($id, $userId);
        if (!$trip) {
            header('Location: /live_trip/');
            exit;
        }
        $events = [];
        try {
            $events = (new TripPlanEventModel())->getByTripPlanId($id, $userId);
        } catch (\Throwable $e) { }
        $trip = TripPlanModel::enrichTripWithEvents($trip, $events);
        $hinataEventModel = new HinataEventModel();
        $ltEventModel = new LtEventModel();
        $hinataEvents = $hinataEventModel->getAllUpcomingEvents();
        $ltEvents = $ltEventModel->getForSelect();
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/form.php';
    }

    public function update(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /live_trip/');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        $tripMemberModel = new TripMemberModel();
        if (!$tripMemberModel->isMember($id, $userId)) {
            header('Location: /live_trip/');
            exit;
        }
        $tripPlanModel = new TripPlanModel();
        $tripPlanModel->update($id, ['impression' => trim($_POST['impression'] ?? '')]);
        $eventsInput = $_POST['events'] ?? [];
        if (is_array($eventsInput)) {
            $validEvents = [];
            foreach ($eventsInput as $ev) {
                $type = $ev['event_type'] ?? '';
                $hnId = !empty($ev['hn_event_id']) ? (int) $ev['hn_event_id'] : null;
                $ltId = !empty($ev['lt_event_id']) ? (int) $ev['lt_event_id'] : null;
                if ($type === 'hinata' && $hnId) {
                    $validEvents[] = ['event_type' => 'hinata', 'hn_event_id' => $hnId, 'seat_info' => trim($ev['seat_info'] ?? ''), 'impression' => trim($ev['impression'] ?? '')];
                } elseif ($type === 'generic' && $ltId) {
                    $validEvents[] = ['event_type' => 'generic', 'lt_event_id' => $ltId, 'seat_info' => trim($ev['seat_info'] ?? ''), 'impression' => trim($ev['impression'] ?? '')];
                }
            }
            $tpeModel = new TripPlanEventModel();
            $tpeModel->deleteByTripPlanId($id);
            foreach ($validEvents as $i => $ev) {
                $type = $ev['event_type'];
                if ($type === 'hinata') {
                    $tpeModel->create([
                        'trip_plan_id' => $id,
                        'event_type' => 'hinata',
                        'hn_event_id' => $ev['hn_event_id'],
                        'lt_event_id' => null,
                        'sort_order' => $i,
                        'seat_info' => null,
                        'impression' => null,
                    ]);
                    $this->upsertHinataEventStatus($userId, $ev['hn_event_id'], $ev['seat_info'] ?: null, $ev['impression'] ?: null);
                } else {
                    $tpeModel->create([
                        'trip_plan_id' => $id,
                        'event_type' => 'generic',
                        'hn_event_id' => null,
                        'lt_event_id' => $ev['lt_event_id'],
                        'sort_order' => $i,
                        'seat_info' => $ev['seat_info'] ?: null,
                        'impression' => $ev['impression'] ?: null,
                    ]);
                }
            }
        }
        Logger::info("live_trip updated id={$id} by=" . ($_SESSION['user']['id_name'] ?? ''));
        header('Location: /live_trip/show.php?id=' . $id);
        exit;
    }

    public function updateParticipation(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int) ($_POST['id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($id, $userId)) { header('Location: /live_trip/'); exit; }
        (new TripPlanModel())->update($id, ['impression' => trim($_POST['impression'] ?? '')]);
        $eventsInput = $_POST['events'] ?? [];
        if (is_array($eventsInput)) {
            $tpeModel = new TripPlanEventModel();
            foreach ($eventsInput as $row) {
                $tpeId = (int) ($row['tpe_id'] ?? 0);
                $eventType = $row['event_type'] ?? '';
                $hnEventId = (int) ($row['hn_event_id'] ?? 0);
                $seatInfo = trim($row['seat_info'] ?? '') ?: null;
                $impression = trim($row['impression'] ?? '') ?: null;
                if ($eventType === 'hinata' && $hnEventId) {
                    $this->upsertHinataEventStatus($userId, $hnEventId, $seatInfo, $impression);
                } elseif ($tpeId) {
                    $tpe = $tpeModel->find($tpeId);
                    if ($tpe && (int)($tpe['trip_plan_id'] ?? 0) === $id) {
                        $tpeModel->update($tpeId, ['seat_info' => $seatInfo, 'impression' => $impression]);
                    }
                }
            }
        }
        $this->redirectToShow($id, 'info');
    }

    public function delete(): void {
        $this->requireAccess();
        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        $tripMemberModel = new TripMemberModel();
        if (!$tripMemberModel->isMember($id, $userId)) {
            header('Location: /live_trip/');
            exit;
        }
        $tripPlanModel = new TripPlanModel();
        $tripPlanModel->delete($id);
        Logger::info("live_trip deleted id={$id} by=" . ($_SESSION['user']['id_name'] ?? ''));
        header('Location: /live_trip/');
        exit;
    }

    public function storeExpense(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /live_trip/'); exit; }
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new ExpenseModel();
        $model->create([
            'trip_plan_id' => $tripPlanId,
            'category' => $_POST['category'] ?? 'other',
            'amount' => (int) ($_POST['amount'] ?? 0),
            'memo' => trim($_POST['memo'] ?? ''),
        ]);
        $this->redirectToShow($tripPlanId, 'expense');
    }

    public function updateExpense(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new ExpenseModel();
        $row = $model->find($id);
        if (!$row || (int)$row['trip_plan_id'] !== $tripPlanId) { header('Location: /live_trip/'); exit; }
        $model->update($id, [
            'category' => $_POST['category'] ?? 'other',
            'amount' => (int) ($_POST['amount'] ?? 0),
            'memo' => trim($_POST['memo'] ?? ''),
        ]);
        $this->redirectToShow($tripPlanId, 'expense');
    }

    public function deleteExpense(): void {
        $this->requireAccess();
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new ExpenseModel();
        $model->delete($id);
        $this->redirectToShow($tripPlanId, 'expense');
    }

    public function storeHotel(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new HotelStayModel();
        $model->create([
            'trip_plan_id' => $tripPlanId,
            'hotel_name' => trim($_POST['hotel_name'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'distance_from_home' => trim($_POST['distance_from_home'] ?? ''),
            'time_from_home' => trim($_POST['time_from_home'] ?? ''),
            'distance_from_venue' => trim($_POST['distance_from_venue'] ?? ''),
            'time_from_venue' => trim($_POST['time_from_venue'] ?? ''),
            'check_in' => !empty($_POST['check_in']) ? $_POST['check_in'] : null,
            'check_out' => !empty($_POST['check_out']) ? $_POST['check_out'] : null,
            'reservation_no' => trim($_POST['reservation_no'] ?? ''),
            'price' => !empty($_POST['price']) ? (int) $_POST['price'] : null,
            'num_guests' => !empty($_POST['num_guests']) ? (int) $_POST['num_guests'] : null,
            'memo' => trim($_POST['memo'] ?? ''),
        ]);
        $this->redirectToShow($tripPlanId, 'hotel');
    }

    public function updateHotel(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new HotelStayModel();
        $row = $model->find($id);
        if (!$row || (int)$row['trip_plan_id'] !== $tripPlanId) { header('Location: /live_trip/'); exit; }
        $model->update($id, [
            'hotel_name' => trim($_POST['hotel_name'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'distance_from_home' => trim($_POST['distance_from_home'] ?? ''),
            'time_from_home' => trim($_POST['time_from_home'] ?? ''),
            'distance_from_venue' => trim($_POST['distance_from_venue'] ?? ''),
            'time_from_venue' => trim($_POST['time_from_venue'] ?? ''),
            'check_in' => !empty($_POST['check_in']) ? $_POST['check_in'] : null,
            'check_out' => !empty($_POST['check_out']) ? $_POST['check_out'] : null,
            'reservation_no' => trim($_POST['reservation_no'] ?? ''),
            'price' => !empty($_POST['price']) ? (int) $_POST['price'] : null,
            'num_guests' => !empty($_POST['num_guests']) ? (int) $_POST['num_guests'] : null,
            'memo' => trim($_POST['memo'] ?? ''),
        ]);
        $this->redirectToShow($tripPlanId, 'hotel');
    }

    public function deleteHotel(): void {
        $this->requireAccess();
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new HotelStayModel();
        $model->delete($id);
        $this->redirectToShow($tripPlanId, 'hotel');
    }

    public function storeTransport(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new TransportLegModel();
        $amount = (int) ($_POST['transport_amount'] ?? 0);
        $depDate = trim($_POST['departure_date'] ?? '') ?: null;
        $model->create([
            'trip_plan_id' => $tripPlanId,
            'direction' => $_POST['direction'] ?? 'outbound',
            'departure_date' => $depDate,
            'transport_type' => trim($_POST['transport_type'] ?? ''),
            'route_memo' => trim($_POST['route_memo'] ?? ''),
            'departure' => trim($_POST['departure'] ?? ''),
            'arrival' => trim($_POST['arrival'] ?? ''),
            'duration_min' => !empty($_POST['duration_min']) ? (int) $_POST['duration_min'] : null,
            'scheduled_time' => trim($_POST['scheduled_time'] ?? '') ?: null,
            'amount' => $amount > 0 ? $amount : null,
        ]);
        $this->redirectToShow($tripPlanId, 'transport');
    }

    public function updateTransport(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new TransportLegModel();
        $row = $model->find($id);
        if (!$row || (int)$row['trip_plan_id'] !== $tripPlanId) { header('Location: /live_trip/'); exit; }
        $depDate = trim($_POST['departure_date'] ?? '') ?: null;
        $model->update($id, [
            'direction' => $_POST['direction'] ?? 'outbound',
            'departure_date' => $depDate,
            'transport_type' => trim($_POST['transport_type'] ?? ''),
            'route_memo' => trim($_POST['route_memo'] ?? ''),
            'departure' => trim($_POST['departure'] ?? ''),
            'arrival' => trim($_POST['arrival'] ?? ''),
            'duration_min' => isset($_POST['duration_min']) && $_POST['duration_min'] !== '' ? (int) $_POST['duration_min'] : null,
            'scheduled_time' => trim($_POST['scheduled_time'] ?? '') ?: null,
            'amount' => isset($_POST['amount']) && $_POST['amount'] !== '' ? (int) $_POST['amount'] : null,
        ]);
        $this->redirectToShow($tripPlanId, $_POST['tab'] ?? 'transport');
    }

    public function deleteTransport(): void {
        $this->requireAccess();
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new TransportLegModel();
        $model->delete($id);
        $this->redirectToShow($tripPlanId, 'transport');
    }

    public function storeTimeline(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new TimelineItemModel();
        $schDate = trim($_POST['scheduled_date'] ?? '') ?: null;
        $model->create([
            'trip_plan_id' => $tripPlanId,
            'scheduled_date' => $schDate,
            'label' => trim($_POST['label'] ?? ''),
            'scheduled_time' => trim($_POST['scheduled_time'] ?? ''),
            'memo' => trim($_POST['memo'] ?? ''),
        ]);
        $this->redirectToShow($tripPlanId, 'timeline');
    }

    public function updateTimeline(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new TimelineItemModel();
        $row = $model->find($id);
        if (!$row || (int)$row['trip_plan_id'] !== $tripPlanId) { header('Location: /live_trip/'); exit; }
        $schDate = trim($_POST['scheduled_date'] ?? '') ?: null;
        $model->update($id, [
            'scheduled_date' => $schDate,
            'label' => trim($_POST['label'] ?? ''),
            'scheduled_time' => trim($_POST['scheduled_time'] ?? ''),
            'memo' => trim($_POST['memo'] ?? ''),
        ]);
        $this->redirectToShow($tripPlanId, 'timeline');
    }

    public function deleteTimeline(): void {
        $this->requireAccess();
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        (new TimelineItemModel())->delete($id);
        $this->redirectToShow($tripPlanId, 'timeline');
    }

    public function storeChecklist(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new ChecklistItemModel();
        $model->create([
            'trip_plan_id' => $tripPlanId,
            'item_name' => trim($_POST['item_name'] ?? ''),
            'checked' => 0,
        ]);
        $id = (int) $model->lastInsertId();
        $item = $model->find($id);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'item' => $item]);
        exit;
    }

    public function updateChecklist(): void {
        $this->requireAccess();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['status' => 'error']); exit; }
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { echo json_encode(['status' => 'error']); exit; }
        $model = new ChecklistItemModel();
        if (!$model->findByIdAndTripPlan($id, $tripPlanId)) { echo json_encode(['status' => 'error']); exit; }
        $model->updateItemName($id, $tripPlanId, trim($_POST['item_name'] ?? ''));
        $item = $model->findByIdAndTripPlan($id, $tripPlanId);
        echo json_encode(['status' => 'ok', 'item' => $item]);
        exit;
    }

    public function deleteChecklist(): void {
        $this->requireAccess();
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) {
            if ($this->isAjaxRequest()) { header('Content-Type: application/json'); echo json_encode(['status' => 'error']); exit; }
            header('Location: /live_trip/'); exit;
        }
        $model = new ChecklistItemModel();
        $ok = $model->deleteByIdAndTripPlan($id, $tripPlanId);
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => $ok ? 'ok' : 'error']);
            exit;
        }
        $this->redirectToShow($tripPlanId, 'checklist');
    }

    private function redirectToShow(int $tripPlanId, string $defaultTab = 'summary'): void {
        $tab = $_POST['tab'] ?? $defaultTab;
        $valid = ['summary', 'info', 'expense', 'hotel', 'transport', 'timeline', 'checklist'];
        if (!in_array($tab, $valid, true)) {
            $tab = $defaultTab;
        }
        header('Location: /live_trip/show.php?id=' . $tripPlanId . '#' . $tab);
        exit;
    }

    /**
     * @param array $events TripPlanEventModel::getByTripPlanId の戻り値
     */
    private function mergeTimelineWithTransport(array $timelineItems, array $transportLegs, array $events = []): array {
        $items = [];
        $eventDates = array_values(array_unique(array_filter(array_column($events, 'event_date'))));
        sort($eventDates);
        $firstEv = $eventDates[0] ?? '9999-12-31';
        $lastEv = !empty($eventDates) ? $eventDates[count($eventDates) - 1] : $firstEv;
        foreach ($timelineItems as $t) {
            $date = $t['scheduled_date'] ?? $firstEv;
            $time = trim($t['scheduled_time'] ?? '') ?: '99:99';
            $items[] = ['type' => 'timeline', 'date' => $date, 'time' => $time, 'sortKey' => $date . ' ' . $time, 'data' => $t];
        }
        foreach ($transportLegs as $t) {
            $st = trim($t['scheduled_time'] ?? '');
            if ($st === '') continue;
            $dd = $t['departure_date'] ?? null;
            if (!$dd && $firstEv !== '9999-12-31') {
                $dir = $t['direction'] ?? 'outbound';
                $refDate = $dir === 'outbound' ? $firstEv : $lastEv;
                $evDt = \DateTime::createFromFormat('Y-m-d', $refDate);
                if ($evDt) {
                    $evDt->modify($dir === 'outbound' ? '-1 day' : '+1 day');
                    $dd = $evDt->format('Y-m-d');
                } else {
                    $dd = $refDate;
                }
            }
            $dd = $dd ?: $firstEv;
            $items[] = ['type' => 'transport', 'date' => $dd, 'time' => $st, 'sortKey' => $dd . ' ' . $st, 'data' => $t];
        }
        usort($items, fn($a, $b) => strcmp($a['sortKey'], $b['sortKey']));
        return $items;
    }

    private function isAjaxRequest(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function toggleChecklist(): void {
        $this->requireAccess();
        header('Content-Type: application/json');
        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? $_GET['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        $model = new ChecklistItemModel();
        if ($model->toggleChecked($id, $tripPlanId, $userId)) {
            $item = $model->findByIdAndTripPlan($id, $tripPlanId);
            echo json_encode(['status' => 'ok', 'checked' => (int)($item['checked'] ?? 0)]);
        } else {
            http_response_code(403);
            echo json_encode(['status' => 'error']);
        }
        exit;
    }

    public function applyMyList(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $myListId = (int) ($_POST['my_list_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId) || !$myListId) {
            header('Location: /live_trip/show.php?id=' . $tripPlanId);
            exit;
        }
        $itemModel = new MyListItemModel();
        $itemModel->copyToChecklist($myListId, $tripPlanId, $userId);
        $this->redirectToShow($tripPlanId, 'checklist');
    }

    public function saveChecklistToMyList(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) {
            header('Location: /live_trip/');
            exit;
        }
        $action = $_POST['action'] ?? '';
        $checkModel = new ChecklistItemModel();
        $items = $checkModel->getByTripPlanId($tripPlanId);
        if (empty($items)) {
            $_SESSION['flash_error'] = 'チェックリストが空です。項目を追加してから登録してください。';
            $this->redirectToShow($tripPlanId, 'checklist');
            return;
        }
        $listModel = new MyListModel();
        $itemModel = new MyListItemModel();
        if ($action === 'new') {
            $listName = trim($_POST['list_name'] ?? '');
            if ($listName === '') {
                $_SESSION['flash_error'] = 'リスト名を入力してください。';
                $this->redirectToShow($tripPlanId, 'checklist');
                return;
            }
            $listModel->create(['list_name' => $listName]);
            $myListId = (int) $listModel->lastInsertId();
            if (!$myListId) {
                $_SESSION['flash_error'] = '登録に失敗しました。';
                $this->redirectToShow($tripPlanId, 'checklist');
                return;
            }
            foreach ($items as $i => $item) {
                $itemModel->create([
                    'my_list_id' => $myListId,
                    'item_name' => $item['item_name'],
                    'sort_order' => $i,
                ]);
            }
            $_SESSION['flash_success'] = 'マイリスト「' . $listName . '」を作成しました。';
        } elseif ($action === 'add') {
            $myListId = (int) ($_POST['my_list_id'] ?? 0);
            $list = $listModel->find($myListId);
            if (!$list || (int)$list['user_id'] !== $userId) {
                $_SESSION['flash_error'] = 'マイリストを選択してください。';
                $this->redirectToShow($tripPlanId, 'checklist');
                return;
            }
            $existing = $itemModel->getByMyListId($myListId);
            $existingNames = array_flip(array_column($existing, 'item_name'));
            $sortOrder = count($existing);
            foreach ($items as $item) {
                if (isset($existingNames[$item['item_name']])) continue;
                $itemModel->create([
                    'my_list_id' => $myListId,
                    'item_name' => $item['item_name'],
                    'sort_order' => $sortOrder++,
                ]);
            }
            $_SESSION['flash_success'] = 'マイリストに追加しました。';
        } else {
            header('Location: /live_trip/show.php?id=' . $tripPlanId . '#checklist');
            exit;
        }
        $this->redirectToShow($tripPlanId, 'checklist');
    }

    public function myListIndex(): void {
        $this->requireAccess();
        $model = new MyListModel();
        $lists = [];
        try {
            $lists = $model->getWithItems();
        } catch (\Throwable $e) { }
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/my_list_index.php';
    }

    public function myListStore(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $model = new MyListModel();
        $model->create(['list_name' => trim($_POST['list_name'] ?? '')]);
        header('Location: /live_trip/my_list.php');
        exit;
    }

    public function myListDelete(): void {
        $this->requireAccess();
        $id = (int) ($_POST['id'] ?? 0);
        $model = new MyListModel();
        $model->delete($id);
        header('Location: /live_trip/my_list.php');
        exit;
    }

    public function myListItemStore(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $myListId = (int) ($_POST['my_list_id'] ?? 0);
        $listModel = new MyListModel();
        $list = $listModel->find($myListId);
        if (!$list || (int)$list['user_id'] !== (int)$_SESSION['user']['id']) { header('Location: /live_trip/my_list.php'); exit; }
        (new MyListItemModel())->create([
            'my_list_id' => $myListId,
            'item_name' => trim($_POST['item_name'] ?? ''),
        ]);
        header('Location: /live_trip/my_list.php?edit=' . $myListId);
        exit;
    }

    public function myListItemDelete(): void {
        $this->requireAccess();
        $id = (int) ($_POST['id'] ?? 0);
        $myListId = (int) ($_POST['my_list_id'] ?? 0);
        $listModel = new MyListModel();
        $list = $listModel->find($myListId);
        if (!$list || (int)$list['user_id'] !== (int)$_SESSION['user']['id']) { header('Location: /live_trip/my_list.php'); exit; }
        (new MyListItemModel())->delete($id);
        header('Location: /live_trip/my_list.php?edit=' . $myListId);
        exit;
    }

    public function ltEventCreateForm(): void {
        $this->requireAccess();
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/lt_event_form.php';
    }

    public function ltEventStore(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /live_trip/');
            exit;
        }
        $model = new LtEventModel();
        $model->create([
            'event_name' => trim($_POST['event_name'] ?? ''),
            'event_date' => $_POST['event_date'] ?? '',
            'event_place' => trim($_POST['event_place'] ?? ''),
            'event_info' => trim($_POST['event_info'] ?? ''),
        ]);
        $redirect = $_POST['redirect'] ?? '/live_trip/create.php';
        header('Location: ' . $redirect);
        exit;
    }

    private function canAccessTrip(int $tripPlanId, int $userId): bool {
        return (new TripMemberModel())->isMember($tripPlanId, $userId);
    }

    private function upsertHinataEventStatus(int $userId, int $eventId, ?string $seatInfo, ?string $impression): void {
        $pdo = \Core\Database::connect();
        $sql = "INSERT INTO hn_user_events_status (user_id, event_id, status, seat_info, impression)
                VALUES (:uid, :eid, 1, :seat_info, :impression)
                ON DUPLICATE KEY UPDATE
                  seat_info = VALUES(seat_info),
                  impression = VALUES(impression)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'uid' => $userId,
            'eid' => $eventId,
            'seat_info' => $seatInfo,
            'impression' => $impression,
        ]);
    }
}
