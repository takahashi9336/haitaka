<?php

namespace App\SenseLab\Model;

use Core\Database;

class SenseQuickEntryModel
{
    public function create(array $data): int
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO sl_sense_quick_entries (user_id, app_key, page_title, source_url, category_hint, note, image_path, reason_1, reason_2, reason_3)
             VALUES (:user_id, :app_key, :page_title, :source_url, :category_hint, :note, :image_path, :reason_1, :reason_2, :reason_3)'
        );
        $stmt->execute([
            'user_id' => $data['user_id'],
            'app_key' => $data['app_key'],
            'page_title' => $data['page_title'],
            'source_url' => $data['source_url'],
            'category_hint' => $data['category_hint'],
            'note' => $data['note'],
            'image_path' => $data['image_path'] ?? null,
            'reason_1' => $data['reason_1'] ?? null,
            'reason_2' => $data['reason_2'] ?? null,
            'reason_3' => $data['reason_3'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    public function getListByUser(int $userId): array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT * FROM sl_sense_quick_entries WHERE user_id = :user_id ORDER BY created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT * FROM sl_sense_quick_entries WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateByIdAndUser(int $id, int $userId, array $fields): bool
    {
        $allowed = [
            'note' => true,
            'category_hint' => true,
            'image_path' => true,
            'reason_1' => true,
            'reason_2' => true,
            'reason_3' => true,
            'linked_entry_id' => true,
        ];

        $setParts = [];
        $params = [
            'id' => $id,
            'user_id' => $userId,
        ];

        foreach ($fields as $k => $v) {
            if (!isset($allowed[$k])) {
                continue;
            }
            $setParts[] = "{$k} = :{$k}";
            $params[$k] = $v;
        }

        if (!$setParts) {
            return false;
        }

        $db = Database::connect();
        $sql = 'UPDATE sl_sense_quick_entries SET ' . implode(', ', $setParts) . ' WHERE id = :id AND user_id = :user_id';
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
}

