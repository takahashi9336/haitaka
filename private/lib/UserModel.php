<?php

namespace Core;

/**
 * ユーザー管理用モデル（apps 横断で利用）
 * 物理パス: haitaka/private/lib/UserModel.php
 */
class UserModel extends BaseModel {
    protected string $table = 'sys_users';
    protected array $fields = ['id', 'id_name', 'password', 'role', 'created_at', 'updated_at'];

    /**
     * 全ユーザー取得
     */
    public function getAllUsers(): array {
        $sql = "SELECT id, id_name, role, created_at FROM {$this->table} ORDER BY id ASC";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * パスワード更新
     */
    public function updatePassword(int $userId, string $newHash): bool {
        $sql = "UPDATE {$this->table} SET password = :pass, updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['pass' => $newHash, 'id' => $userId]);
    }

    /**
     * 数値IDでユーザー取得
     */
    public function findById(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * ログインID（id_name）でユーザー取得
     */
    public function findByIdName(string $idName): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE id_name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $idName]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * 新規ユーザー作成
     */
    public function createUser(string $idName, string $passwordHash, string $role): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE id_name = :name");
        $stmt->execute(['name' => $idName]);
        if ($stmt->fetch()) {
            return false;
        }

        $sql = "INSERT INTO {$this->table} (id_name, password, role, created_at, updated_at) 
                VALUES (:name, :pass, :role, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'name' => $idName,
            'pass' => $passwordHash,
            'role' => $role
        ]);
    }
}
