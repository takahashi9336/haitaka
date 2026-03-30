<?php

namespace App\FriendsActivity\Model;

use Core\Database;

/**
 * 友達・ユーザーグループから「閲覧可能なユーザーID」を取得するモデル
 * 友人視聴共有機能で、誰の視聴履歴を参照できるかを判定する
 */
class FriendGroupModel {

    protected \PDO $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
    }

    /**
     * 指定ユーザーが閲覧可能な他ユーザーのID一覧を取得
     * 友達（sys_user_friends）と同一グループ（sys_user_group_members）のユーザーをマージ
     *
     * @param int $currentUserId ログインユーザーのID
     * @return int[] 閲覧可能な user_id の配列（自分は含まない）
     */
    public function getViewableUserIds(int $currentUserId): array {
        $friendIds = $this->getFriendUserIds($currentUserId);
        $groupMemberIds = $this->getGroupMemberUserIds($currentUserId);
        $merged = array_unique(array_merge($friendIds, $groupMemberIds));
        // 自分を除外
        return array_values(array_filter($merged, fn($id) => (int)$id !== $currentUserId));
    }

    /**
     * 友達（sys_user_friends）で紐づく他ユーザーIDを取得
     */
    private function getFriendUserIds(int $userId): array {
        $sql = "SELECT
                    CASE WHEN user_id = :uid THEN friend_user_id ELSE user_id END AS other_id
                FROM sys_user_friends
                WHERE user_id = :uid1 OR friend_user_id = :uid2";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'uid1' => $userId, 'uid2' => $userId]);
        return array_map('intval', array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'other_id'));
    }

    /**
     * 同一グループに所属する他ユーザーIDを取得
     */
    private function getGroupMemberUserIds(int $userId): array {
        $sql = "SELECT DISTINCT gm.user_id
                FROM sys_user_group_members gm
                INNER JOIN sys_user_group_members my ON my.group_id = gm.group_id AND my.user_id = :uid
                WHERE gm.user_id != :uid2";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'uid2' => $userId]);
        return array_map('intval', array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'user_id'));
    }
}

