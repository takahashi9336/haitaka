<?php

namespace Core;

/**
 * ロール別アプリ許可（sys_role_apps）システム用モデル
 */
class RoleAppModel {
    protected \PDO $pdo;
    protected string $table = 'sys_role_apps';

    public function __construct() {
        $this->pdo = Database::connect();
    }

    /**
     * ロールに許可された app_id の一覧（sort_order 順）
     */
    public function getAppIdsByRoleId(int $roleId): array {
        $stmt = $this->pdo->prepare("SELECT app_id FROM {$this->table} WHERE role_id = :rid ORDER BY sort_order ASC, app_id ASC");
        $stmt->execute(['rid' => $roleId]);
        return array_map('intval', array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'app_id'));
    }

    /**
     * ロールのアプリ割り当てを一括保存（既存は削除してから挿入）
     */
    public function setForRole(int $roleId, array $appIdsWithOrder = []): bool {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE role_id = :rid");
            $stmt->execute(['rid' => $roleId]);
            $sort = 0;
            foreach ($appIdsWithOrder as $appId) {
                $appId = (int)$appId;
                if ($appId <= 0) {
                    continue;
                }
                $stmt = $this->pdo->prepare("INSERT INTO {$this->table} (role_id, app_id, sort_order) VALUES (:rid, :aid, :ord)");
                $stmt->execute(['rid' => $roleId, 'aid' => $appId, 'ord' => $sort++]);
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
