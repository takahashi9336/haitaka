<?php

namespace App\Health\Model;

use Core\BaseModel;

class RoutineModel extends BaseModel {
    protected string $table = 'hl_routines';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id',
        'user_id',
        'name',
        'type',
        'theme',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    public function getAllWithStructure(): array {
        $routines = $this->pdo->prepare(
            "SELECT id, name, type, theme, sort_order, updated_at
             FROM {$this->table}
             WHERE user_id = :uid
             ORDER BY sort_order ASC, id ASC"
        );
        $routines->execute(['uid' => $this->userId]);
        $rows = $routines->fetchAll();

        $phaseModel = new RoutinePhaseModel();
        $itemModel  = new RoutineItemModel();

        foreach ($rows as &$r) {
            $r['phases'] = $phaseModel->getByRoutineId((int)$r['id']);
            foreach ($r['phases'] as &$p) {
                $p['items'] = $itemModel->getByPhaseId((int)$p['id']);
            }
        }
        return $rows;
    }

    public function getOneWithStructure(int $id): ?array {
        $row = $this->find($id);
        if (!$row) return null;

        $phaseModel = new RoutinePhaseModel();
        $itemModel  = new RoutineItemModel();

        $row['phases'] = $phaseModel->getByRoutineId($id);
        foreach ($row['phases'] as &$p) {
            $p['items'] = $itemModel->getByPhaseId((int)$p['id']);
        }
        return $row;
    }

    public function nextSortOrder(): int {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(MAX(sort_order), -1) + 1 FROM {$this->table} WHERE user_id = :uid"
        );
        $stmt->execute(['uid' => $this->userId]);
        return (int)$stmt->fetchColumn();
    }
}
