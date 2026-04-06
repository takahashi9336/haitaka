<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * 影ナレ（イベントに1件）モデル
 */
class EventShadowNarrationModel extends BaseModel {
    protected string $table = 'hn_event_shadow_narrations';
    protected array $fields = [
        'event_id', 'memo', 'created_at', 'updated_at', 'update_user'
    ];
    protected bool $isUserIsolated = false;

    public function getByEventId(int $eventId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        if (!$row) {
            return null;
        }
        $row['member_ids'] = $this->getMemberIdsByEventId($eventId);
        return $row;
    }

    public function getMemberIdsByEventId(int $eventId): array {
        $stmt = $this->pdo->prepare("SELECT member_id FROM hn_event_shadow_narration_members WHERE event_id = ? ORDER BY member_id ASC");
        $stmt->execute([$eventId]);
        return array_map('intval', array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'member_id'));
    }

    public function saveForEvent(int $eventId, array $memberIds, ?string $memo = null): void {
        $user = $_SESSION['user']['id_name'] ?? '';

        $this->pdo->prepare(
            "INSERT INTO {$this->table} (event_id, memo, update_user) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE memo = VALUES(memo), update_user = VALUES(update_user)"
        )->execute([$eventId, $memo, $user]);

        $this->pdo->prepare("DELETE FROM hn_event_shadow_narration_members WHERE event_id = ?")->execute([$eventId]);
        if (empty($memberIds)) {
            return;
        }

        $ins = $this->pdo->prepare("INSERT INTO hn_event_shadow_narration_members (event_id, member_id) VALUES (?, ?)");
        foreach ($memberIds as $mid) {
            $mid = (int)$mid;
            if ($mid <= 0) continue;
            $ins->execute([$eventId, $mid]);
        }
    }
}

