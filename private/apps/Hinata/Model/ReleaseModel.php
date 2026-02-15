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
     * リリース一覧を取得（デフォルト：リリース日降順。シングル・アルバムは区別しない）
     */
    public function getAllReleases(): array {
        $sql = "SELECT " . implode(', ', $this->fields) . " 
                FROM {$this->table} 
                ORDER BY release_date IS NULL, release_date DESC, id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * 一覧用：リリース一覧に版別情報・収録曲数を付与して取得（リリース日降順）
     * @return array 各要素に editions[], song_count が付く
     */
    public function getAllReleasesWithSummary(): array {
        $releases = $this->getAllReleases();
        if (empty($releases)) {
            return [];
        }
        $releaseIds = array_column($releases, 'id');
        $editionModel = new ReleaseEditionModel();
        $editionsByRelease = $editionModel->getEditionsByReleaseIds($releaseIds);

        $countSql = "SELECT release_id, COUNT(*) as cnt FROM hn_songs WHERE release_id IN (" . implode(',', array_map('intval', $releaseIds)) . ") GROUP BY release_id";
        $counts = [];
        foreach ($this->pdo->query($countSql)->fetchAll() as $row) {
            $counts[(int)$row['release_id']] = (int)$row['cnt'];
        }

        foreach ($releases as &$r) {
            $rid = (int)$r['id'];
            $r['editions'] = $editionsByRelease[$rid] ?? [];
            $r['song_count'] = $counts[$rid] ?? 0;
        }
        unset($r);
        return $releases;
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
