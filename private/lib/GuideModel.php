<?php

namespace Core;

/**
 * sys_guides 用モデル（プラットフォーム共通・user_id 隔離なし）
 */
class GuideModel extends BaseModel {
    protected string $table = 'sys_guides';
    protected array $fields = [
        'id', 'guide_key', 'title', 'blocks', 'app_key', 'show_on_first_visit', 'sort_order',
        'created_at', 'updated_at',
    ];
    protected bool $isUserIsolated = false;

    /**
     * ID指定取得（blocks をデコード）
     */
    public function find($id): ?array {
        $row = parent::find($id);
        if ($row && !empty($row['blocks']) && is_string($row['blocks'])) {
            $row['blocks'] = json_decode($row['blocks'], true);
        }
        return $row;
    }

    /**
     * guide_key で取得
     */
    public function findByKey(string $guideKey): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE guide_key = :guide_key LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['guide_key' => $guideKey]);
        $row = $stmt->fetch();
        if ($row && !empty($row['blocks'])) {
            $row['blocks'] = is_string($row['blocks']) ? json_decode($row['blocks'], true) : $row['blocks'];
        }
        return $row ?: null;
    }

    /**
     * 全件取得（並び順）
     */
    public function getAll(): array {
        $sql = "SELECT * FROM {$this->table} ORDER BY sort_order ASC, id ASC";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            if (!empty($row['blocks']) && is_string($row['blocks'])) {
                $row['blocks'] = json_decode($row['blocks'], true);
            }
        }
        return $rows;
    }

    /**
     * 作成（blocks を JSON 化）
     */
    public function createGuide(array $data): int {
        $filtered = $this->filterFields($data);
        if (isset($filtered['blocks']) && is_array($filtered['blocks'])) {
            $filtered['blocks'] = json_encode($filtered['blocks'], JSON_UNESCAPED_UNICODE);
        }
        $columns = implode(', ', array_keys($filtered));
        $placeholders = ':' . implode(', :', array_keys($filtered));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->pdo->prepare($sql)->execute($filtered);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * 更新（blocks を JSON 化）
     */
    public function updateGuide(int $id, array $data): bool {
        $filtered = $this->filterFields($data);
        if (isset($filtered['blocks']) && is_array($filtered['blocks'])) {
            $filtered['blocks'] = json_encode($filtered['blocks'], JSON_UNESCAPED_UNICODE);
        }
        if (empty($filtered)) return false;
        $sets = [];
        foreach (array_keys($filtered) as $key) {
            $sets[] = "{$key} = :{$key}";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :target_id";
        $params = array_merge($filtered, ['target_id' => $id]);
        return $this->pdo->prepare($sql)->execute($params);
    }
}
