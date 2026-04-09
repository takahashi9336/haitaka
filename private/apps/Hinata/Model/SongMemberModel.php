<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * 楽曲参加メンバー管理モデル
 * 物理パス: haitaka/private/apps/Hinata/Model/SongMemberModel.php
 */
class SongMemberModel extends BaseModel {
    protected string $table = 'hn_song_members';
    protected array $fields = [
        'id', 'song_id', 'member_id', 'is_center', 'row_number', 'position',
        'part_description', 'updated_at', 'update_user'
    ];

    /**
     * 楽曲参加メンバーは全ユーザー共通データのため、隔離を無効化
     */
    protected bool $isUserIsolated = false;

    /**
     * 楽曲の参加メンバー一覧をメンバー名付きで取得（編集画面用）
     */
    public function getBySongIdWithNames(int $songId): array {
        $sql = "SELECT sm.id, sm.song_id, sm.member_id, sm.is_center, sm.`row_number`, sm.`position`, sm.part_description,
                       m.name, m.image_url
                FROM {$this->table} sm
                JOIN hn_members m ON m.id = sm.member_id
                WHERE sm.song_id = :sid
                ORDER BY sm.`row_number` ASC, sm.`position` ASC, sm.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['sid' => $songId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 楽曲に参加メンバーを一括登録
     *
     * @param int $songId 楽曲ID
     * @param array $members [['member_id' => 1, 'is_center' => 1, 'row_number' => 1, 'position' => 2], ...]
     *   position: 列内で左端=1、右にカウントアップ（ダブルセンター時は 2,3 がセンター）
     */
    public function bulkInsertMembers(int $songId, array $members): int {
        $updateUser = $_SESSION['user']['id_name'] ?? '';

        $this->pdo->beginTransaction();
        try {
            // 既存のメンバーを削除
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE song_id = ?");
            $stmt->execute([$songId]);

            if (empty($members)) {
                $this->pdo->commit();
                return 0;
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->table} (song_id, member_id, is_center, `row_number`, `position`, part_description, update_user)
                VALUES (:song_id, :member_id, :is_center, :row_number, :position, :part_description, :update_user)
            ");

            $inserted = 0;
            foreach ($members as $member) {
                $rowNumber = $member['row_number'] ?? null;
                if ($rowNumber !== null) {
                    $rowNumber = (int)$rowNumber;
                    if ($rowNumber < 1 || $rowNumber > 5) {
                        $rowNumber = null;
                    }
                }

                $position = $member['position'] ?? null;
                if ($position !== null) {
                    $position = (int)$position;
                    if ($position < 1) {
                        $position = null;
                    }
                }

                $stmt->execute([
                    'song_id' => $songId,
                    'member_id' => (int)($member['member_id'] ?? 0),
                    'is_center' => !empty($member['is_center']) ? 1 : 0,
                    'row_number' => $rowNumber,
                    'position' => $position,
                    'part_description' => $member['part_description'] ?? null,
                    'update_user' => $updateUser,
                ]);
                $inserted++;
            }

            $this->pdo->commit();
            return $inserted;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * フォーメーション列の定数
     */
    public const ROW_NAMES = [
        1 => 'フロント（1列目）',
        2 => '2列目',
        3 => '3列目',
        4 => '4列目',
        5 => '5列目（奥）',
    ];
}
