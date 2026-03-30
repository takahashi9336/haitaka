<?php

namespace App\LiveTrip\Service;

use App\Hinata\Model\EventModel as HinataEventModel;
use Core\Database;

/**
 * LiveTrip から Hinata のイベント機能にアクセスするための橋渡し
 * - LiveTrip が Hinata の Model を直接扱わないための隔離レイヤ
 */
class HinataEventBridge {
    public function getAllUpcomingEvents(): array {
        return (new HinataEventModel())->getAllUpcomingEvents();
    }

    public function upsertUserEventStatus(int $userId, int $eventId, ?string $seatInfo, ?string $impression): void {
        $pdo = Database::connect();
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

