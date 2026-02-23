<?php

namespace App\FocusNote\Model;

use Core\BaseModel;

/**
 * デイリータスクモデル
 * 物理パス: haitaka/private/apps/FocusNote/Model/DailyTaskModel.php
 */
class DailyTaskModel extends BaseModel {
    protected string $table = 'fn_daily_tasks';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id', 'user_id', 'monthly_page_id', 'content', 'sort_order', 'created_at'
    ];

    /**
     * マンスリーページに紐づくタスク一覧
     */
    public function getByMonthlyPageId(int $monthlyPageId): array {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :uid AND monthly_page_id = :mpid 
                ORDER BY sort_order ASC, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'mpid' => $monthlyPageId]);
        return $stmt->fetchAll();
    }

    /**
     * タスク一括保存（既存削除＋新規投入）
     */
    public function replaceTasks(int $monthlyPageId, array $contents): bool {
        $this->pdo->beginTransaction();
        try {
            $del = "DELETE FROM {$this->table} WHERE user_id = :uid AND monthly_page_id = :mpid";
            $stmt = $this->pdo->prepare($del);
            $stmt->execute(['uid' => $this->userId, 'mpid' => $monthlyPageId]);

            foreach ($contents as $i => $content) {
                if (trim($content) === '') continue;
                $this->create([
                    'monthly_page_id' => $monthlyPageId,
                    'content' => trim($content),
                    'sort_order' => $i,
                ]);
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
