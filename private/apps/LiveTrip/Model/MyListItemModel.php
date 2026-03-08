<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * マイリスト項目モデル
 */
class MyListItemModel extends BaseModel {
    protected string $table = 'lt_my_list_items';
    protected array $fields = ['id', 'my_list_id', 'item_name', 'sort_order', 'created_at'];
    protected bool $isUserIsolated = false; // my_list 経由でユーザー紐付け

    public function getByMyListId(int $myListId): array {
        $sql = "SELECT * FROM {$this->table} WHERE my_list_id = :lid ORDER BY sort_order, id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['lid' => $myListId]);
        return $stmt->fetchAll();
    }

    public function updateOrder(int $myListId, array $orderedIds): bool {
        foreach ($orderedIds as $i => $id) {
            $stmt = $this->pdo->prepare("UPDATE {$this->table} SET sort_order = ? WHERE id = ? AND my_list_id = ?");
            $stmt->execute([$i, (int)$id, $myListId]);
        }
        return true;
    }

    public function copyToChecklist(int $myListId, int $tripPlanId, int $userId): int {
        $items = $this->getByMyListId($myListId);
        $listModel = new MyListModel();
        $list = $listModel->find($myListId);
        if (!$list || (int)$list['user_id'] !== $userId) return 0;
        $checkModel = new ChecklistItemModel();
        $count = 0;
        foreach ($items as $i => $item) {
            $checkModel->create([
                'trip_plan_id' => $tripPlanId,
                'item_name' => $item['item_name'],
                'checked' => 0,
                'sort_order' => $i,
            ]);
            $count++;
        }
        return $count;
    }
}
