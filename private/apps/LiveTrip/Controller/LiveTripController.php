<?php

namespace App\LiveTrip\Controller;

use App\LiveTrip\Model\TripPlanModel;
use App\LiveTrip\Model\TripPlanEventModel;
use App\LiveTrip\Model\TripMemberModel;
use App\LiveTrip\Model\LtEventModel;
use App\LiveTrip\Model\ExpenseModel;
use App\LiveTrip\Model\HotelStayModel;
use App\LiveTrip\Model\DestinationModel;
use App\LiveTrip\Model\TransportLegModel;
use App\LiveTrip\Model\TimelineItemModel;
use App\LiveTrip\Model\ChecklistItemModel;
use App\LiveTrip\Model\MyListModel;
use App\LiveTrip\Model\MyListItemModel;
use App\LiveTrip\Service\MapsGeocodeService;
use App\LiveTrip\Service\MapsDistanceMatrixService;
use App\LiveTrip\Service\MapsDirectionsService;
use App\LiveTrip\Service\MapsPlacesAutocompleteService;
use App\LiveTrip\Service\MapsLinkResolveService;
use App\LiveTrip\Service\HinataEventBridge;
use App\LiveTrip\Service\TripPlanFilterService;
use Core\Auth;
use Core\Logger;

/**
 * 遠征管理コントローラ
 * 試験運用: Admin のみアクセス可
 */
class LiveTripController {

    private Auth $auth;
    private const DEFAULT_TIMELINE_DURATION_MIN = 30;

    public function __construct() {
        $this->auth = new Auth();
    }

    private function requireAccess(): void {
        $this->auth->requireLogin();
        $this->auth->requireAdmin();
    }

    public function index(): void {
        $this->requireAccess();
        $userId = (int) $_SESSION['user']['id'];
        $tripPlanModel = new TripPlanModel();
        $trips = $tripPlanModel->getMyTrips($userId);

        $tripIds = array_map(fn($t) => (int)$t['id'], $trips);
        $expenseTotals = (new ExpenseModel())->getTotalsByTripPlanIds($tripIds);
        $transportTotals = (new TransportLegModel())->getAmountTotalsByTripPlanIds($tripIds);
        $hotelTotals = (new HotelStayModel())->getPriceTotalsByTripPlanIds($tripIds);
        $checklistCounts = (new ChecklistItemModel())->getCountsByTripPlanIds($tripIds);

        foreach ($trips as &$t) {
            $tid = (int)$t['id'];
            $t['total_expense'] = ($expenseTotals[$tid] ?? 0) + ($transportTotals[$tid] ?? 0) + ($hotelTotals[$tid] ?? 0);
            $c = $checklistCounts[$tid] ?? ['total' => 0, 'checked' => 0];
            $t['checklist_total'] = $c['total'];
            $t['checklist_checked'] = $c['checked'];
        }
        unset($t);

        $period = $_GET['period'] ?? 'all';
        $sort = $_GET['sort'] ?? 'date_desc';
        $trips = (new TripPlanFilterService())->filterAndSort($trips, (string)$period, (string)$sort);

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
        $destinations = [];
        try {
            $destinations = (new DestinationModel())->getByTripPlanId($id);
        } catch (\Throwable $e) { /* テーブル未作成時 */ }
        $transportLegs = $transportModel->getByTripPlanId($id);
        $timelineItems = [];
        $checklistItems = [];
        try {
            $timelineItems = (new TimelineItemModel())->getByTripPlanId($id);
            $checklistItems = (new ChecklistItemModel())->getByTripPlanId($id);
        } catch (\Throwable $e) { /* テーブル未作成時 */ }
        $mergedTimeline = $this->mergeTimelineWithTransport($timelineItems, $transportLegs, $events);

        // ユーザー別「自宅」
        $homePlace = null;
        try {
            $homePlace = (new \App\LiveTrip\Model\UserPlaceModel())->getByUserAndKey($userId, 'home');
        } catch (\Throwable $e) { }

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/show.php';
    }

    /**
     * ユーザー別「自宅」保存
     */
    public function storeHomePlace(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /live_trip/'); exit; }

        \Core\Database::connect();
        $userId = (int) $_SESSION['user']['id'];
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);

