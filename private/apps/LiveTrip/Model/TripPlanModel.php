<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * 遠征（参加計画）モデル
 * trip 主体。イベントは lt_trip_plan_events で紐づく
 */
class TripPlanModel extends BaseModel {
    protected string $table = 'lt_trip_plans';
    protected array $fields = [
        'id', 'title', 'impression', 'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    /**
     * ユーザーが参加する trip 一覧を取得
     * event_name, event_date, event_place は最初のイベントの情報
     */
    public function getMyTrips(int $userId): array {
        $sql = "SELECT tp.*, m.role,
                       (SELECT COALESCE(he.event_name, le.event_name) FROM lt_trip_plan_events tpe
                        LEFT JOIN hn_events he ON tpe.event_type = 'hinata' AND tpe.hn_event_id = he.id
                        LEFT JOIN lt_events le ON tpe.event_type = 'generic' AND tpe.lt_event_id = le.id
                        WHERE tpe.trip_plan_id = tp.id
                        ORDER BY COALESCE(he.event_date, le.event_date) ASC, tpe.id ASC LIMIT 1) AS event_name,
                       (SELECT COALESCE(he.event_date, le.event_date) FROM lt_trip_plan_events tpe
                        LEFT JOIN hn_events he ON tpe.event_type = 'hinata' AND tpe.hn_event_id = he.id
                        LEFT JOIN lt_events le ON tpe.event_type = 'generic' AND tpe.lt_event_id = le.id
                        WHERE tpe.trip_plan_id = tp.id
                        ORDER BY COALESCE(he.event_date, le.event_date) ASC, tpe.id ASC LIMIT 1) AS event_date,
                       (SELECT COALESCE(he.event_place, le.event_place) FROM lt_trip_plan_events tpe
                        LEFT JOIN hn_events he ON tpe.event_type = 'hinata' AND tpe.hn_event_id = he.id
                        LEFT JOIN lt_events le ON tpe.event_type = 'generic' AND tpe.lt_event_id = le.id
                        WHERE tpe.trip_plan_id = tp.id
                        ORDER BY COALESCE(he.event_date, le.event_date) ASC, tpe.id ASC LIMIT 1) AS event_place
                FROM {$this->table} tp
                JOIN lt_trip_members m ON m.trip_plan_id = tp.id AND m.user_id = :uid
                ORDER BY (
                    SELECT COALESCE(hev.event_date, lev.event_date) FROM lt_trip_plan_events tpe2
                    LEFT JOIN hn_events hev ON tpe2.event_type = 'hinata' AND tpe2.hn_event_id = hev.id
                    LEFT JOIN lt_events lev ON tpe2.event_type = 'generic' AND tpe2.lt_event_id = lev.id
                    WHERE tpe2.trip_plan_id = tp.id
                    ORDER BY COALESCE(hev.event_date, lev.event_date) ASC LIMIT 1
                ) DESC, tp.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * ユーザーがアクセス可能な trip を取得
     * events 配列は TripPlanEventModel::getByTripPlanId で別途取得すること
     */
    public function findForUser(int $id, int $userId): ?array {
        $sql = "SELECT tp.* FROM {$this->table} tp
                JOIN lt_trip_members m ON m.trip_plan_id = tp.id AND m.user_id = :uid
                WHERE tp.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * 日向坂イベントに紐づく遠征（参加計画）のうち、作成日が最も新しい trip の ID。
     * 複数ある場合は created_at DESC で先頭1件。
     */
    public function findLatestTripIdForHinataEvent(int $userId, int $hnEventId): ?int {
        if ($userId <= 0 || $hnEventId <= 0) {
            return null;
        }
        $sql = "SELECT tp.id
                FROM {$this->table} tp
                JOIN lt_trip_members m ON m.trip_plan_id = tp.id AND m.user_id = :uid
                JOIN lt_trip_plan_events tpe ON tpe.trip_plan_id = tp.id
                WHERE tpe.event_type = 'hinata' AND tpe.hn_event_id = :eid
                ORDER BY tp.created_at DESC, tp.id DESC
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'eid' => $hnEventId]);
        $v = $stmt->fetchColumn();
        return $v !== false ? (int) $v : null;
    }

    /**
     * 遠征の表示用情報を events から補完
     * event_name: 複数なら「A・B」、event_date: 範囲または最初、event_place: 会場名の重複除去後を「・」で結合
     */
    public static function enrichTripWithEvents(array $trip, array $events): array {
        $trip['events'] = $events;
        if (empty($events)) {
            $trip['event_name'] = null;
            $trip['event_date'] = null;
            $trip['event_place'] = null;
            $trip['hn_event_place'] = null;
            return $trip;
        }
        $names = array_unique(array_filter(array_column($events, 'event_name')));
        $dates = array_unique(array_filter(array_column($events, 'event_date')));
        sort($dates);
        $trip['event_name'] = implode('・', $names) ?: null;
        $trip['event_date'] = count($dates) > 1 ? ($dates[0] . '〜' . $dates[count($dates) - 1]) : ($dates[0] ?? null);
        $placeOrdered = [];
        foreach ($events as $e) {
            $p = trim((string)($e['event_place'] ?? ''));
            if ($p !== '' && !in_array($p, $placeOrdered, true)) {
                $placeOrdered[] = $p;
            }
        }
        $trip['event_place'] = !empty($placeOrdered) ? implode('・', $placeOrdered) : null;
        $trip['hn_event_place'] = $events[0]['hn_event_place'] ?? null;
        return $trip;
    }

    public function createWithMember(array $data, int $userId): int {
        $filtered = $this->filterFields($data);
        if (empty($filtered)) {
            $filtered = ['title' => '無題の遠征'];
        }
        $cols = implode(', ', array_keys($filtered));
        $placeholders = ':' . implode(', :', array_keys($filtered));
        $sql = "INSERT INTO {$this->table} ({$cols}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filtered);
        $tripId = (int) $this->pdo->lastInsertId();
        (new TripMemberModel())->addMember($tripId, $userId, 'owner');
        return $tripId;
    }
}
