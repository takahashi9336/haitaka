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
        'id', 'song_id', 'member_id', 'role', 'row_number', 'position',
        'is_featured', 'part_description'
    ];

    /**
     * 楽曲参加メンバーは全ユーザー共通データのため、隔離を無効化
     */
    protected bool $isUserIsolated = false;

    /**
     * 楽曲に参加メンバーを一括登録
     * 
     * @param int $songId 楽曲ID
     * @param array $members [['member_id' => 1, 'role' => 'center', 'position' => 1, 'is_featured' => 1], ...]
     */
    public function bulkInsertMembers(int $songId, array $members): bool {
        // 既存のメンバーを削除
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE song_id = ?");
        $stmt->execute([$songId]);

        if (empty($members)) {
            return true;
        }

        // 一括登録
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (song_id, member_id, role, row_number, position, is_featured, part_description)
            VALUES (:song_id, :member_id, :role, :row_number, :position, :is_featured, :part_description)
        ");

        foreach ($members as $member) {
            $stmt->execute([
                'song_id' => $songId,
                'member_id' => $member['member_id'],
                'role' => $member['role'] ?? 'member',
                'row_number' => $member['row_number'] ?? null,
                'position' => $member['position'] ?? null,
                'is_featured' => $member['is_featured'] ?? 0,
                'part_description' => $member['part_description'] ?? null,
            ]);
        }

        return true;
    }

    /**
     * 役割の定数
     */
    public const ROLES = [
        'center' => 'センター',
        'member' => '通常参加',
        'under' => 'アンダー',
        'other' => 'その他',
    ];

    /**
     * フォーメーション列の定数
     */
    public const ROW_NAMES = [
        1 => 'フロント（1列目）',
        2 => '2列目',
        3 => '3列目',
    ];
}
