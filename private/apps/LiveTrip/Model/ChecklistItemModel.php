<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * チェックリスト項目モデル
 */
class ChecklistItemModel extends BaseModel {
    protected string $table = 'lt_checklist_items';
    protected array $fields = [
        'id', 'trip_plan_id', 'item_name', 'checked', 'sort_order',
        'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    public function getByTripPlanId(int $tripPlanId): array {
        $sql = "SELECT * FROM {$this->table} WHERE trip_plan_id = :tid ORDER BY sort_order, id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tid' => $tripPlanId]);
        return $stmt->fetchAll();
    }

    public function findByIdAndTripPlan(int $id, int $tripPlanId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ? AND trip_plan_id = ?");
        $stmt->execute([$id, $tripPlanId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateItemName(int $id, int $tripPlanId, string $itemName): bool {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET item_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND trip_plan_id = ?");
        return $stmt->execute([$itemName, $id, $tripPlanId]);
    }

    public function deleteByIdAndTripPlan(int $id, int $tripPlanId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ? AND trip_plan_id = ?");
        return $stmt->execute([$id, $tripPlanId]);
    }

    public function toggleChecked(int $id, int $tripPlanId, int $userId): bool {
        $memberModel = new TripMemberModel();
        if (!$memberModel->isMember($tripPlanId, $userId)) return false;
        $row = $this->pdo->prepare("SELECT checked FROM {$this->table} WHERE id = ? AND trip_plan_id = ?");
        $row->execute([$id, $tripPlanId]);
        $r = $row->fetch();
        if (!$r) return false;
        $newVal = $r['checked'] ? 0 : 1;
        $this->pdo->prepare("UPDATE {$this->table} SET checked = ? WHERE id = ?")->execute([$newVal, $id]);
        return true;
    }

    public function updateOrder(int $tripPlanId, array $orderedIds): bool {
        $pdo = $this->pdo;
        $stmt = $pdo->prepare("UPDATE {$this->table} SET sort_order = ? WHERE id = ? AND trip_plan_id = ?");
        foreach ($orderedIds as $i => $id) {
            $stmt->execute([$i, (int)$id, $tripPlanId]);
        }
        return true;
    }

    /**
     * 複数 trip のチェックリスト件数・チェック数を取得
     * @return array<int, array{total: int, checked: int}>
     */
    public function getCountsByTripPlanIds(array $tripPlanIds): array {
        if (empty($tripPlanIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($tripPlanIds), '?'));
        $sql = "SELECT trip_plan_id, COUNT(*) AS total, SUM(checked) AS checked FROM {$this->table}
                WHERE trip_plan_id IN ({$placeholders}) GROUP BY trip_plan_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($tripPlanIds));
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['trip_plan_id']] = [
                'total' => (int)$row['total'],
                'checked' => (int)$row['checked'],
            ];
        }
        return $result;
    }
}
