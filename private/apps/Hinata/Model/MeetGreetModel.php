<?php

namespace App\Hinata\Model;

use Core\BaseModel;

class MeetGreetModel extends BaseModel {
    protected string $table = 'hn_meetgreet_slots';
    protected array $fields = [
        'id', 'user_id', 'event_id', 'event_date', 'slot_name', 'start_time', 'end_time',
        'member_id', 'member_name_raw', 'ticket_count', 'report',
        'created_at', 'updated_at'
    ];

    /**
     * 日付ごとにグループ化したスロット一覧を取得
     * メンバー情報を LEFT JOIN で結合
     */
    public function getGroupedByDate(): array {
        $sql = "SELECT s.*, m.name as member_name,
                       c1.color_code as color1, c2.color_code as color2,
                       ev.event_name as linked_event_name
                FROM {$this->table} s
                LEFT JOIN hn_members m ON s.member_id = m.id
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                LEFT JOIN hn_events ev ON s.event_id = ev.id
                WHERE s.user_id = :uid
                ORDER BY s.event_date ASC, s.start_time ASC, s.slot_name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $date = $row['event_date'];
            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][] = $row;
        }
        return $grouped;
    }

    /**
     * 指定日付のスロットを一括登録
     */
    public function bulkInsert(string $eventDate, array $slots, ?int $eventId = null): int {
        $sql = "INSERT INTO {$this->table}
                (user_id, event_id, event_date, slot_name, start_time, end_time, member_id, member_name_raw, ticket_count, created_at)
                VALUES (:uid, :event_id, :event_date, :slot_name, :start_time, :end_time, :member_id, :member_name_raw, :ticket_count, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $count = 0;

        foreach ($slots as $slot) {
            $stmt->execute([
                'uid'             => $this->userId,
                'event_id'        => $eventId,
                'event_date'      => $eventDate,
                'slot_name'       => $slot['slot_name'],
                'start_time'      => $slot['start_time'] ?: null,
                'end_time'        => $slot['end_time'] ?: null,
                'member_id'       => $slot['member_id'] ?: null,
                'member_name_raw' => $slot['member_name_raw'] ?? null,
                'ticket_count'    => (int)($slot['ticket_count'] ?? 0),
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * 指定イベントIDに紐づくスロット一覧（メンバー情報付き）
     */
    public function getSlotsByEventId(int $eventId): array {
        $sql = "SELECT s.*, m.name as member_name,
                       c1.color_code as color1, c2.color_code as color2
                FROM {$this->table} s
                LEFT JOIN hn_members m ON s.member_id = m.id
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                WHERE s.user_id = :uid AND s.event_id = :event_id
                ORDER BY s.start_time ASC, s.slot_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    /**
     * 指定日付に紐づくスロット一覧（event_id未設定のものも含め日付ベースで取得）
     */
    public function getSlotsByDate(string $eventDate): array {
        $sql = "SELECT s.*, m.name as member_name,
                       c1.color_code as color1, c2.color_code as color2
                FROM {$this->table} s
                LEFT JOIN hn_members m ON s.member_id = m.id
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                WHERE s.user_id = :uid AND s.event_date = :event_date
                ORDER BY s.start_time ASC, s.slot_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'event_date' => $eventDate]);
        return $stmt->fetchAll();
    }

    /**
     * 指定日付のスロットを全削除
     */
    public function deleteByDate(string $eventDate): bool {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :uid AND event_date = :event_date";
        return $this->pdo->prepare($sql)->execute([
            'uid'        => $this->userId,
            'event_date' => $eventDate,
        ]);
    }
}
