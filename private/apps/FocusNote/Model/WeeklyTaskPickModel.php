<?php

namespace App\FocusNote\Model;

use Core\BaseModel;

/**
 * ウィークリーで選んだタスクモデル
 * 物理パス: haitaka/private/apps/FocusNote/Model/WeeklyTaskPickModel.php
 */
class WeeklyTaskPickModel extends BaseModel {
    protected string $table = 'fn_weekly_task_picks';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id', 'user_id', 'weekly_page_id', 'daily_task_id', 'sort_order', 'created_at'
    ];

    /**
     * ウィークリーページに紐づく選んだタスク一覧（デイリータスク内容も含む）
     */
    public function getPicksWithTasks(int $weeklyPageId): array {
        $sql = "SELECT p.id, p.weekly_page_id, p.daily_task_id, p.sort_order,
                       t.content as task_content
                FROM {$this->table} p
                JOIN fn_daily_tasks t ON p.daily_task_id = t.id
                WHERE p.user_id = :uid AND p.weekly_page_id = :wpid
                ORDER BY p.sort_order ASC, p.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'wpid' => $weeklyPageId]);
        return $stmt->fetchAll();
    }

    /**
     * 選択タスクを一括置換
     */
    public function replacePicks(int $weeklyPageId, array $dailyTaskIds): bool {
        $this->pdo->beginTransaction();
        try {
            $del = "DELETE FROM {$this->table} WHERE user_id = :uid AND weekly_page_id = :wpid";
            $stmt = $this->pdo->prepare($del);
            $stmt->execute(['uid' => $this->userId, 'wpid' => $weeklyPageId]);

            foreach ($dailyTaskIds as $i => $dailyTaskId) {
                if (empty($dailyTaskId)) continue;
                $this->create([
                    'weekly_page_id' => $weeklyPageId,
                    'daily_task_id' => (int) $dailyTaskId,
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
