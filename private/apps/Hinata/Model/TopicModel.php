<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * ポータルトピックモデル（ひな誕祭・ひなたフェス等）
 */
class TopicModel extends BaseModel {
    protected string $table = 'hn_topics';
    protected array $fields = [
        'id', 'title', 'summary', 'url', 'image_url', 'topic_type',
        'start_date', 'end_date', 'sort_order', 'is_active',
        'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    public const TOPIC_TYPES = [
        'big_event' => 'ビッグイベント',
        'goods'     => 'グッズ',
        'news'      => 'ニュース',
        'other'     => 'その他',
    ];

    /**
     * 表示中のトピック一覧（start_date〜end_date 内、is_active=1）
     */
    public function getActiveTopics(): array {
        $sql = "SELECT * FROM {$this->table}
                WHERE is_active = 1
                  AND (start_date IS NULL OR start_date <= CURDATE())
                  AND (end_date IS NULL OR end_date >= CURDATE())
                ORDER BY sort_order ASC, id ASC";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 管理用：全件取得
     */
    public function getAll(): array {
        $sql = "SELECT * FROM {$this->table} ORDER BY sort_order ASC, id ASC";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
