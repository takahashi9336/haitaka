<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * スケジュールモデル
 */
class ScheduleModel extends BaseModel
{
    protected string $table = 'hn_schedule';
    protected array $fields = [
        'id', 'schedule_code', 'schedule_date', 'category',
        'time_text', 'title', 'detail_url', 'created_at', 'updated_at',
    ];
    protected bool $isUserIsolated = false;

    public function upsertSchedule(array $data): string
    {
        $existing = $this->findByCode($data['schedule_code']);
        if ($existing) {
            $this->pdo->prepare(
                "UPDATE {$this->table} SET
                    schedule_date = :schedule_date,
                    category      = :category,
                    time_text     = :time_text,
                    title         = :title,
                    detail_url    = :detail_url
                 WHERE schedule_code = :schedule_code"
            )->execute([
                'schedule_date' => $data['schedule_date'],
                'category'      => $data['category'] ?? '',
                'time_text'     => $data['time_text'] ?? null,
                'title'         => $data['title'] ?? '',
                'detail_url'    => $data['detail_url'],
                'schedule_code' => $data['schedule_code'],
            ]);
            return 'updated';
        }

        $this->pdo->prepare(
            "INSERT INTO {$this->table}
                (schedule_code, schedule_date, category, time_text, title, detail_url)
             VALUES
                (:schedule_code, :schedule_date, :category, :time_text, :title, :detail_url)"
        )->execute([
            'schedule_code' => $data['schedule_code'],
            'schedule_date' => $data['schedule_date'],
            'category'      => $data['category'] ?? '',
            'time_text'     => $data['time_text'] ?? null,
            'title'         => $data['title'] ?? '',
            'detail_url'    => $data['detail_url'],
        ]);
        return 'inserted';
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE schedule_code = :c LIMIT 1");
        $stmt->execute(['c' => $code]);
        return $stmt->fetch() ?: null;
    }

    public function getIdByCode(string $code): ?int
    {
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE schedule_code = :c LIMIT 1");
        $stmt->execute(['c' => $code]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    public function setMembers(int $scheduleId, array $memberIds): void
    {
        $this->pdo->prepare("DELETE FROM hn_schedule_members WHERE schedule_id = ?")->execute([$scheduleId]);
        if (empty($memberIds)) return;
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO hn_schedule_members (schedule_id, member_id) VALUES (?, ?)");
        foreach ($memberIds as $mid) {
            $stmt->execute([$scheduleId, $mid]);
        }
    }

    /**
     * 特定メンバーのスケジュール取得 (当月初～未来、新しい順)
     */
    public function getUpcomingByMember(int $memberId, int $limit = 10): array
    {
        $monthStart = date('Y-m-01');
        $stmt = $this->pdo->prepare(
            "SELECT s.*
             FROM {$this->table} s
             JOIN hn_schedule_members sm ON sm.schedule_id = s.id
             WHERE sm.member_id = :mid
               AND s.schedule_date >= :month_start
             ORDER BY s.schedule_date DESC, s.time_text DESC
             LIMIT :lim"
        );
        $stmt->bindValue('mid', $memberId, \PDO::PARAM_INT);
        $stmt->bindValue('month_start', $monthStart);
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 特定メンバーの直近スケジュール取得 (過去含む)
     */
    public function getRecentByMember(int $memberId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.*
             FROM {$this->table} s
             JOIN hn_schedule_members sm ON sm.schedule_id = s.id
             WHERE sm.member_id = :mid
             ORDER BY s.schedule_date DESC, s.time_text DESC
             LIMIT :lim"
        );
        $stmt->bindValue('mid', $memberId, \PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * メンバー名検出 (NewsModel と同じロジック)
     */
    public function detectMembers(string $title, array $nameMap): array
    {
        $normalizedTitle = str_replace([' ', '　'], '', $title);
        $ids = [];
        foreach ($nameMap as $name => $id) {
            if (mb_strpos($normalizedTitle, $name) !== false) {
                $ids[] = $id;
            }
        }
        return array_unique($ids);
    }
}
