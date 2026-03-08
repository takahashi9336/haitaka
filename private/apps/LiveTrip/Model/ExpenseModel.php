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

    /**
     * 複数 trip の費用合計を取得
     * @return array<int, int> trip_plan_id => total amount
     */
    public function getTotalsByTripPlanIds(array $tripPlanIds): array {
        if (empty($tripPlanIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($tripPlanIds), '?'));
        $sql = "SELECT trip_plan_id, COALESCE(SUM(amount), 0) AS total FROM {$this->table}
                WHERE trip_plan_id IN ({$placeholders}) GROUP BY trip_plan_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($tripPlanIds));
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['trip_plan_id']] = (int)$row['total'];
        }
        return $result;
    }
}
