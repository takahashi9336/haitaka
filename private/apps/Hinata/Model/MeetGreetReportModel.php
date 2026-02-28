<?php

namespace App\Hinata\Model;

use Core\BaseModel;

class MeetGreetReportModel extends BaseModel {
    protected string $table = 'hn_meetgreet_reports';
    protected array $fields = [
        'id', 'user_id', 'slot_id', 'ticket_used', 'my_nickname',
        'sort_order', 'created_at', 'updated_at'
    ];

    /**
     * スロットIDに紐づくレポ一覧を取得（メッセージ数付き）
     */
    public function getReportsBySlotId(int $slotId): array {
        $sql = "SELECT r.*,
                       (SELECT COUNT(*) FROM hn_meetgreet_report_messages m
                        WHERE m.report_id = r.id) as message_count
                FROM {$this->table} r
                WHERE r.slot_id = :slot_id AND r.user_id = :uid
                ORDER BY r.sort_order ASC, r.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slot_id' => $slotId, 'uid' => $this->userId]);
        return $stmt->fetchAll();
    }

    /**
     * レポ新規作成、挿入IDを返す
     */
    public function createReport(int $slotId, int $ticketUsed, ?string $nickname): string|false {
        $maxOrder = $this->pdo->prepare(
            "SELECT COALESCE(MAX(sort_order), -1) FROM {$this->table}
             WHERE slot_id = :slot_id AND user_id = :uid"
        );
        $maxOrder->execute(['slot_id' => $slotId, 'uid' => $this->userId]);
        $nextOrder = (int)$maxOrder->fetchColumn() + 1;

        $this->create([
            'slot_id'      => $slotId,
            'ticket_used'  => $ticketUsed,
            'my_nickname'  => $nickname,
            'sort_order'   => $nextOrder,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
        return $this->lastInsertId();
    }

    /**
     * スロットIDに紐づくレポ件数を取得
     */
    public function countBySlotId(int $slotId): int {
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE slot_id = :slot_id AND user_id = :uid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slot_id' => $slotId, 'uid' => $this->userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * 複数スロットIDに対するレポ件数を一括取得
     */
    public function countBySlotIds(array $slotIds): array {
        if (empty($slotIds)) return [];
        $placeholders = implode(',', array_fill(0, count($slotIds), '?'));
        $sql = "SELECT slot_id, COUNT(*) as cnt FROM {$this->table}
                WHERE slot_id IN ({$placeholders}) AND user_id = ?
                GROUP BY slot_id";
        $stmt = $this->pdo->prepare($sql);
        $params = array_values($slotIds);
        $params[] = $this->userId;
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['slot_id']] = (int)$row['cnt'];
        }
        return $result;
    }

    /**
     * 複数スロットIDに対する ticket_used 合計を一括取得
     */
    public function sumTicketUsedBySlotIds(array $slotIds): array {
        if (empty($slotIds)) return [];
        $placeholders = implode(',', array_fill(0, count($slotIds), '?'));
        $sql = "SELECT slot_id, SUM(ticket_used) as total FROM {$this->table}
                WHERE slot_id IN ({$placeholders}) AND user_id = ?
                GROUP BY slot_id";
        $stmt = $this->pdo->prepare($sql);
        $params = array_values($slotIds);
        $params[] = $this->userId;
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['slot_id']] = (int)$row['total'];
        }
        return $result;
    }
}
