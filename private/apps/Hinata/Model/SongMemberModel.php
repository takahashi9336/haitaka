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
        'part_description'
    ];

    /**
     * 楽曲参加メンバーは全ユーザー共通データのため、隔離を無効化
     */
    protected bool $isUserIsolated = false;

    /**
     * 楽曲に参加メンバーを一括登録
     *
     * @param int $songId 楽曲ID
     * @param array $members [['member_id' => 1, 'is_center' => 1, 'row_number' => 1, 'position' => 2], ...]
     *   position: 列内で左端=1、右にカウントアップ（ダブルセンター時は 2,3 がセンター）
     */
    public function bulkInsertMembers(int $songId, array $members): bool {
        // 既存のメンバーを削除
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE song_id = ?");
        $stmt->execute([$songId]);

        if (empty($members)) {
            return true;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (song_id, member_id, is_center, row_number, position, part_description)
            VALUES (:song_id, :member_id, :is_center, :row_number, :position, :part_description)
        ");

        foreach ($members as $member) {
            $stmt->execute([
                'song_id' => $songId,
                'member_id' => $member['member_id'],
                'is_center' => !empty($member['is_center']) ? 1 : 0,
                'row_number' => $member['row_number'] ?? null,
                'position' => $member['position'] ?? null,
                'part_description' => $member['part_description'] ?? null,
            ]);
        }

        return true;
    }

    /**
     * フォーメーション列の定数
     */
    public const ROW_NAMES = [
        1 => 'フロント（1列目）',
        2 => '2列目',
        3 => '3列目',
    ];
}
