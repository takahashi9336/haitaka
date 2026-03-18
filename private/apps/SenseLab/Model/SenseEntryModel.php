<?php

namespace App\SenseLab\Model;

use Core\Database;

class SenseEntryModel
{
    public function getList(int $userId, ?string $category = null): array
    {
        $db = Database::connect();
        if ($category) {
            $stmt = $db->prepare(
                'SELECT * FROM sl_sense_entries WHERE user_id = :user_id AND category = :category ORDER BY created_at DESC'
            );
            $stmt->execute([
                'user_id' => $userId,
                'category' => $category,
            ]);
        } else {
            $stmt = $db->prepare(
                'SELECT * FROM sl_sense_entries WHERE user_id = :user_id ORDER BY created_at DESC'
            );
            $stmt->execute([
                'user_id' => $userId,
            ]);
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getStats(int $userId): array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT category, COUNT(*) AS cnt FROM sl_sense_entries WHERE user_id = :user_id GROUP BY category ORDER BY cnt DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stats = [
            'total' => 0,
            'by_category' => [],
        ];
        foreach ($rows as $row) {
            $stats['total'] += (int) $row['cnt'];
            $stats['by_category'][] = [
                'category' => $row['category'],
                'count' => (int) $row['cnt'],
            ];
        }
        return $stats;
    }

    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT * FROM sl_sense_entries WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO sl_sense_entries (user_id, title, image_path, category, reason_1, reason_2, reason_3)
             VALUES (:user_id, :title, :image_path, :category, :reason_1, :reason_2, :reason_3)'
        );
        $stmt->execute([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'image_path' => $data['image_path'],
            'category' => $data['category'],
            'reason_1' => $data['reason_1'],
            'reason_2' => $data['reason_2'],
            'reason_3' => $data['reason_3'],
        ]);
    }

    public function update(int $id, int $userId, array $data): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'UPDATE sl_sense_entries
             SET title = :title,
                 image_path = :image_path,
                 category = :category,
                 reason_1 = :reason_1,
                 reason_2 = :reason_2,
                 reason_3 = :reason_3
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
            'title' => $data['title'],
            'image_path' => $data['image_path'],
            'category' => $data['category'],
            'reason_1' => $data['reason_1'],
            'reason_2' => $data['reason_2'],
            'reason_3' => $data['reason_3'],
        ]);
    }

    public function delete(int $id, int $userId): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'DELETE FROM sl_sense_entries WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
    }
}

