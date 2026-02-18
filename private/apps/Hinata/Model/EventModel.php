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
        'id', 'event_name', 'event_date', 'category', 'event_place', 'event_info', 'event_url',
        'updated_at', 'update_user'
    ];

    /**
     * イベントは全ユーザー共通データのため、隔離を無効化する
     */
    protected bool $isUserIsolated = false;

    public function getEventsForCalendar(string $start, string $end): array {
        $sql = "SELECT e.*, s.status as my_status,
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
    
    public function getAllUpcomingEvents(): array {
        // 過去3ヶ月から未来1年のイベントを取得
        $sql = "SELECT * FROM {$this->table} 
                WHERE event_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                  AND event_date <= DATE_ADD(NOW(), INTERVAL 1 YEAR)
                ORDER BY event_date ASC";
        return $this->pdo->query($sql)->fetchAll();
    }
}