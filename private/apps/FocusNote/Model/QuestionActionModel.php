<?php

namespace App\FocusNote\Model;

use Core\BaseModel;

/**
 * 質問型アクションモデル
 * 物理パス: haitaka/private/apps/FocusNote/Model/QuestionActionModel.php
 */
class QuestionActionModel extends BaseModel {
    protected string $table = 'fn_question_actions';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id', 'user_id', 'weekly_task_pick_id', 'scheduled_time', 'place',
        'question_text', 'done', 'actual_duration_min', 'done_at',
        'created_at', 'updated_at'
    ];

    /**
     * ウィークリーページのピックIDに紐づくアクションを取得（1:1想定）
     */
    public function findByWeeklyTaskPickId(int $weeklyTaskPickId): ?array {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :uid AND weekly_task_pick_id = :wtpid LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'wtpid' => $weeklyTaskPickId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * 今週の質問型アクション一覧（未完了優先、ピック・タスク内容含む）
     */
    public function getActionsForWeek(string $weekStart): array {
        $sql = "SELECT q.id, q.weekly_task_pick_id, q.scheduled_time, q.place, q.question_text,
                       q.done, q.actual_duration_min, q.done_at,
                       p.daily_task_id, t.content as task_content
                FROM {$this->table} q
                JOIN fn_weekly_task_picks p ON q.weekly_task_pick_id = p.id
                JOIN fn_daily_tasks t ON p.daily_task_id = t.id
                WHERE q.user_id = :uid AND p.weekly_page_id = (
                    SELECT id FROM fn_weekly_pages WHERE user_id = :uid2 AND week_start = :ws LIMIT 1
                )
                ORDER BY q.done ASC, p.sort_order ASC, q.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'uid2' => $this->userId, 'ws' => $weekStart]);
        return $stmt->fetchAll();
    }

    /**
     * 今週のアクションを取得（weekly_page_id 指定版）
     */
    public function getActionsByWeeklyPageId(int $weeklyPageId): array {
        $sql = "SELECT q.id, q.weekly_task_pick_id, q.scheduled_time, q.place, q.question_text,
                       q.done, q.actual_duration_min, q.done_at,
                       p.daily_task_id, t.content as task_content
                FROM fn_question_actions q
                JOIN fn_weekly_task_picks p ON q.weekly_task_pick_id = p.id
                JOIN fn_daily_tasks t ON p.daily_task_id = t.id
                WHERE q.user_id = :uid AND p.weekly_page_id = :wpid
                ORDER BY q.done ASC, p.sort_order ASC, q.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'wpid' => $weeklyPageId]);
        return $stmt->fetchAll();
    }

    /**
     * 完了状態をトグル
     */
    public function toggleDone(int $id): bool {
        $row = $this->find($id);
        if (!$row) return false;
        $done = $row['done'] ? 0 : 1;
        return $this->update($id, [
            'done' => $done,
            'done_at' => $done ? date('Y-m-d H:i:s') : null,
        ]);
    }

    /**
     * 完了＋所要時間を更新
     */
    public function markDone(int $id, ?int $durationMin = null): bool {
        $data = ['done' => 1, 'done_at' => date('Y-m-d H:i:s')];
        if ($durationMin !== null) {
            $data['actual_duration_min'] = $durationMin;
        }
        return $this->update($id, $data);
    }

    /**
     * アクション作成または更新
     */
    public function upsertForPick(int $weeklyTaskPickId, array $data): array {
        $existing = $this->findByWeeklyTaskPickId($weeklyTaskPickId);
        $filtered = array_intersect_key($data, array_flip(['scheduled_time', 'place', 'question_text']));
        if ($existing) {
            if (!empty($filtered)) {
                $this->update($existing['id'], $filtered);
            }
            return $this->find($existing['id']) ?? $existing;
        }
        $filtered['weekly_task_pick_id'] = $weeklyTaskPickId;
        $this->create($filtered);
        $id = $this->lastInsertId();
        return $this->find((int) $id) ?? [];
    }
}
