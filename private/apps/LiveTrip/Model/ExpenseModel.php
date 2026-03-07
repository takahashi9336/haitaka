<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * 費用モデル
 */
class ExpenseModel extends BaseModel {
    protected string $table = 'lt_expenses';
    protected array $fields = ['id', 'trip_plan_id', 'category', 'amount', 'memo', 'created_at', 'updated_at'];
    protected bool $isUserIsolated = false;

    public static array $categories = [
        'transport' => '交通費',
        'hotel' => 'ホテル代',
        'ticket' => 'チケット',
        'food' => '食費',
        'goods' => 'グッズ・物販',
        'other' => 'その他'
    ];

    public function getByTripPlanId(int $tripPlanId): array {
        $sql = "SELECT * FROM {$this->table} WHERE trip_plan_id = :tid ORDER BY category, id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tid' => $tripPlanId]);
        return $stmt->fetchAll();
    }
}
