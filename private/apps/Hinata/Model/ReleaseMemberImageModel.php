<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * リリース単位のメンバーアーティスト写真（hn_release_member_images）
 * 楽曲フォーメーション表示で参照
 */
class ReleaseMemberImageModel extends BaseModel {
    protected string $table = 'hn_release_member_images';
    protected array $fields = [
        'id', 'release_id', 'member_id', 'image_url', 'sort_order', 'created_at',
        'updated_at', 'update_user'
    ];

    protected bool $isUserIsolated = false;

    /**
     * リリースに登録されたメンバー写真を member_id => image_url の連想配列で取得
     */
    public function getMapByReleaseId(int $releaseId): array {
        $sql = "SELECT member_id, image_url FROM {$this->table}
                WHERE release_id = :rid ORDER BY sort_order ASC, member_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['rid' => $releaseId]);
        $map = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $map[(int)$row['member_id']] = $row['image_url'];
        }
        return $map;
    }

    /**
     * リリースのアーティスト写真一覧を取得（管理画面・編集用）
     */
    public function getByReleaseId(int $releaseId): array {
        $sql = "SELECT rmi.*, m.name
                FROM {$this->table} rmi
                JOIN hn_members m ON m.id = rmi.member_id
                WHERE rmi.release_id = :rid
                ORDER BY rmi.sort_order ASC, m.kana ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['rid' => $releaseId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 複数リリースIDのアー写をまとめて取得（閲覧一覧用）
     * @return array [ release_id => [ member_id => image_url ] ]
     */
    public function getRowsByReleaseIds(array $releaseIds): array {
        $releaseIds = array_values(array_filter(array_map('intval', $releaseIds), fn($v) => $v > 0));
        if (empty($releaseIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($releaseIds), '?'));
        $sql = "SELECT release_id, member_id, image_url
                FROM {$this->table}
                WHERE release_id IN ($placeholders)
                ORDER BY release_id ASC, sort_order ASC, member_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($releaseIds);
        $out = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $rid = (int)$row['release_id'];
            $mid = (int)$row['member_id'];
            if (!isset($out[$rid])) $out[$rid] = [];
            $out[$rid][$mid] = (string)($row['image_url'] ?? '');
        }
        return $out;
    }

    /**
     * 複数メンバーIDのアー写をまとめて取得（閲覧一覧用）
     * @return array [ member_id => [ release_id => image_url ] ]
     */
    public function getRowsByMemberIds(array $memberIds): array {
        $memberIds = array_values(array_filter(array_map('intval', $memberIds), fn($v) => $v > 0));
        if (empty($memberIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
        $sql = "SELECT member_id, release_id, image_url
                FROM {$this->table}
                WHERE member_id IN ($placeholders)
                ORDER BY member_id ASC, sort_order ASC, release_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($memberIds);
        $out = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $mid = (int)$row['member_id'];
            $rid = (int)$row['release_id'];
            if (!isset($out[$mid])) $out[$mid] = [];
            $out[$mid][$rid] = (string)($row['image_url'] ?? '');
        }
        return $out;
    }

    /**
     * リリースのメンバー写真を一括保存（既存は削除してから挿入）
     * @param array $rows [['member_id' => int, 'image_url' => string], ...]
     */
    public function saveForRelease(int $releaseId, array $rows): void {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE release_id = ?");
        $stmt->execute([$releaseId]);

        if (empty($rows)) {
            return;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (release_id, member_id, image_url, sort_order, update_user)
            VALUES (:release_id, :member_id, :image_url, :sort_order, :update_user)
        ");
        $sortOrder = 0;
        foreach ($rows as $row) {
            $memberId = (int)($row['member_id'] ?? 0);
            $imageUrl = trim((string)($row['image_url'] ?? ''));
            if ($memberId <= 0) {
                continue;
            }
            $stmt->execute([
                'release_id' => $releaseId,
                'member_id' => $memberId,
                'image_url' => $imageUrl !== '' ? $imageUrl : '',
                'sort_order' => $sortOrder++,
                'update_user' => $_SESSION['user']['id_name'] ?? '',
            ]);
        }
    }
}
