<?php

namespace App\Health\Model;

use Core\BaseModel;

/**
 * Health: 食材ストック（キッチン在庫）
 */
class KitchenStockModel extends BaseModel {
    protected string $table = 'hl_kitchen_stock_items';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id',
        'user_id',
        'name',
        'qty',
        'purchased_date',
        'is_frozen',
        'created_at',
        'updated_at',
    ];

    public function getAllItems(): array {
        $sql = "SELECT " . implode(', ', $this->fields) . "
                FROM {$this->table}
                WHERE user_id = :uid
                ORDER BY (purchased_date IS NULL) ASC, purchased_date DESC, created_at DESC, id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetchAll();
    }
}

