<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * お知らせ（アナウンス）モデル
 */
class AnnouncementModel extends BaseModel {
    protected string $table = 'hn_announcements';
    protected array $fields = [
        'id', 'title', 'body', 'url', 'image_url', 'announcement_type',
        'published_at', 'expires_at', 'sort_order', 'is_active',
        'created_at', 'updated_at'
    ];
    protected bool $isUserIsolated = false;

    public const TYPES = [
        'goods'                => 'グッズ',
        'application_deadline' => '応募締切',
        'big_event'            => 'ビッグイベント',
        'media'                => 'メディア',
        'release'              => 'リリース',
        'ticket'               => 'チケット',
        'fanclub'              => 'ファンクラブ',
        'meetgreet'            => 'ミート＆グリート',
        'audition'             => 'オーディション',
        'other'                => 'その他',
    ];

    /**
     * ポータル表示用：公開期間内のお知らせ
     */
    public function getActiveAnnouncements(int $limit = 20): array {
        $sql = "SELECT * FROM {$this->table}
                WHERE is_active = 1
                  AND (published_at IS NULL OR published_at <= NOW())
                  AND (expires_at IS NULL OR expires_at >= NOW())
                ORDER BY sort_order ASC, published_at DESC, id DESC
                LIMIT " . (int) $limit;
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 管理用：全件取得
     */
    public function getAll(): array {
        $sql = "SELECT * FROM {$this->table} ORDER BY sort_order ASC, published_at DESC, id DESC";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
