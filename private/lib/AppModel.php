<?php

namespace Core;

/**
 * アプリ・画面マスタ（sys_apps）システム用モデル
 * ユーザー隔離なし
 */
class AppModel {
    protected \PDO $pdo;
    protected string $table = 'sys_apps';

    public function __construct() {
        $this->pdo = Database::connect();
    }

    /**
     * 表示対象のアプリを sort_order 順で全件取得
     */
    public function getAllVisible(): array {
        $sql = "SELECT * FROM {$this->table} WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * ID指定取得
     */
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * app_key 指定取得
     */
    public function findByAppKey(string $appKey): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE app_key = :k");
        $stmt->execute(['k' => $appKey]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * 親子ツリーに組み立て（親のみ・子は children に格納）
     * $flat は getAllVisible の結果、または許可 app_id でフィルタ済みの配列
     */
    public function buildTree(array $flat, ?array $allowedAppIds = null): array {
        $allowedAppIds = $allowedAppIds !== null ? array_flip($allowedAppIds) : null;
        $byParent = [];
        foreach ($flat as $row) {
            if ($allowedAppIds !== null && !isset($allowedAppIds[(int)$row['id']])) {
                continue;
            }
            $pid = isset($row['parent_id']) && $row['parent_id'] !== null ? (int)$row['parent_id'] : 0;
            if (!isset($byParent[$pid])) {
                $byParent[$pid] = [];
            }
            $byParent[$pid][] = $row;
        }
        $build = function ($parentId) use (&$build, $byParent) {
            $list = $byParent[$parentId] ?? [];
            $result = [];
            foreach ($list as $app) {
                $app['children'] = $build((int)$app['id']);
                $result[] = $app;
            }
            return $result;
        };
        return $build(0);
    }

    /**
     * 全件取得（管理画面用・is_visible 関係なし）
     */
    public function getAll(): array {
        $sql = "SELECT * FROM {$this->table} ORDER BY sort_order ASC, id ASC";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 子を持つか
     */
    public function hasChildren(int $parentId): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM {$this->table} WHERE parent_id = :pid LIMIT 1");
        $stmt->execute(['pid' => $parentId]);
        return (bool)$stmt->fetch();
    }

    public function create(array $data): bool {
        $cols = ['app_key','name','parent_id','route_prefix','path','icon_class','theme_primary','theme_light','default_route','description','is_system','sort_order','is_visible','admin_only'];
        $filtered = array_intersect_key($data, array_flip($cols));
        $filtered['parent_id'] = $filtered['parent_id'] ?? null;
        $filtered['sort_order'] = (int)($filtered['sort_order'] ?? 0);
        $filtered['is_visible'] = (int)($filtered['is_visible'] ?? 1);
        $filtered['admin_only'] = (int)($filtered['admin_only'] ?? 0);
        $filtered['is_system'] = (int)($filtered['is_system'] ?? 0);
        $keys = array_keys($filtered);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $keys) . ") VALUES (:" . implode(',:', $keys) . ")";
        return $this->pdo->prepare($sql)->execute($filtered);
    }

    public function update(int $id, array $data): bool {
        $cols = ['app_key','name','parent_id','route_prefix','path','icon_class','theme_primary','theme_light','default_route','description','is_system','sort_order','is_visible','admin_only'];
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
