<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * イベント応募締め切りモデル（1イベントに複数ラウンド）
 */
class EventApplicationModel extends BaseModel {
    protected string $table = 'hn_event_applications';
    protected array $fields = [
        'id', 'event_id', 'round_name', 'application_start', 'application_deadline',
        'announcement_date', 'application_url', 'memo', 'sort_order',
        'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    /**
     * 応募締め切りが近いもの（未来7日以内）を取得
     */
    public function getUpcomingDeadlines(int $days = 7): array {
        $sql = "SELECT ea.*, e.event_name, e.event_date, e.category
                FROM {$this->table} ea
                JOIN hn_events e ON e.id = ea.event_id
                WHERE ea.application_deadline >= NOW()
                  AND ea.application_deadline <= DATE_ADD(NOW(), INTERVAL :days DAY)
                ORDER BY ea.application_deadline ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * イベントID指定で応募ラウンド一覧取得
     */
    public function getByEventId(int $eventId): array {
        $sql = "SELECT * FROM {$this->table} WHERE event_id = :eid ORDER BY sort_order ASC, application_deadline ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['eid' => $eventId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * イベントの応募ラウンドを一括置換
     */
    public function replaceForEvent(int $eventId, array $rows): void {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM {$this->table} WHERE event_id = ?")->execute([$eventId]);
            foreach ($rows as $i => $r) {
                if (empty($r['application_deadline'])) continue;
                $this->create([
                    'event_id'            => $eventId,
                    'round_name'          => $r['round_name'] ?? '',
                    'application_start'   => $r['application_start'] ?: null,
                    'application_deadline'=> $r['application_deadline'],
                    'announcement_date'   => $r['announcement_date'] ?? null,
                    'application_url'     => $r['application_url'] ?? null,
                    'memo'                => $r['memo'] ?? null,
                    'sort_order'          => $i,
                ]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
