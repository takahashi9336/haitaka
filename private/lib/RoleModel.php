<?php

namespace Core;

/**
 * ロールマスタ（sys_roles）システム用モデル
 */
class RoleModel {
    protected \PDO $pdo;
    protected string $table = 'sys_roles';

    public function __construct() {
        $this->pdo = Database::connect();
    }

    public function getByRoleKey(string $roleKey): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE role_key = :k LIMIT 1");
        $stmt->execute(['k' => $roleKey]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getAll(): array {
        $sql = "SELECT * FROM {$this->table} ORDER BY id ASC";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return int 新規ID。失敗時は 0 */
    public function create(array $data): int {
        $cols = ['role_key','name','description','default_route','logo_text','sidebar_mode'];
        $filtered = array_intersect_key($data, array_flip($cols));
        $filtered['default_route'] = $filtered['default_route'] ?? '/index.php';
        $filtered['sidebar_mode'] = $filtered['sidebar_mode'] ?? 'full';
        $keys = array_keys($filtered);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $keys) . ") VALUES (:" . implode(',:', $keys) . ")";
        if (!$this->pdo->prepare($sql)->execute($filtered)) {
            return 0;
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $cols = ['role_key','name','description','default_route','logo_text','sidebar_mode'];
        $filtered = array_intersect_key($data, array_flip($cols));
        if (empty($filtered)) {
            return true;
        }
        $sets = [];
        foreach (array_keys($filtered) as $k) {
            $sets[] = "{$k} = :{$k}";
        }
        $filtered['id'] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id";
        return $this->pdo->prepare($sql)->execute($filtered);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
