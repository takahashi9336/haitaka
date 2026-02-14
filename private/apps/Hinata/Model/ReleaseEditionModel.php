<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * リリース版別情報モデル（hn_release_editions）
 * 1行＝1リリースの1版。ジャケット画像はその1要素。
 */
class ReleaseEditionModel extends BaseModel {
    protected string $table = 'hn_release_editions';
    protected array $fields = [
        'id', 'release_id', 'edition', 'jacket_image_url', 'sort_order', 'created_at'
    ];

    protected bool $isUserIsolated = false;

    /** 版の種類（メインジャケットは type_a） */
    public const EDITIONS = [
        'type_a'  => '初回限定 TYPE-A',
        'type_b'  => '初回限定 TYPE-B',
        'type_c'  => '初回限定 TYPE-C',
        'type_d'  => '初回限定 TYPE-D',
        'normal'  => '通常版',
    ];

    /**
     * リリースに紐づく版一覧を取得（edition 順）
     */
    public function getByReleaseId(int $releaseId): array {
        $sql = "SELECT * FROM {$this->table} WHERE release_id = :rid ORDER BY FIELD(edition, 'type_a','type_b','type_c','type_d','normal'), sort_order ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['rid' => $releaseId]);
        return $stmt->fetchAll();
    }

    /**
     * 複数リリースIDに対して版一覧をまとめて取得（release_id ごとに配列）
     * @return array [ release_id => [ edition行, ... ], ... ]
     */
    public function getEditionsByReleaseIds(array $releaseIds): array {
        if (empty($releaseIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($releaseIds), '?'));
        $sql = "SELECT * FROM {$this->table} WHERE release_id IN ($placeholders) ORDER BY release_id, FIELD(edition, 'type_a','type_b','type_c','type_d','normal'), sort_order ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($releaseIds));
        $rows = $stmt->fetchAll();
        $byRelease = [];
        foreach ($rows as $row) {
            $rid = (int)$row['release_id'];
            if (!isset($byRelease[$rid])) {
                $byRelease[$rid] = [];
            }
            $byRelease[$rid][] = $row;
        }
        return $byRelease;
    }

    /**
     * リリースのメインジャケットURL（原則 type_a）を取得
     */
    public function getMainJacketUrl(int $releaseId): ?string {
        $sql = "SELECT jacket_image_url FROM {$this->table} WHERE release_id = :rid AND edition = 'type_a' LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['rid' => $releaseId]);
        $row = $stmt->fetch();
        return $row ? ($row['jacket_image_url'] ?? null) : null;
    }

    /**
     * リリースの版情報を一括保存（既存は削除してから挿入）
     */
    public function saveForRelease(int $releaseId, array $editions): void {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE release_id = ?");
        $stmt->execute([$releaseId]);

        if (empty($editions)) {
            return;
        }

        $sql = "INSERT INTO {$this->table} (release_id, edition, jacket_image_url, sort_order) VALUES (:release_id, :edition, :jacket_image_url, :sort_order)";
        $stmt = $this->pdo->prepare($sql);
        $order = 0;
        foreach ($editions as $row) {
            $edition = $row['edition'] ?? null;
            if (!isset(self::EDITIONS[$edition])) {
                continue;
            }
            $stmt->execute([
                'release_id' => $releaseId,
                'edition' => $edition,
                'jacket_image_url' => !empty($row['jacket_image_url']) ? trim($row['jacket_image_url']) : null,
                'sort_order' => (int)($row['sort_order'] ?? $order++),
            ]);
        }
    }
}
