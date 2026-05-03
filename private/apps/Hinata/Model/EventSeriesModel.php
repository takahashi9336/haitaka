<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * 日向坂イベント「系列」マスタ
 * 物理パス: haitaka/private/apps/Hinata/Model/EventSeriesModel.php
 */
class EventSeriesModel extends BaseModel {
    protected string $table = 'hn_event_series';
    protected array $fields = ['id', 'name', 'created_at'];
    protected bool $isUserIsolated = false;

    /** @return list<array{id:int|string,name:string}> */
    public function allByNameAsc(): array {
        $sql = 'SELECT id, name FROM ' . $this->table . ' ORDER BY name ASC';
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function createByName(string $name): int {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('系列名を入力してください');
        }
        try {
            $ok = $this->create(['name' => $name]);
            if (!$ok) {
                throw new \RuntimeException('系列の作成に失敗しました');
            }
            $id = $this->lastInsertId();
            if ($id === false || (int)$id <= 0) {
                throw new \RuntimeException('系列IDの取得に失敗しました');
            }
            return (int)$id;
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] ?? null === 1062) {
                throw new \InvalidArgumentException('同じ名前の系列が既に存在します');
            }
            throw $e;
        }
    }
}
