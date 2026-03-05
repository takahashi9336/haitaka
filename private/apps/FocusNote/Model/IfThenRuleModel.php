<?php

namespace App\FocusNote\Model;

use Core\BaseModel;

/**
 * If-Then ルールモデル
 */
class IfThenRuleModel extends BaseModel {
    protected string $table = 'fn_if_then_rules';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id', 'goal_id', 'user_id', 'if_condition', 'then_action',
        'sort_order', 'created_at',
    ];

    /**
     * 目標に紐づく If-Then ルールを取得
     */
    public function getByGoalId(int $goalId): array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND goal_id = :gid ORDER BY sort_order ASC, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'gid' => $goalId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * If-Then ルールを一括置換（削除してから挿入）
     */
    public function replaceForGoal(int $goalId, array $items): void {
        $this->pdo->prepare("DELETE FROM {$this->table} WHERE user_id = :uid AND goal_id = :gid")
            ->execute(['uid' => $this->userId, 'gid' => $goalId]);

        foreach ($items as $i => $item) {
            $ifCond = trim($item['if_condition'] ?? '');
            $thenAct = trim($item['then_action'] ?? '');
            if ($ifCond === '' && $thenAct === '') continue;

            $this->create([
                'goal_id' => $goalId,
                'if_condition' => $ifCond ?: '',
                'then_action' => $thenAct ?: '',
                'sort_order' => (int) ($item['sort_order'] ?? $i),
            ]);
        }
    }
}
