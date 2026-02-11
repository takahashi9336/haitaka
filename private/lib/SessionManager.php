<?php

namespace Core;

class SessionManager implements \SessionHandlerInterface {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }

    public function read($id): string {
        $stmt = $this->db->prepare("SELECT data FROM sys_sessions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $row['data'] : '';
    }

    public function write($id, $data): bool {
        $userId = $_SESSION['user']['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $sql = "INSERT INTO sys_sessions (id, user_id, data, ip_address, user_agent, last_activity) 
                VALUES (:id, :uid, :data, :ip, :ua, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id), data = VALUES(data), 
                ip_address = VALUES(ip_address), last_activity = CURRENT_TIMESTAMP";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id, 'uid' => $userId, 'data' => $data, 'ip' => $ip, 'ua' => $ua
        ]);
    }

    public function destroy($id): bool {
        $stmt = $this->db->prepare("DELETE FROM sys_sessions WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function gc($max_lifetime): int|false {
        // 30日(2592000秒)を超えたものを削除
        $stmt = $this->db->prepare("DELETE FROM sys_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        return $stmt->execute() ? 1 : false;
    }
}