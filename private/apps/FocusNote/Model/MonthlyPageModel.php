<?php

namespace App\FocusNote\Model;

use Core\BaseModel;

/**
 * マンスリーページモデル
 * 物理パス: haitaka/private/apps/FocusNote/Model/MonthlyPageModel.php
 */
class MonthlyPageModel extends BaseModel {
    protected string $table = 'fn_monthly_pages';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id', 'user_id', 'ym', 'target', 'importance_check',
        'concrete_imaging', 'reverse_planning', 'created_at', 'updated_at'
    ];

    /**
     * 年月で取得（なければ null）
     * カラム名は ym または year_month に対応（移行期の互換性）
     */
    public function findByYearMonth(string $yearMonth): ?array {
        $cols = ['ym', 'year_month'];
        foreach ($cols as $col) {
            try {
                $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND `{$col}` = :ym LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['uid' => $this->userId, 'ym' => $yearMonth]);
                $row = $stmt->fetch();
                if ($row !== false) {
                    $row['ym'] = $row['ym'] ?? $row['year_month'] ?? null;
                    return $row;
                }
            } catch (\PDOException $e) {
                if (strpos($e->getMessage(), "Unknown column") !== false) {
                    continue;
                }
                throw $e;
            }
        }
        return null;
    }

    /**
     * 年月で取得または自動作成
     */
    public function findOrCreateForYearMonth(string $yearMonth): array {
        $page = $this->findByYearMonth($yearMonth);
        if ($page) {
            return $page;
        }
        $this->createMonthlyPage($yearMonth);
        return $this->findByYearMonth($yearMonth) ?? [];
    }

    /**
     * マンスリーページ新規作成（ym または year_month に対応）
     */
    private function createMonthlyPage(string $yearMonth): void {
        foreach (['ym', 'year_month'] as $col) {
            try {
                $sql = "INSERT INTO {$this->table} (user_id, `{$col}`, target, importance_check, concrete_imaging, reverse_planning)
                        VALUES (:uid, :ym, '', '', '', '')";
                $this->pdo->prepare($sql)->execute(['uid' => $this->userId, 'ym' => $yearMonth]);
                return;
            } catch (\PDOException $e) {
                if (strpos($e->getMessage(), "Unknown column") !== false) {
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * 更新（部分更新対応）
     */
    public function savePage(int $id, array $data): bool {
        $allowed = ['target', 'importance_check', 'concrete_imaging', 'reverse_planning'];
        $filtered = array_intersect_key($data, array_flip($allowed));
        if (empty($filtered)) return false;
        return $this->update($id, $filtered);
    }
}
