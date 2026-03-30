<?php

namespace Core;

/**
 * アプリ・画面マスタ（sys_apps）システム用モデル
 * ユーザー隔離なし
 */
class AppModel extends BaseModel {
    protected string $table = 'sys_apps';
    protected array $fields = [
        'id',
        'app_key',
        'name',
        'parent_id',
        'route_prefix',
        'path',
        'icon_class',
        'theme_primary',
        'theme_light',
        'default_route',
        'description',
        'is_system',
        'sort_order',
        'is_visible',
        'admin_only',
    ];

    protected bool $isUserIsolated = false;

    public function __construct() {
        parent::__construct();
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
        return $this->find($id);
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
}
