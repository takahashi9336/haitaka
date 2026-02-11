<?php

namespace Core;

/**
 * BaseModel
 * 物理パス: haitaka/private/lib/BaseModel.php
 */
abstract class BaseModel {
    protected \PDO $pdo;
    protected ?int $userId = null;
    protected string $table = '';
    protected array $fields = [];

    /**
     * ユーザー隔離フラグ
     * true: 全てのクエリに user_id = :uid を自動付加する
     * false: 共有データとして扱い、user_id を無視する
     */
    protected bool $isUserIsolated = true;

    public function __construct() {
        $this->pdo = Database::connect();
        $this->userId = $_SESSION['user']['id'] ?? null;
    }

    protected function filterFields(array $data): array {
        return array_intersect_key($data, array_flip($this->fields));
    }

    /**
     * 全件取得 (隔離フラグ対応)
     */
    public function all(): array {
        $sql = "SELECT " . implode(', ', $this->fields) . " FROM {$this->table}";
        $params = [];
        if ($this->isUserIsolated) {
            $sql .= " WHERE user_id = :uid";
            $params['uid'] = $this->userId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * ID指定取得 (隔離フラグ対応)
     */
    public function find($id): ?array {
        $sql = "SELECT " . implode(', ', $this->fields) . " FROM {$this->table} WHERE id = :id";
        $params = ['id' => $id];
        if ($this->isUserIsolated) {
            $sql .= " AND user_id = :uid";
            $params['uid'] = $this->userId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): bool {
        $filtered = $this->filterFields($data);
        if ($this->isUserIsolated) {
            $filtered['user_id'] = $this->userId;
        }
        $columns = implode(', ', array_keys($filtered));
        $placeholders = ':' . implode(', :', array_keys($filtered));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        return $this->pdo->prepare($sql)->execute($filtered);
    }

    public function update($id, array $data): bool {
        $filtered = $this->filterFields($data);
        if (empty($filtered)) return false;
        $sets = [];
        foreach (array_keys($filtered) as $key) { $sets[] = "{$key} = :{$key}"; }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :target_id";
        $params = array_merge($filtered, ['target_id' => $id]);
        if ($this->isUserIsolated) {
            $sql .= " AND user_id = :uid";
            $params['uid'] = $this->userId;
        }
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function delete($id): bool {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $params = ['id' => $id];
        if ($this->isUserIsolated) {
            $sql .= " AND user_id = :uid";
            $params['uid'] = $this->userId;
        }
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function lastInsertId(): string|false {
        return $this->pdo->lastInsertId();
    }
}