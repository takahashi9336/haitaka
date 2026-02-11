<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * ネタデータのCRUDとグループ化を担当
 */
class NetaModel extends BaseModel {
    protected string $table = 'hn_neta';
    protected array $fields = ['id', 'user_id', 'member_id', 'content', 'memo', 'status', 'created_at', 'updated_at'];

    /**
     * メンバーごとにグループ化したネタ一覧を取得
     * 要望5：サイリウム2色の情報を取得
     */
    public function getGroupedNeta(): array {
        $sql = "SELECT n.*, m.name as member_name, c1.color_code as color1, c2.color_code as color2,
                       COALESCE(f.level, 0) as favorite_level
                FROM {$this->table} n
                JOIN hn_members m ON n.member_id = m.id
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                LEFT JOIN hn_favorites f ON f.member_id = m.id AND f.user_id = :uid_fav
                WHERE n.user_id = :uid_neta AND n.status != 'delete'
                ORDER BY favorite_level DESC, m.generation ASC, m.kana ASC, n.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid_fav' => $this->userId, 'uid_neta' => $this->userId]);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $mid = $row['member_id'];
            if (!isset($grouped[$mid])) {
                $grouped[$mid] = [
                    'member_name' => $row['member_name'],
                    'color1' => $row['color1'] ?: '#7cc7e8',
                    'color2' => $row['color2'] ?: $row['color1'] ?: '#7cc7e8',
                    'items' => []
                ];
            }
            $grouped[$mid]['items'][] = $row;
        }
        return $grouped;
    }
}