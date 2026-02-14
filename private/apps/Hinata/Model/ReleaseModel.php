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
        'release_date', 'description', 'created_at'
    ];

    /**
     * リリースは全ユーザー共通データのため、隔離を無効化
     */
    protected bool $isUserIsolated = false;

    /**
     * リリース一覧をカテゴリ単位・リリース順（発売日昇順）で取得
     */
    public function getAllReleases(): array {
        $sql = "SELECT " . implode(', ', $this->fields) . " 
                FROM {$this->table} 
                ORDER BY 
                    CASE release_type 
                        WHEN 'single' THEN 1 
                        WHEN 'album' THEN 2 
                        WHEN 'digital' THEN 3 
                        WHEN 'ep' THEN 4 
                        WHEN 'best' THEN 5 
                        ELSE 6 
                    END,
                    release_date IS NULL,
                    release_date ASC,
                    id ASC";
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

        // 収録曲を取得（センターは hn_song_members.is_center で管理）
        $sql = "SELECT s.*,
                       hmeta.category,
                       ma.media_key, ma.thumbnail_url
                FROM hn_songs s
                LEFT JOIN hn_media_metadata hmeta ON s.media_meta_id = hmeta.id
                LEFT JOIN com_media_assets ma ON hmeta.asset_id = ma.id
                WHERE s.release_id = :rid
                ORDER BY s.track_number ASC, s.id ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['rid' => $releaseId]);
        $release['songs'] = $stmt->fetchAll();

        // 版別情報（editions）を取得
        $editionModel = new ReleaseEditionModel();
        $release['editions'] = $editionModel->getByReleaseId($releaseId);

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
