<?php

namespace App\FocusNote\Model;

use Core\BaseModel;

/**
 * 行動目標モデル（MAC: content, measurement, is_process_goal）
 */
class ActionGoalModel extends BaseModel {
    protected string $table = 'fn_action_goals';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id', 'goal_id', 'user_id', 'content', 'measurement', 'is_process_goal',
        'sort_order', 'created_at',
    ];

    /**
     * 目標に紐づく行動目標を取得
     */
    public function getByGoalId(int $goalId): array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND goal_id = :gid ORDER BY sort_order ASC, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'gid' => $goalId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 行動目標を一括置換（削除してから挿入）
     */
    public function replaceForGoal(int $goalId, array $items): void {
        $this->pdo->prepare("DELETE FROM {$this->table} WHERE user_id = :uid AND goal_id = :gid")
            ->execute(['uid' => $this->userId, 'gid' => $goalId]);

        foreach ($items as $i => $item) {
            $content = trim($item['content'] ?? '');
            if ($content === '') continue;

            $this->create([
                'goal_id' => $goalId,
                'content' => $content,
                'measurement' => trim($item['measurement'] ?? ''),
                'is_process_goal' => !empty($item['is_process_goal']) ? 1 : 0,
                'sort_order' => (int) ($item['sort_order'] ?? $i),
            ]);
        }
    }
}