        $label = trim((string)($_POST['label'] ?? '')) ?: '自宅';
        $address = trim((string)($_POST['address'] ?? '')) ?: null;
        $placeId = trim((string)($_POST['place_id'] ?? '')) ?: null;
        $lat = trim((string)($_POST['latitude'] ?? '')) ?: null;
        $lng = trim((string)($_POST['longitude'] ?? '')) ?: null;

        // place_id はあるが緯度経度が無い場合は補完
        if ($placeId && (!$lat || !$lng)) {
            try {
                $geo = (new \App\LiveTrip\Service\MapsGeocodeService())->geocodeByPlaceId($placeId);
                if ($geo) {
                    $lat = $geo['latitude'] ?? $lat;
                    $lng = $geo['longitude'] ?? $lng;
                    $address = $geo['formatted_address'] ?? $address;
                }
            } catch (\Throwable $e) { }
        }

        try {
            (new \App\LiveTrip\Model\UserPlaceModel())->upsertHome($userId, [
                'label' => $label,
                'address' => $address,
                'place_id' => $placeId,
                'latitude' => $lat,
                'longitude' => $lng,
            ]);
            $_SESSION['flash_success'] = '自宅を登録しました。';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = '自宅の登録に失敗しました。';
        }

        if ($tripPlanId > 0) {
            header('Location: /live_trip/show.php?id=' . $tripPlanId . '#transport');
        } else {
            header('Location: /live_trip/');
        }
        exit;
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
        $destinations = [];
        try {
            $destinations = (new DestinationModel())->getByTripPlanId($id);
        } catch (\Throwable $e) { }
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
        $hinataBridge = new HinataEventBridge();
        $ltEventModel = new LtEventModel();
        $hinataEvents = $hinataBridge->getAllUpcomingEvents();
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
        $title = trim((string)($_POST['title'] ?? ''));
        if ($title === '') {
            $_SESSION['flash_error'] = '遠征タイトルを入力してください';
            header('Location: /live_trip/create.php');
            exit;
        }
        $tripPlanModel = new TripPlanModel();
        $tripId = $tripPlanModel->createWithMember([
            'title' => $title,
        ], (int) $_SESSION['user']['id']);
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
                (new HinataEventBridge())->upsertUserEventStatus($userId, $ev['hn_event_id'], $ev['seat_info'] ?: null, $ev['impression'] ?: null);
            }
        }
        Logger::info("live_trip created id={$tripId} by=" . ($_SESSION['user']['id_name'] ?? ''));
        $_SESSION['flash_success'] = '遠征を登録しました。宿泊・移動・チェックリストを追加して計画を充実させましょう。';
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
        $hinataBridge = new HinataEventBridge();
        $ltEventModel = new LtEventModel();
        $hinataEvents = $hinataBridge->getAllUpcomingEvents();
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
        $title = trim((string)($_POST['title'] ?? ''));
        if ($title === '') {
            $_SESSION['flash_error'] = '遠征タイトルを入力してください';
            header('Location: /live_trip/edit.php?id=' . $id);
            exit;
        }
        $tripPlanModel = new TripPlanModel();
        $tripPlanModel->update($id, [
            'title' => $title,
        ]);
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
                    (new HinataEventBridge())->upsertUserEventStatus($userId, $ev['hn_event_id'], $ev['seat_info'] ?: null, $ev['impression'] ?: null);
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
                    (new HinataEventBridge())->upsertUserEventStatus($userId, $hnEventId, $seatInfo, $impression);
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
        \Core\Database::connect(); // .env を $_ENV に読み込む（Geocoding 前に必須）

        $geoLat = trim($_POST['latitude'] ?? '') ?: null;
        $geoLng = trim($_POST['longitude'] ?? '') ?: null;
        $geoPlaceId = trim($_POST['place_id'] ?? '') ?: null;
        if ($geoLat === null || $geoLng === null) {
            $address = trim($_POST['address'] ?? '');
            $hotelName = trim($_POST['hotel_name'] ?? '');
            $geocodeTarget = $address ?: $hotelName;
            if ($geocodeTarget !== '') {
                $geo = (new MapsGeocodeService())->geocode($geocodeTarget);
                if ($geo) {
                    $geoLat = $geo['latitude'];
                    $geoLng = $geo['longitude'];
                    $geoPlaceId = $geo['place_id'];
                }
            }
        }

        $distFromVenue = trim($_POST['distance_from_venue'] ?? '');
        $timeFromVenue = trim($_POST['time_from_venue'] ?? '');
        if ($geoLat !== null && $geoLng !== null) {
            $venue = $this->getVenueCoordinatesForTrip($tripPlanId, $userId);
            if ($venue !== null) {
                $dm = (new MapsDistanceMatrixService())->getDistanceAndDuration(
                    $venue['lat'], $venue['lng'],
                    $geoLat, $geoLng
                );
                if ($dm !== null) {
                    $distFromVenue = $dm['distance'];
                    $timeFromVenue = $dm['duration'];
                }
            }
        }

        $model = new HotelStayModel();
        $model->create([
            'trip_plan_id' => $tripPlanId,
            'hotel_name' => trim($_POST['hotel_name'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'distance_from_home' => trim($_POST['distance_from_home'] ?? ''),
            'time_from_home' => trim($_POST['time_from_home'] ?? ''),
            'distance_from_venue' => $distFromVenue,
            'time_from_venue' => $timeFromVenue,
            'check_in' => !empty($_POST['check_in']) ? $_POST['check_in'] : null,
            'check_out' => !empty($_POST['check_out']) ? $_POST['check_out'] : null,
            'reservation_no' => trim($_POST['reservation_no'] ?? ''),
            'price' => !empty($_POST['price']) ? (int) $_POST['price'] : null,
            'num_guests' => !empty($_POST['num_guests']) ? (int) $_POST['num_guests'] : null,
            'memo' => trim($_POST['memo'] ?? ''),
            'latitude' => $geoLat,
            'longitude' => $geoLng,
            'place_id' => $geoPlaceId,
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
        \Core\Database::connect(); // .env を $_ENV に読み込む（Geocoding 前に必須）

        $geoLat = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? trim($_POST['latitude']) : null;
        $geoLng = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? trim($_POST['longitude']) : null;
        $geoPlaceId = trim($_POST['place_id'] ?? '') ?: null;
        if ($geoLat === null || $geoLng === null) {
            $address = trim($_POST['address'] ?? '');
            $hotelName = trim($_POST['hotel_name'] ?? '');
            $geocodeTarget = $address ?: $hotelName;
            if ($geocodeTarget !== '') {
                $geo = (new MapsGeocodeService())->geocode($geocodeTarget);
                if ($geo) {
                    $geoLat = $geo['latitude'];
                    $geoLng = $geo['longitude'];
                    $geoPlaceId = $geo['place_id'];
                }
            }
        }

        $distFromVenue = trim($_POST['distance_from_venue'] ?? '');
        $timeFromVenue = trim($_POST['time_from_venue'] ?? '');
        if ($geoLat !== null && $geoLng !== null) {
            $venue = $this->getVenueCoordinatesForTrip($tripPlanId, $userId);
            if ($venue !== null) {
                $dm = (new MapsDistanceMatrixService())->getDistanceAndDuration(
                    $venue['lat'], $venue['lng'],
                    $geoLat, $geoLng
                );
                if ($dm !== null) {
                    $distFromVenue = $dm['distance'];
                    $timeFromVenue = $dm['duration'];
                }
            }
        }

        $model->update($id, [
            'hotel_name' => trim($_POST['hotel_name'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'distance_from_home' => trim($_POST['distance_from_home'] ?? ''),
            'time_from_home' => trim($_POST['time_from_home'] ?? ''),
            'distance_from_venue' => $distFromVenue,
            'time_from_venue' => $timeFromVenue,
            'check_in' => !empty($_POST['check_in']) ? $_POST['check_in'] : null,
            'check_out' => !empty($_POST['check_out']) ? $_POST['check_out'] : null,
            'reservation_no' => trim($_POST['reservation_no'] ?? ''),
            'price' => !empty($_POST['price']) ? (int) $_POST['price'] : null,
            'num_guests' => !empty($_POST['num_guests']) ? (int) $_POST['num_guests'] : null,
            'memo' => trim($_POST['memo'] ?? ''),
            'latitude' => $geoLat,
            'longitude' => $geoLng,
            'place_id' => $geoPlaceId,
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

    public function storeDestination(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        \Core\Database::connect();

        $geoLat = trim($_POST['latitude'] ?? '') ?: null;
        $geoLng = trim($_POST['longitude'] ?? '') ?: null;
        $geoPlaceId = trim($_POST['place_id'] ?? '') ?: null;
        if ($geoLat === null || $geoLng === null) {
            $address = trim($_POST['address'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $geocodeTarget = $address ?: $name;
            if ($geocodeTarget !== '') {
                $geo = (new MapsGeocodeService())->geocode($geocodeTarget);
                if ($geo) {
                    $geoLat = $geo['latitude'];
                    $geoLng = $geo['longitude'];
                    $geoPlaceId = $geo['place_id'];
                }
            }
        }

        $type = trim($_POST['destination_type'] ?? 'other');
        if (!in_array($type, ['main', 'collab', 'sightseeing', 'other'], true)) $type = 'other';

        $model = new DestinationModel();
        $model->create([
            'trip_plan_id' => $tripPlanId,
            'name' => trim($_POST['name'] ?? ''),
            'destination_type' => $type,
            'address' => trim($_POST['address'] ?? ''),
            'visit_date' => !empty($_POST['visit_date']) ? $_POST['visit_date'] : null,
            'visit_time' => trim($_POST['visit_time'] ?? '') ?: null,
            'memo' => trim($_POST['memo'] ?? ''),
            'latitude' => $geoLat,
            'longitude' => $geoLng,
            'place_id' => $geoPlaceId,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);
        $this->redirectToShow($tripPlanId, 'destination');
    }

    public function updateDestination(): void {
        $this->requireAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new DestinationModel();
        $row = $model->find($id);
        if (!$row || (int)$row['trip_plan_id'] !== $tripPlanId) { header('Location: /live_trip/'); exit; }
        \Core\Database::connect();

        $geoLat = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? trim($_POST['latitude']) : null;
        $geoLng = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? trim($_POST['longitude']) : null;
        $geoPlaceId = trim($_POST['place_id'] ?? '') ?: null;
        if ($geoLat === null || $geoLng === null) {
            $address = trim($_POST['address'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $geocodeTarget = $address ?: $name;
            if ($geocodeTarget !== '') {
                $geo = (new MapsGeocodeService())->geocode($geocodeTarget);
                if ($geo) {
                    $geoLat = $geo['latitude'];
                    $geoLng = $geo['longitude'];
                    $geoPlaceId = $geo['place_id'];
                }
            }
        }

        $type = trim($_POST['destination_type'] ?? 'other');
        if (!in_array($type, ['main', 'collab', 'sightseeing', 'other'], true)) $type = 'other';

        $model->update($id, [
            'name' => trim($_POST['name'] ?? ''),
            'destination_type' => $type,
            'address' => trim($_POST['address'] ?? ''),
            'visit_date' => !empty($_POST['visit_date']) ? $_POST['visit_date'] : null,
            'visit_time' => trim($_POST['visit_time'] ?? '') ?: null,
            'memo' => trim($_POST['memo'] ?? ''),
            'latitude' => $geoLat,
            'longitude' => $geoLng,
            'place_id' => $geoPlaceId,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ]);
        $this->redirectToShow($tripPlanId, 'destination');
    }

    public function deleteDestination(): void {
        $this->requireAccess();
        $id = (int) ($_POST['id'] ?? 0);
        $tripPlanId = (int) ($_POST['trip_plan_id'] ?? 0);
        $userId = (int) $_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId)) { header('Location: /live_trip/'); exit; }
        $model = new DestinationModel();
        $model->delete($id);
        $this->redirectToShow($tripPlanId, 'destination');
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
        $mapsLink = trim($_POST['maps_link'] ?? '');
        if (strlen($mapsLink) > 2048) {
            $mapsLink = substr($mapsLink, 0, 2048);
        }
        $model->create([
            'trip_plan_id' => $tripPlanId,
            'departure_date' => $depDate,
            'transport_type' => trim($_POST['transport_type'] ?? ''),
            'route_memo' => trim($_POST['route_memo'] ?? ''),
            'departure' => trim($_POST['departure'] ?? ''),
            'arrival' => trim($_POST['arrival'] ?? ''),
            'duration_min' => !empty($_POST['duration_min']) ? (int) $_POST['duration_min'] : null,
            'scheduled_time' => trim($_POST['scheduled_time'] ?? '') ?: null,
            'amount' => $amount > 0 ? $amount : null,
            'maps_link' => $mapsLink !== '' ? $mapsLink : null,
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
        $mapsLink = trim($_POST['maps_link'] ?? '');
        if (strlen($mapsLink) > 2048) {
            $mapsLink = substr($mapsLink, 0, 2048);
        }
        $model->update($id, [
            'departure_date' => $depDate,
            'transport_type' => trim($_POST['transport_type'] ?? ''),
            'route_memo' => trim($_POST['route_memo'] ?? ''),
            'departure' => trim($_POST['departure'] ?? ''),
            'arrival' => trim($_POST['arrival'] ?? ''),
            'duration_min' => isset($_POST['duration_min']) && $_POST['duration_min'] !== '' ? (int) $_POST['duration_min'] : null,
            'scheduled_time' => trim($_POST['scheduled_time'] ?? '') ?: null,
            'amount' => isset($_POST['amount']) && $_POST['amount'] !== '' ? (int) $_POST['amount'] : null,
            'maps_link' => $mapsLink !== '' ? $mapsLink : null,
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
        $scheduledTime = trim($_POST['scheduled_time'] ?? '');
        $durationMin = $this->calcDurationMinFromPost($scheduledTime, $_POST['end_time'] ?? null, $_POST['duration_min'] ?? null);

        $placeId = trim($_POST['place_id'] ?? '') ?: null;
        $lat = trim($_POST['latitude'] ?? '') ?: null;
        $lng = trim($_POST['longitude'] ?? '') ?: null;
        $locationLabel = trim($_POST['location_label'] ?? '') ?: null;
        $locationAddress = trim($_POST['location_address'] ?? '') ?: null;

        if ($placeId !== null && ($lat === null || $lng === null)) {
            \Core\Database::connect();
            $geo = (new MapsGeocodeService())->geocodeByPlaceId($placeId);
            if ($geo) {
                $lat = $geo['latitude'] ?? $lat;
                $lng = $geo['longitude'] ?? $lng;
                $locationAddress = $locationAddress ?? ($geo['formatted_address'] ?? null);
            }
        }

        $model->create([
            'trip_plan_id' => $tripPlanId,
            'scheduled_date' => $schDate,
            'label' => trim($_POST['label'] ?? ''),
            'scheduled_time' => $scheduledTime,
            'duration_min' => $durationMin,
            'memo' => trim($_POST['memo'] ?? ''),
            'place_id' => $placeId,
            'latitude' => $lat,
            'longitude' => $lng,
            'location_label' => $locationLabel,
            'location_address' => $locationAddress,
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
        $scheduledTime = trim($_POST['scheduled_time'] ?? '');
        $durationMin = $this->calcDurationMinFromPost($scheduledTime, $_POST['end_time'] ?? null, $_POST['duration_min'] ?? null);

        $placeId = trim($_POST['place_id'] ?? '') ?: null;
        $lat = trim($_POST['latitude'] ?? '') ?: null;
        $lng = trim($_POST['longitude'] ?? '') ?: null;
        $locationLabel = trim($_POST['location_label'] ?? '') ?: null;
        $locationAddress = trim($_POST['location_address'] ?? '') ?: null;

        if ($placeId !== null && ($lat === null || $lng === null)) {
            \Core\Database::connect();
            $geo = (new MapsGeocodeService())->geocodeByPlaceId($placeId);
            if ($geo) {
                $lat = $geo['latitude'] ?? $lat;
                $lng = $geo['longitude'] ?? $lng;
                $locationAddress = $locationAddress ?? ($geo['formatted_address'] ?? null);
            }
        }

        $model->update($id, [
            'scheduled_date' => $schDate,
            'label' => trim($_POST['label'] ?? ''),
            'scheduled_time' => $scheduledTime,
            'duration_min' => $durationMin,
            'memo' => trim($_POST['memo'] ?? ''),
            'place_id' => $placeId,
            'latitude' => $lat,
            'longitude' => $lng,
            'location_label' => $locationLabel,
            'location_address' => $locationAddress,
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
        $valid = ['summary', 'info', 'expense', 'hotel', 'destination', 'transport', 'timeline', 'checklist'];
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
        foreach ($timelineItems as $t) {
            $date = $t['scheduled_date'] ?? $firstEv;
            $time = trim($t['scheduled_time'] ?? '') ?: '99:99';
            $durationMin = isset($t['duration_min']) && $t['duration_min'] !== '' ? (int)$t['duration_min'] : self::DEFAULT_TIMELINE_DURATION_MIN;
            $durationMin = $durationMin > 0 ? $durationMin : self::DEFAULT_TIMELINE_DURATION_MIN;
            $items[] = [
                'type' => 'timeline',
                'date' => $date,
                'time' => $time,
                'duration_min' => $durationMin,
                'sortKey' => $date . ' ' . $time,
                'data' => $t
            ];
        }
        foreach ($transportLegs as $t) {
            $st = trim($t['scheduled_time'] ?? '');
            if ($st === '') continue;
            $dd = $t['departure_date'] ?? $firstEv;
            $durationMin = isset($t['duration_min']) && $t['duration_min'] !== '' ? (int)$t['duration_min'] : self::DEFAULT_TIMELINE_DURATION_MIN;
            $durationMin = $durationMin > 0 ? $durationMin : self::DEFAULT_TIMELINE_DURATION_MIN;
            $items[] = [
                'type' => 'transport',
                'date' => $dd,
                'time' => $st,
                'duration_min' => $durationMin,
                'sortKey' => $dd . ' ' . $st,
                'data' => $t
            ];
        }
        usort($items, fn($a, $b) => strcmp($a['sortKey'], $b['sortKey']));
        return $items;
    }

    private function calcDurationMinFromPost(string $scheduledTime, mixed $endTimeRaw, mixed $durationMinRaw): ?int {
        $duration = null;
        if ($durationMinRaw !== null && $durationMinRaw !== '' && is_numeric($durationMinRaw)) {
            $d = (int)$durationMinRaw;
            if ($d > 0) {
                return $d;
            }
        }

        $endTime = is_string($endTimeRaw) ? trim($endTimeRaw) : '';
        if ($scheduledTime === '' || $endTime === '') {
            return null;
        }

        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $scheduledTime, $m1)) return null;
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $endTime, $m2)) return null;

        $sMin = ((int)$m1[1]) * 60 + (int)$m1[2];
        $eMin = ((int)$m2[1]) * 60 + (int)$m2[2];
        $diff = $eMin - $sMin;
        if ($diff <= 0) return null;
        return $diff;
    }

    private function isAjaxRequest(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function reorderChecklist(): void {
        $this->requireAccess();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error']);
            exit;
        }
        $tripPlanId = (int)($_POST['trip_plan_id'] ?? 0);
        $order = $_POST['order'] ?? [];
        $userId = (int)$_SESSION['user']['id'];
        if (!$this->canAccessTrip($tripPlanId, $userId) || !is_array($order)) {
            echo json_encode(['status' => 'error']);
            exit;
        }
        $orderedIds = array_map('intval', array_filter($order));
        (new ChecklistItemModel())->updateOrder($tripPlanId, $orderedIds);
        echo json_encode(['status' => 'ok']);
        exit;
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
        (new ChecklistItemModel())->deleteByTripPlanId($tripPlanId);
        (new MyListItemModel())->copyToChecklist($myListId, $tripPlanId, $userId);
        $_SESSION['flash_success'] = 'マイリストの内容でチェックリストを置き換えました。';
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
        // 1フォーム2ボタン時代の名残で action が送られない場合のフォールバック（Enter送信など）
        if ($action === '' && trim($_POST['list_name'] ?? '') !== '') {
            $action = 'new';
        }
        if ($action === '' && (int)($_POST['my_list_id'] ?? 0) > 0) {
            $action = 'add';
        }
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
            $pdo = \Core\Database::connect();
            $pdo->prepare("DELETE FROM lt_my_list_items WHERE my_list_id = ?")->execute([$myListId]);
            foreach ($items as $i => $item) {
                $itemModel->create([
                    'my_list_id' => $myListId,
                    'item_name' => $item['item_name'],
                    'sort_order' => $i,
                ]);
            }
            $_SESSION['flash_success'] = 'マイリストを更新しました。';
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
        header('Location: /live_trip/my_list.php');
        exit;
    }

    public function reorderMyListItems(): void {
        $this->requireAccess();
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error']);
            exit;
        }
        $myListId = (int)($_POST['my_list_id'] ?? 0);
        $order = $_POST['order'] ?? [];
        $userId = (int)$_SESSION['user']['id'];
        $listModel = new MyListModel();
        $list = $listModel->find($myListId);
        if (!$list || (int)$list['user_id'] !== $userId || !is_array($order)) {
            echo json_encode(['status' => 'error']);
            exit;
        }
        $orderedIds = array_map('intval', array_filter($order));
        (new MyListItemModel())->updateOrder($myListId, $orderedIds);
        echo json_encode(['status' => 'ok']);
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
        header('Location: /live_trip/my_list.php');
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
        \Core\Database::connect(); // .env を $_ENV に読み込む（Geocoding 前に必須）
        $eventPlace = trim($_POST['event_place'] ?? '');
        $eventPlaceAddress = trim($_POST['event_place_address'] ?? '') ?: null;

        $geoLat = null;
        $geoLng = null;
        $geoPlaceId = null;
        $geocodeTarget = $eventPlaceAddress ?: $eventPlace;
        if ($geocodeTarget !== '') {
            $geo = (new MapsGeocodeService())->geocode($geocodeTarget);
            if ($geo) {
                $geoLat = $geo['latitude'];
                $geoLng = $geo['longitude'];
                $geoPlaceId = $geo['place_id'];
            }
        }

        $model = new LtEventModel();
        $model->create([
            'event_name' => trim($_POST['event_name'] ?? ''),
            'event_date' => $_POST['event_date'] ?? '',
            'event_place' => $eventPlace ?: null,
            'event_place_address' => $eventPlaceAddress,
            'latitude' => $geoLat,
            'longitude' => $geoLng,
            'place_id' => $geoPlaceId,
            'event_info' => trim($_POST['event_info'] ?? ''),
        ]);
        $redirect = $_POST['redirect'] ?? '/live_trip/create.php';
        header('Location: ' . $redirect);
        exit;
    }

    private function canAccessTrip(int $tripPlanId, int $userId): bool {
        return (new TripMemberModel())->isMember($tripPlanId, $userId);
    }

    /**
     * 遠征の会場座標を取得（最初のイベントから）
     * @return array{lat: string, lng: string}|null
     */
    private function getVenueCoordinatesForTrip(int $tripPlanId, int $userId): ?array {
        $events = (new TripPlanEventModel())->getByTripPlanId($tripPlanId, $userId);
        if (empty($events)) return null;

        $ev = $events[0];
        $lat = trim($ev['venue_latitude'] ?? '');
        $lng = trim($ev['venue_longitude'] ?? '');
        if ($lat !== '' && $lng !== '') {
            return ['lat' => $lat, 'lng' => $lng];
        }

        $geocodeTarget = trim($ev['venue_address'] ?? '') ?: trim($ev['event_place'] ?? '') ?: trim($ev['hn_event_place'] ?? '');
        if ($geocodeTarget === '') return null;

        $geo = (new MapsGeocodeService())->geocode($geocodeTarget);
        return $geo ? ['lat' => $geo['latitude'], 'lng' => $geo['longitude']] : null;
    }

    /**
     * Google Maps 経路URL解決 API（貼り付け用）
     * GET url= で受け取り、発・着・所要時間・resolved_url を返す
     */
    public function resolveMapsLink(): void {
        $this->requireAccess();
        header('Content-Type: application/json; charset=utf-8');

        \Core\Database::connect();
        $url = trim($_GET['url'] ?? '');
        if ($url === '') {
            echo json_encode(['status' => 'error', 'message' => 'URLを入力してください']);
            exit;
        }
        $result = (new MapsLinkResolveService())->resolve($url);
        echo json_encode($result);
        exit;
    }

    /**
     * Places Autocomplete API（発着・宿泊などの入力補完）
     */
    public function placesAutocomplete(): void {
        $this->requireAccess();
        header('Content-Type: application/json; charset=utf-8');

        \Core\Database::connect();
        $input = trim($_GET['input'] ?? '');

        if ($input === '') {
            echo json_encode(['status' => 'ok', 'predictions' => []]);
            exit;
        }

        $predictions = (new MapsPlacesAutocompleteService())->getSuggestions($input);
        echo json_encode(['status' => 'ok', 'predictions' => $predictions]);
        exit;
    }

    /**
     * Directions overview_polyline API（地図描画用）
     * GET origin= destination= mode= departure_date=（Y-m-d） を受け取って返す
     */
    public function directionsPolyline(): void {
        $this->requireAccess();
        header('Content-Type: application/json; charset=utf-8');

        \Core\Database::connect();
        $origin = trim($_GET['origin'] ?? '');
        $destination = trim($_GET['destination'] ?? '');
        $mode = trim($_GET['mode'] ?? 'transit');
        $departureDate = trim($_GET['departure_date'] ?? '') ?: null;
        $debug = (string)($_GET['debug'] ?? '') === '1';

        // #region agent log
        try {
            // repo root: private/apps/LiveTrip/Controller -> (Controller -> LiveTrip -> apps -> private -> root)
            $debugLogPath = dirname(__DIR__, 4) . '/.cursor/debug-572306.log';
            $payload = [
                'sessionId' => '572306',
                'runId' => 'pre-fix',
                'hypothesisId' => 'H4',
                'location' => 'private/apps/LiveTrip/Controller/LiveTripController.php',
                'message' => 'directionsPolyline_request',
                'data' => [
                    'originLen' => strlen($origin),
                    'destinationLen' => strlen($destination),
                    'mode' => $mode,
                    'departureDate' => $departureDate,
                ],
                'timestamp' => (int) floor(microtime(true) * 1000),
            ];
            @file_put_contents($debugLogPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
        } catch (\Throwable $e) { }
        // #endregion agent log

        if ($origin === '' || $destination === '') {
            echo json_encode(['status' => 'error', 'message' => 'origin/destination を指定してください']);
            exit;
        }
        if (!in_array($mode, ['driving', 'transit', 'walking', 'bicycling'], true)) {
            $mode = 'transit';
        }

        $svc = new MapsDirectionsService();
        $route = $svc->getOverviewPolyline($origin, $destination, $mode, $departureDate);
        if ($route === null) {
            // #region agent log
            try {
                // repo root: private/apps/LiveTrip/Controller -> (Controller -> LiveTrip -> apps -> private -> root)
                $debugLogPath = dirname(__DIR__, 4) . '/.cursor/debug-572306.log';
                $payload = [
                    'sessionId' => '572306',
                    'runId' => 'pre-fix',
                    'hypothesisId' => 'H4',
                    'location' => 'private/apps/LiveTrip/Controller/LiveTripController.php',
                    'message' => 'directionsPolyline_routeNull',
                    'data' => [
                        'mode' => $mode,
                        'departureDate' => $departureDate,
                    ],
                    'timestamp' => (int) floor(microtime(true) * 1000),
                ];
                @file_put_contents($debugLogPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
            } catch (\Throwable $e) { }
            // #endregion agent log
            $payload = ['status' => 'error', 'message' => '取得できませんでした'];
            if ($debug) {
                $payload['debug'] = [
                    'directions' => $svc->getLastError(),
                ];
            }
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        // #region agent log
        try {
            // repo root: private/apps/LiveTrip/Controller -> (Controller -> LiveTrip -> apps -> private -> root)
            $debugLogPath = dirname(__DIR__, 4) . '/.cursor/debug-572306.log';
            $payload = [
                'sessionId' => '572306',
                'runId' => 'pre-fix',
                'hypothesisId' => 'H4',
                'location' => 'private/apps/LiveTrip/Controller/LiveTripController.php',
                'message' => 'directionsPolyline_ok',
                'data' => [
                    'polylineLen' => strlen((string)($route['polyline'] ?? '')),
                    'durationMin' => (int)($route['duration_min'] ?? 0),
                ],
                'timestamp' => (int) floor(microtime(true) * 1000),
            ];
            @file_put_contents($debugLogPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
        } catch (\Throwable $e) { }
        // #endregion agent log
        echo json_encode(['status' => 'ok', 'route' => $route]);
        exit;
    }
}
