<?php

namespace App\Anime\Model;

use Core\BaseModel;

/**
 * Annict 作品キャッシュモデル
 * 物理パス: haitaka/private/apps/Anime/Model/WorkModel.php
 */
class WorkModel extends BaseModel {

    protected string $table = 'an_works';
    protected bool $isUserIsolated = false;

    protected array $fields = [
        'id', 'annict_id', 'title', 'title_kana', 'media', 'season_name',
        'released_on', 'episodes_count', 'image_url', 'official_site_url',
        'created_at', 'updated_at',
    ];

    public function findByAnnictId(int $annictId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE annict_id = :aid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['aid' => $annictId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Annict API の work オブジェクトからキャッシュを作成 or 更新
     */
    public function upsertFromAnnict(array $work): array {
        $annictId = (int)($work['id'] ?? 0);
        if ($annictId <= 0) {
            return [];
        }

        $imageUrl = null;
        if (!empty($work['images']['recommended_url'])) {
            $imageUrl = $work['images']['recommended_url'];
        } elseif (!empty($work['images']['facebook']['og_image_url'])) {
            $imageUrl = $work['images']['facebook']['og_image_url'];
        }

        $data = [
            'annict_id' => $annictId,
            'title' => $work['title'] ?? '',
            'title_kana' => $work['title_kana'] ?? null,
            'media' => $work['media'] ?? null,
            'season_name' => $work['season_name'] ?? null,
            'released_on' => !empty($work['released_on']) ? $work['released_on'] : null,
            'episodes_count' => isset($work['episodes_count']) ? (int)$work['episodes_count'] : null,
            'image_url' => $imageUrl,
            'official_site_url' => $work['official_site_url'] ?? null,
        ];

        $existing = $this->findByAnnictId($annictId);
        if ($existing) {
            $sets = [];
            foreach (array_keys($data) as $k) {
                if ($k !== 'annict_id') $sets[] = "{$k} = :{$k}";
            }
            $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE annict_id = :annict_id";
            $this->pdo->prepare($sql)->execute($data);
            return $this->findByAnnictId($annictId);
        }

        $cols = implode(', ', array_keys($data));
        $phs = ':' . implode(', :', array_keys($data));
        $this->pdo->prepare("INSERT INTO {$this->table} ({$cols}) VALUES ({$phs})")->execute($data);
        return $this->findByAnnictId($annictId);
    }
}
