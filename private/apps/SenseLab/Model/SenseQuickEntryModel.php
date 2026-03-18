<?php

namespace App\SenseLab\Model;

use Core\Database;

class SenseQuickEntryModel
{
    public function create(array $data): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO sl_sense_quick_entries (user_id, app_key, page_title, source_url, category_hint, note)
             VALUES (:user_id, :app_key, :page_title, :source_url, :category_hint, :note)'
        );
        $stmt->execute([
            'user_id' => $data['user_id'],
            'app_key' => $data['app_key'],
            'page_title' => $data['page_title'],
            'source_url' => $data['source_url'],
            'category_hint' => $data['category_hint'],
            'note' => $data['note'],
        ]);
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
}

