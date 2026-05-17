<?php

namespace App\Health\Model;

use Core\BaseModel;

/**
 * Health: トレーニング実施履歴
 */
class TrainingLogModel extends BaseModel {
    public const LOG_KIND_EXERCISE = 'exercise';
    public const LOG_KIND_HIIT = 'hiit';

    protected string $table = 'hl_training_logs';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id',
        'user_id',
        'log_kind',
        'menu_item_id',
        'menu_name',
        'reps',
        'duration_sec',
        'menu_snapshot',
        'performed_at',
        'created_at',
        'updated_at',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLogs(?string $from = null, ?string $to = null, int $limit = 200): array {
        $sql = "SELECT " . implode(', ', $this->fields) . "
                FROM {$this->table}
                WHERE user_id = :uid";
        $params = ['uid' => $this->userId];

        if ($from !== null && $from !== '') {
            $sql .= " AND performed_at >= :from_date";
            $params['from_date'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= " AND performed_at <= :to_date";
            $params['to_date'] = $to;
        }

        $sql .= " ORDER BY performed_at DESC, id DESC LIMIT " . max(1, min($limit, 500));
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * @return array{ok: bool, exercise_count?: int, duration_sec?: int}
     */
    public function createHiitSession(string $performedAt): array {
        $menuModel = new TrainingMenuModel();
        $menus = $menuModel->getAllItems();
        if ($menus === []) {
            return ['ok' => false];
        }

        $snapshot = [];
        $totalDuration = 0;
        foreach ($menus as $menu) {
            $durationSec = (int) ($menu['duration_sec'] ?? TrainingDuration::DEFAULT_SEC);
            $totalDuration += $durationSec;
            $snapshot[] = [
                'name' => (string) $menu['name'],
                'reps' => (int) $menu['reps'],
                'duration_sec' => $durationSec,
            ];
        }

        $ok = $this->create([
            'log_kind' => self::LOG_KIND_HIIT,
            'menu_item_id' => null,
            'menu_name' => 'HIIT',
            'reps' => count($snapshot),
            'duration_sec' => $totalDuration,
            'menu_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'performed_at' => $performedAt,
        ]);

        if (!$ok) {
            return ['ok' => false];
        }

        return [
            'ok' => true,
            'exercise_count' => count($snapshot),
            'duration_sec' => $totalDuration,
        ];
    }

    public function createFromMenuItem(
        int $menuItemId,
        string $performedAt,
        ?int $repsOverride = null,
        ?int $durationSecOverride = null
    ): bool {
        $menuModel = new TrainingMenuModel();
        $menu = $menuModel->find($menuItemId);
        if ($menu === null) {
            return false;
        }

        $reps = $repsOverride ?? (int) $menu['reps'];
        if ($reps < 1) {
            return false;
        }

        $durationSec = $durationSecOverride ?? (int) ($menu['duration_sec'] ?? TrainingDuration::DEFAULT_SEC);
        if ($durationSec < 0 || $durationSec > TrainingDuration::MAX_SEC) {
            return false;
        }

        return $this->create([
            'log_kind' => self::LOG_KIND_EXERCISE,
            'menu_item_id' => $menuItemId,
            'menu_name' => (string) $menu['name'],
            'reps' => $reps,
            'duration_sec' => $durationSec,
            'performed_at' => $performedAt,
        ]);
    }
}
