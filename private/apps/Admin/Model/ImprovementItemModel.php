<?php

namespace App\Admin\Model;

use Core\BaseModel;

/**
 * sys_improvement_items 用モデル（管理者向け・user_id 隔離なし）
 */
class ImprovementItemModel extends BaseModel {
    protected string $table = 'sys_improvement_items';
    protected array $fields = [
        'id', 'screen_name', 'content', 'status', 'priority', 'source_url',
        'created_by', 'created_at', 'updated_at', 'resolved_at', 'memo',
    ];
    protected bool $isUserIsolated = false;

    public const STATUS_PENDING = 'pending';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        'pending' => '未対応',
        'done' => '対応済',
        'cancelled' => '見送り',
    ];

    /**
     * 改善事項を作成（created_by をセッションから設定）
     */
    public function createItem(array $data): int {
        $data['created_by'] = $this->userId ?? 0;
        $filtered = $this->filterFields($data);
        $columns = implode(', ', array_keys($filtered));
        $placeholders = ':' . implode(', :', array_keys($filtered));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->pdo->prepare($sql)->execute($filtered);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * 一覧取得（フィルタ・並び順）
     */
    public function getList(?string $statusFilter = null, ?string $screenNameFilter = null): array {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        if ($statusFilter !== null && $statusFilter !== '') {
            $sql .= " AND status = :status";
            $params['status'] = $statusFilter;
        }
        if ($screenNameFilter !== null && $screenNameFilter !== '') {
            $sql .= " AND screen_name LIKE :screen_name";
            $params['screen_name'] = '%' . $screenNameFilter . '%';
        }
        $sql .= " ORDER BY FIELD(status, 'pending', 'done', 'cancelled'), created_at DESC, id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * ステータス更新（resolved_at を自動設定）
     */
    public function updateStatus(int $id, string $status): bool {
        $updates = ['status' => $status];
        if ($status === self::STATUS_DONE) {
            $updates['resolved_at'] = date('Y-m-d H:i:s');
        } else {
            $updates['resolved_at'] = null;
        }
        return $this->update($id, $updates);
    }
}
