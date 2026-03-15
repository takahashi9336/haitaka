<?php

namespace App\Anime\Model;

use Core\BaseModel;

/**
 * ユーザー作品ステータスモデル（an_user_works）
 * 物理パス: haitaka/private/apps/Anime/Model/UserWorkModel.php
 */
class UserWorkModel extends BaseModel {

    protected string $table = 'an_user_works';
    protected bool $isUserIsolated = false;

    protected array $fields = [
        'id', 'user_id', 'work_id', 'annict_work_id', 'status',
        'rating', 'memo', 'watched_date', 'watched_episodes',
        'created_at', 'updated_at',
    ];

    public function findByUserAndAnnictWork(int $userId, int $annictWorkId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND annict_work_id = :awid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'awid' => $annictWorkId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * an_user_works に upsert
     * @param int $workId an_works.id
     * @param int $annictWorkId Annict 作品ID
     */
    public function upsert(int $userId, int $workId, int $annictWorkId, string $status): array {
        $existing = $this->findByUserAndAnnictWork($userId, $annictWorkId);
        $data = [
            'user_id' => $userId,
            'work_id' => $workId,
            'annict_work_id' => $annictWorkId,
            'status' => $status,
        ];
        if ($existing) {
            $sql = "UPDATE {$this->table} SET work_id = :work_id, status = :status, updated_at = NOW() WHERE user_id = :user_id AND annict_work_id = :annict_work_id";
            $this->pdo->prepare($sql)->execute($data);
        } else {
            $this->pdo->prepare(
                "INSERT INTO {$this->table} (user_id, work_id, annict_work_id, status) VALUES (:user_id, :work_id, :annict_work_id, :status)"
            )->execute($data);
        }
        return $this->findByUserAndAnnictWork($userId, $annictWorkId);
    }

    /**
     * ユーザー・ステータスで一覧取得（an_works と JOIN、View 互換形式で返す）
     * @return array 各要素は View が期待する work 形式（id=annict_id, images.recommended_url, status.kind 等）
     */
    public function getByUserAndStatus(int $userId, string $status): array {
        $sql = "SELECT w.id AS work_id, w.annict_id, w.title, w.title_kana, w.media, w.season_name,
                w.released_on, w.episodes_count, w.image_url, w.official_site_url,
                uw.status
                FROM {$this->table} uw
                INNER JOIN an_works w ON uw.work_id = w.id
                WHERE uw.user_id = :uid AND uw.status = :status
                ORDER BY uw.updated_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'status' => $status]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'toViewFormat'], $rows);
    }

    /**
     * ユーザーの全ステータス作品を取得（ダッシュボード用）
     */
    public function getAllByUser(int $userId): array {
        $sql = "SELECT w.id AS work_id, w.annict_id, w.title, w.title_kana, w.media, w.season_name,
                w.released_on, w.episodes_count, w.image_url, w.official_site_url,
                uw.status
                FROM {$this->table} uw
                INNER JOIN an_works w ON uw.work_id = w.id
                WHERE uw.user_id = :uid
                ORDER BY uw.updated_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'toViewFormat'], $rows);
    }

    /**
     * ユーザーのステータス別件数を集計（エンタメダッシュボードなどで利用）
     */
    public function getStatsByUser(int $userId): array {
        $sql = "SELECT status, COUNT(*) AS cnt FROM {$this->table} WHERE user_id = :uid GROUP BY status";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stats = [
            'wanna_watch' => 0,
            'watching' => 0,
            'watched' => 0,
        ];
        foreach ($rows as $r) {
            if (isset($stats[$r['status']])) {
                $stats[$r['status']] = (int)$r['cnt'];
            }
        }
        return $stats;
    }

    private function toViewFormat(array $row): array {
        $seasonName = $row['season_name'] ?? '';
        $seasonNameText = $this->seasonToText($seasonName);
        return [
            'id' => (int)$row['annict_id'],
            'title' => $row['title'] ?? '',
            'title_kana' => $row['title_kana'] ?? null,
            'media' => $row['media'] ?? null,
            'media_text' => $this->mediaToText($row['media'] ?? ''),
            'season_name' => $seasonName,
            'season_name_text' => $seasonNameText,
            'released_on' => $row['released_on'] ?? null,
            'episodes_count' => isset($row['episodes_count']) ? (int)$row['episodes_count'] : null,
            'images' => [
                'recommended_url' => $row['image_url'] ?? null,
                'facebook' => ['og_image_url' => $row['image_url'] ?? null],
            ],
            'official_site_url' => $row['official_site_url'] ?? null,
            'status' => ['kind' => $row['status'] ?? 'no_select'],
        ];
    }

    private function seasonToText(string $season): string {
        if (empty($season) || !preg_match('/^(\d{4})-(winter|spring|summer|autumn)$/', $season, $m)) {
            return $season;
        }
        $labels = ['winter' => '冬', 'spring' => '春', 'summer' => '夏', 'autumn' => '秋'];
        return $m[1] . '年' . ($labels[$m[2]] ?? $m[2]);
    }

    private function mediaToText(string $media): string {
        $map = ['tv' => 'TV', 'ova' => 'OVA', 'movie' => '映画', 'web' => 'Web', 'other' => 'その他'];
        return $map[$media] ?? $media ?: 'その他';
    }

    /**
     * an_works 行 + an_user_works 行 を詳細画面用の view 形式に変換
     */
    public function formatWorkForDetail(array $workRow, ?array $userWorkRow): array {
        $seasonName = $workRow['season_name'] ?? '';
        $status = $userWorkRow['status'] ?? 'no_select';
        return [
            'id' => (int)($workRow['annict_id'] ?? 0),
            'title' => $workRow['title'] ?? '',
            'title_kana' => $workRow['title_kana'] ?? null,
            'media' => $workRow['media'] ?? null,
            'media_text' => $this->mediaToText($workRow['media'] ?? ''),
            'season_name' => $seasonName,
            'season_name_text' => $this->seasonToText($seasonName),
            'released_on' => $workRow['released_on'] ?? null,
            'episodes_count' => isset($workRow['episodes_count']) ? (int)$workRow['episodes_count'] : null,
            'images' => [
                'recommended_url' => $workRow['image_url'] ?? null,
                'facebook' => ['og_image_url' => $workRow['image_url'] ?? null],
            ],
            'official_site_url' => $workRow['official_site_url'] ?? null,
            'status' => ['kind' => $status],
        ];
    }
}
