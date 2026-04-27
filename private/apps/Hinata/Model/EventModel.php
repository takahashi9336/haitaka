<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * 日向坂イベント管理モデル
 * 物理パス: haitaka/private/apps/Hinata/Model/EventModel.php
 */
class EventModel extends BaseModel {
    protected string $table = 'hn_events';
    protected array $fields = [
        'id', 'event_name', 'event_date', 'category', 'mg_rounds', 'event_place', 'event_info', 'event_url',
        'event_hashtag', 'collaboration_urls',
        'updated_at', 'update_user'
    ];

    /**
     * イベントは全ユーザー共通データのため、隔離を無効化する
     */
    protected bool $isUserIsolated = false;

    public function getEventsForCalendar(string $start, string $end): array {
        $sql = "SELECT e.*, s.status as my_status, s.seat_info as seat_info, s.impression as impression,
                       ma.media_key as video_key
                FROM {$this->table} e
                LEFT JOIN hn_user_events_status s ON e.id = s.event_id AND s.user_id = :uid
                LEFT JOIN hn_event_movies em ON e.id = em.event_id
                LEFT JOIN com_media_assets ma ON em.movie_id = ma.id AND ma.platform = 'youtube'
                WHERE e.event_date BETWEEN :start AND :end
                ORDER BY e.event_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $_SESSION['user']['id'] ?? 0, 'start' => $start, 'end' => $end]);
        return $stmt->fetchAll();
    }

    public function getNextEvent(): ?array {
        $sql = "SELECT *, DATEDIFF(event_date, NOW()) as days_left 
                FROM {$this->table} 
                WHERE event_date >= CURDATE() 
                ORDER BY event_date ASC LIMIT 1";
        return $this->pdo->query($sql)->fetch() ?: null;
    }

    /**
     * 次のミーグリ/リアミ（カテゴリ 2 or 3）
     */
    public function getNextMgEvent(): ?array {
        $sql = "SELECT *, DATEDIFF(event_date, NOW()) as days_left
                FROM {$this->table}
                WHERE event_date >= CURDATE()
                  AND category IN (2, 3)
                ORDER BY event_date ASC LIMIT 1";
        return $this->pdo->query($sql)->fetch() ?: null;
    }
    
    /**
     * 指定日付のMG/RMGイベントを検索（カテゴリ 2 or 3）
     */
    public function findMgEventsByDate(string $date): array {
        $sql = "SELECT id, event_name, event_date, category, mg_rounds
                FROM {$this->table}
                WHERE event_date = :d AND category IN (2, 3)
                ORDER BY id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['d' => $date]);
        return $stmt->fetchAll();
    }

    /**
     * MG/RMGイベント一覧（過去1年〜未来6ヶ月、インポートマッチング用）
     */
    public function getMgEventsForMatching(): array {
        $sql = "SELECT id, event_name, event_date, category, mg_rounds
                FROM {$this->table}
                WHERE category IN (2, 3)
                  AND event_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
                  AND event_date <= DATE_ADD(NOW(), INTERVAL 6 MONTH)
                ORDER BY event_date ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * MG/RMGイベント全件（レポ作成画面用、日付制限なし）
     */
    public function getAllMgEvents(): array {
        $sql = "SELECT id, event_name, event_date, category, mg_rounds
                FROM {$this->table}
                WHERE category IN (2, 3)
                ORDER BY event_date ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getAllUpcomingEvents(): array {
        // 過去3ヶ月から未来1年のイベントを取得
        $sql = "SELECT * FROM {$this->table} 
                WHERE event_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                  AND event_date <= DATE_ADD(NOW(), INTERVAL 1 YEAR)
                ORDER BY event_date ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * 初参戦ガイド用：直近のライブイベント一覧（category=1）
     * 過去1ヶ月〜未来1年
     */
    public function getUpcomingLiveEventsForGuide(): array {
        $sql = "SELECT id, event_name, event_date, event_place, event_url, event_hashtag, collaboration_urls
                FROM {$this->table}
                WHERE category = 1
                  AND event_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                  AND event_date <= DATE_ADD(NOW(), INTERVAL 1 YEAR)
                ORDER BY event_date ASC";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function saveUserStatus(int $eventId, int $status): void {
        $userId = $_SESSION['user']['id'] ?? 0;
        $sql = "INSERT INTO hn_user_events_status (user_id, event_id, status)
                VALUES (:uid, :eid, :st)
                ON DUPLICATE KEY UPDATE status = VALUES(status)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'eid' => $eventId, 'st' => $status]);
    }

    public function saveUserSeatImpression(int $eventId, ?string $seatInfo, ?string $impression): void {
        $userId = $_SESSION['user']['id'] ?? 0;
        $sql = "INSERT INTO hn_user_events_status (user_id, event_id, status, seat_info, impression)
                VALUES (:uid, :eid, 1, :seat_info, :impression)
                ON DUPLICATE KEY UPDATE seat_info = VALUES(seat_info), impression = VALUES(impression)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'uid' => $userId,
            'eid' => $eventId,
            'seat_info' => $seatInfo ?: null,
            'impression' => $impression ?: null,
        ]);
    }

    public function deleteUserStatus(int $eventId): void {
        $userId = $_SESSION['user']['id'] ?? 0;
        $this->pdo->prepare("DELETE FROM hn_user_events_status WHERE user_id = ? AND event_id = ?")
                  ->execute([$userId, $eventId]);
    }
}