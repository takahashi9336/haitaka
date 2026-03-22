<?php

namespace App\Admin\Model;

use Core\Database;

/**
 * 友達・ユーザーグループの管理者用CRUD
 * 友人視聴共有機能の管理画面で使用
 */
class FriendGroupAdminModel {

    protected \PDO $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
    }

    // ---- 友達（sys_user_friends） ----

    /**
     * 登録済み友達ペア一覧（id_name 付き）
     */
    public function getAllFriendsWithNames(): array {
        $sql = "SELECT f.id, f.user_id, f.friend_user_id, f.created_at,
                       u1.id_name AS user_id_name, u2.id_name AS friend_id_name
                FROM sys_user_friends f
                JOIN sys_users u1 ON f.user_id = u1.id
                JOIN sys_users u2 ON f.friend_user_id = u2.id
                ORDER BY f.created_at DESC";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 友達ペアを登録（user_id < friend_user_id で格納）
     */
    public function addFriend(int $userIdA, int $userIdB, int $createdBy): bool {
        $userId = min($userIdA, $userIdB);
        $friendUserId = max($userIdA, $userIdB);
        if ($userId === $friendUserId) {
            return false;
        }
        $sql = "INSERT IGNORE INTO sys_user_friends (user_id, friend_user_id, created_by) VALUES (:uid, :fid, :by)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['uid' => $userId, 'fid' => $friendUserId, 'by' => $createdBy]) && $stmt->rowCount() > 0;
    }

    /**
     * 友達ペアを削除
     */
    public function deleteFriend(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sys_user_friends WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * 既存の友達ペアかチェック
     */
    public function friendPairExists(int $userIdA, int $userIdB): bool {
        $userId = min($userIdA, $userIdB);
        $friendUserId = max($userIdA, $userIdB);
        $stmt = $this->pdo->prepare("SELECT 1 FROM sys_user_friends WHERE user_id = :uid AND friend_user_id = :fid");
        $stmt->execute(['uid' => $userId, 'fid' => $friendUserId]);
        return (bool)$stmt->fetch();
    }

    // ---- ユーザーグループ（sys_user_groups, sys_user_group_members） ----

    /**
     * グループ一覧（メンバー数付き）
     */
    public function getAllGroupsWithMemberCount(): array {
        $sql = "SELECT g.id, g.name, g.created_at,
                       (SELECT COUNT(*) FROM sys_user_group_members WHERE group_id = g.id) AS member_count
                FROM sys_user_groups g
                ORDER BY g.name ASC";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * グループ1件取得
     */
    public function getGroupById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM sys_user_groups WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * グループのメンバー一覧（id_name 付き）
     */
    public function getGroupMembers(int $groupId): array {
        $sql = "SELECT gm.*, u.id_name
                FROM sys_user_group_members gm
                JOIN sys_users u ON gm.user_id = u.id
                WHERE gm.group_id = :gid
                ORDER BY u.id_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['gid' => $groupId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * グループ作成
     */
    public function createGroup(string $name, int $createdBy): ?int {
        $stmt = $this->pdo->prepare("INSERT INTO sys_user_groups (name, created_by) VALUES (:name, :by)");
        if (!$stmt->execute(['name' => trim($name), 'by' => $createdBy])) {
            return null;
        }
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * グループ名を更新
     */
    public function updateGroupName(int $id, string $name): bool {
        $stmt = $this->pdo->prepare("UPDATE sys_user_groups SET name = :name WHERE id = :id");
        return $stmt->execute(['name' => trim($name), 'id' => $id]);
    }

    /**
     * グループ削除（CASCADEでメンバーも削除）
     */
    public function deleteGroup(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sys_user_groups WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * グループにメンバーを追加
     */
    public function addGroupMember(int $groupId, int $userId): bool {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO sys_user_group_members (group_id, user_id) VALUES (:gid, :uid)");
        return $stmt->execute(['gid' => $groupId, 'uid' => $userId]) && $stmt->rowCount() > 0;
    }

    /**
     * グループからメンバーを削除
     */
    public function removeGroupMember(int $groupId, int $userId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sys_user_group_members WHERE group_id = :gid AND user_id = :uid");
        return $stmt->execute(['gid' => $groupId, 'uid' => $userId]);
    }

    /**
     * グループのメンバーを一括設定（既存を削除してから新規挿入）
     */
    public function setGroupMembers(int $groupId, array $userIds): bool {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sys_user_group_members WHERE group_id = :gid");
            $stmt->execute(['gid' => $groupId]);
            $stmt = $this->pdo->prepare("INSERT INTO sys_user_group_members (group_id, user_id) VALUES (:gid, :uid)");
            foreach (array_unique(array_map('intval', $userIds)) as $uid) {
                if ($uid > 0) {
                    $stmt->execute(['gid' => $groupId, 'uid' => $uid]);
                }
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
