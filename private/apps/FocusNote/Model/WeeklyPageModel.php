<?php

namespace App\FocusNote\Model;

use Core\BaseModel;

/**
 * ウィークリーページモデル
 * 物理パス: haitaka/private/apps/FocusNote/Model/WeeklyPageModel.php
 */
class WeeklyPageModel extends BaseModel {
    protected string $table = 'fn_weekly_pages';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id', 'user_id', 'week_start', 'obstacle_contrast', 'obstacle_fix',
        'created_at', 'updated_at'
    ];

    /**
     * 週の月曜日で取得
     */
    public function findByWeekStart(string $weekStart): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND week_start = :ws LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'ws' => $weekStart]);
        return $stmt->fetch() ?: null;
    }

    /**
     * 取得または自動作成
     */
    public function findOrCreateForWeek(string $weekStart): array {
        $page = $this->findByWeekStart($weekStart);
        if ($page) {
            return $page;
        }
        $this->create([
            'week_start' => $weekStart,
            'obstacle_contrast' => '',
            'obstacle_fix' => '',
        ]);
        $id = $this->lastInsertId();
        return $this->find((int) $id) ?? [];
    }

    /**
     * 更新
     */
    public function savePage(int $id, array $data): bool {
        $allowed = ['obstacle_contrast', 'obstacle_fix'];
        $filtered = array_intersect_key($data, array_flip($allowed));
        if (empty($filtered)) return false;
        return $this->update($id, $filtered);
    }

    /**
     * 日付からその週の月曜日を取得
     */
    public static function getWeekStart(string $date): string {
        $ts = strtotime($date);
        $w = (int) date('w', $ts); // 0=Sun, 6=Sat
        $mondayOffset = $w === 0 ? -6 : 1 - $w;
        return date('Y-m-d', strtotime("+{$mondayOffset} days", $ts));
    }
}
