<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * リリース管理モデル（シングル・アルバム）
 * 物理パス: haitaka/private/apps/Hinata/Model/ReleaseModel.php
 */
class ReleaseModel extends BaseModel {
    protected string $table = 'hn_releases';
    protected array $fields = [
        'id', 'release_type', 'release_number', 'title', 'title_kana',
        'release_date', 'jacket_image_url', 'description', 'created_at'
    ];

    /**
     * リリースは全ユーザー共通データのため、隔離を無効化
     */
    protected bool $isUserIsolated = false;

    /**
     * リリース一覧を発売日順で取得
     */
    public function getAllReleases(): array {
        $sql = "SELECT " . implode(', ', $this->fields) . " 
                FROM {$this->table} 
                ORDER BY release_date DESC, id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * リリース詳細と収録曲を取得
     */
    public function getReleaseWithSongs(int $releaseId): ?array {
        // リリース情報
        $release = $this->find($releaseId);
        if (!$release) {
            return null;
        }

        // 収録曲を取得
        $sql = "SELECT s.*, 
                       m.name as center_name,
                       hmeta.category,
                       ma.media_key, ma.thumbnail_url
                FROM hn_songs s
                LEFT JOIN hn_members m ON s.center_member_id = m.id
                LEFT JOIN hn_media_metadata hmeta ON s.media_meta_id = hmeta.id
                LEFT JOIN com_media_assets ma ON hmeta.asset_id = ma.id
                WHERE s.release_id = :rid
                ORDER BY s.track_number ASC, s.id ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['rid' => $releaseId]);
        $release['songs'] = $stmt->fetchAll();

        return $release;
    }

    /**
     * リリース種別の定数
     */
    public const RELEASE_TYPES = [
        'single' => 'シングル',
        'album' => 'アルバム',
        'digital' => 'デジタルシングル',
        'ep' => 'EP',
        'best' => 'ベストアルバム',
    ];
}
