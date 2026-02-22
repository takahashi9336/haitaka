<?php

namespace App\Hinata\Model;

use Core\BaseModel;
use Core\Database;

/**
 * セットリスト・参戦記録モデル
 */
class SetlistModel extends BaseModel {
    protected string $table = 'hn_setlists';
    protected array $fields = [
        'id', 'event_id', 'song_id', 'sort_order', 'encore', 'memo',
        'created_at', 'updated_at', 'update_user'
    ];
    protected bool $isUserIsolated = false;

    public function getByEventId(int $eventId): array {
        $sql = "SELECT sl.*, s.title as song_title, s.track_type,
                       r.title as release_title, r.release_type
                FROM {$this->table} sl
                JOIN hn_songs s ON sl.song_id = s.id
                JOIN hn_releases r ON s.release_id = r.id
                WHERE sl.event_id = :eid
                ORDER BY sl.sort_order ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['eid' => $eventId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * セットリストを一括保存（delete-insert）
     * @param array $items [{ song_id, sort_order, encore, memo? }]
     */
    public function saveForEvent(int $eventId, array $items): void {
        $this->pdo->prepare("DELETE FROM {$this->table} WHERE event_id = ?")->execute([$eventId]);
        if (empty($items)) return;
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (event_id, song_id, sort_order, encore, memo, update_user) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $user = $_SESSION['user']['id_name'] ?? '';
        foreach ($items as $item) {
            $stmt->execute([
                $eventId,
                (int)$item['song_id'],
                (int)$item['sort_order'],
                !empty($item['encore']) ? 1 : 0,
                $item['memo'] ?? null,
                $user,
            ]);
        }
    }

    /**
     * 参戦トグル
     */
    public function toggleAttendance(int $eventId): bool {
        $userId = $_SESSION['user']['id'] ?? 0;
        $pdo = Database::connect();
        $check = $pdo->prepare("SELECT id FROM hn_event_attendance WHERE user_id = ? AND event_id = ?");
        $check->execute([$userId, $eventId]);
        if ($check->fetch()) {
            $pdo->prepare("DELETE FROM hn_event_attendance WHERE user_id = ? AND event_id = ?")->execute([$userId, $eventId]);
            return false;
        }
        $pdo->prepare("INSERT INTO hn_event_attendance (user_id, event_id) VALUES (?, ?)")->execute([$userId, $eventId]);
        return true;
    }

    public function isAttended(int $eventId): bool {
        $userId = $_SESSION['user']['id'] ?? 0;
        $stmt = $this->pdo->prepare("SELECT 1 FROM hn_event_attendance WHERE user_id = ? AND event_id = ?");
        $stmt->execute([$userId, $eventId]);
        return (bool)$stmt->fetch();
    }

    /**
     * ユーザーの参戦イベントID一覧
     */
    public function getAttendedEventIds(): array {
        $userId = $_SESSION['user']['id'] ?? 0;
        $stmt = $this->pdo->prepare("SELECT event_id FROM hn_event_attendance WHERE user_id = ?");
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'event_id');
    }

    /**
     * 楽曲別のライブ披露回数（ユーザーが参戦したライブのみ）
     */
    public function getSongPlayCounts(): array {
        $userId = $_SESSION['user']['id'] ?? 0;
        $sql = "SELECT s.id as song_id, s.title as song_title, COUNT(*) as play_count
                FROM hn_setlists sl
                JOIN hn_songs s ON sl.song_id = s.id
                JOIN hn_event_attendance ea ON ea.event_id = sl.event_id AND ea.user_id = :uid
                GROUP BY s.id, s.title
                ORDER BY play_count DESC, s.title ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * セットリストが登録されているイベントID一覧
     */
    public function getEventsWithSetlist(): array {
        $sql = "SELECT DISTINCT event_id FROM {$this->table}";
        return array_column($this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC), 'event_id');
    }
}
