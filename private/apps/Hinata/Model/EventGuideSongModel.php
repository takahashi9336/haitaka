<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * 初参戦ライブガイド用 イベント候補曲モデル
 * hn_event_guide_songs
 */
class EventGuideSongModel extends BaseModel {
    protected string $table = 'hn_event_guide_songs';
    protected array $fields = [
        'id', 'event_id', 'song_id', 'likelihood', 'sort_order', 'created_at'
    ];
    protected bool $isUserIsolated = false;

    /** 出る確度の表示ラベル */
    public const LIKELIHOOD_LABELS = [
        'certain' => 'ほぼ確実に出る',
        'high'    => '高確率で出る',
        'possible'=> '出る可能性がある',
    ];

    /**
     * イベントの候補曲一覧を確度別で取得
     * @return array ['certain' => [...], 'high' => [...], 'possible' => [...]]
     */
    public function getByEventIdGroupedByLikelihood(int $eventId): array {
        $sql = "SELECT egs.*, s.id as song_id, s.title as song_title, s.track_type,
                s.apple_music_url, s.spotify_url,
                r.title as release_title, r.release_number
                FROM {$this->table} egs
                JOIN hn_songs s ON s.id = egs.song_id
                JOIN hn_releases r ON r.id = s.release_id
                WHERE egs.event_id = :eid
                ORDER BY 
                    FIELD(egs.likelihood, 'certain', 'high', 'possible'),
                    egs.sort_order ASC,
                    s.track_number ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['eid' => $eventId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $grouped = ['certain' => [], 'high' => [], 'possible' => []];
        foreach ($rows as $row) {
            $lik = $row['likelihood'] ?? 'possible';
            if (!isset($grouped[$lik])) {
                $grouped[$lik] = [];
            }
            $grouped[$lik][] = $row;
        }
        return $grouped;
    }

    /**
     * イベントの候補曲を一括保存（delete-insert）
     * @param array $items [['song_id' => int, 'likelihood' => string], ...]
     */
    public function saveForEvent(int $eventId, array $items): void {
        $this->pdo->prepare("DELETE FROM {$this->table} WHERE event_id = ?")->execute([$eventId]);
        if (empty($items)) {
            return;
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (event_id, song_id, likelihood, sort_order) VALUES (?, ?, ?, ?)"
        );
        foreach ($items as $i => $item) {
            $likelihood = $item['likelihood'] ?? 'possible';
            if (!in_array($likelihood, ['certain', 'high', 'possible'], true)) {
                $likelihood = 'possible';
            }
            $stmt->execute([
                $eventId,
                (int)$item['song_id'],
                $likelihood,
                (int)($item['sort_order'] ?? $i + 1),
            ]);
        }
    }
}
