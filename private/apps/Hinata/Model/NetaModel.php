<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * ネタデータのCRUDとグループ化を担当
 */
class NetaModel extends BaseModel {
    protected string $table = 'hn_neta';
    protected array $fields = ['id', 'user_id', 'member_id', 'content', 'memo', 'neta_type', 'is_favorite', 'status', 'created_at', 'updated_at'];
    protected array $encryptedFields = ['content', 'memo'];

    public function upsertTag(string $name): int {
        $name = trim($name);
        if ($name === '') throw new \InvalidArgumentException('タグが空です');

        $sql = "INSERT INTO hn_tags (user_id, name) VALUES (:uid, :name)
                ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), updated_at = NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'name' => $name]);
        return (int)$this->pdo->lastInsertId();
    }

    public function replaceNetaTags(int $netaId, array $tagNames): void {
        // normalize
        $names = [];
        foreach ($tagNames as $t) {
            $t = trim((string)$t);
            if ($t === '') continue;
            if (str_starts_with($t, '#')) $t = ltrim($t, '#');
            $t = trim($t);
            if ($t === '') continue;
            $names[$t] = true;
        }
        $tagNames = array_keys($names);

        // clear
        $this->pdo->prepare("DELETE FROM hn_neta_tags WHERE neta_id = ?")->execute([$netaId]);
        if (empty($tagNames)) return;

        // upsert tags and link
        $linkStmt = $this->pdo->prepare("INSERT IGNORE INTO hn_neta_tags (neta_id, tag_id) VALUES (?, ?)");
        foreach ($tagNames as $name) {
            $tagId = $this->upsertTag($name);
            $linkStmt->execute([$netaId, $tagId]);
        }
    }

    public function listTagsForUser(int $limit = 50): array {
        $stmt = $this->pdo->prepare("SELECT name FROM hn_tags WHERE user_id = :uid ORDER BY updated_at DESC, name ASC LIMIT {$limit}");
        $stmt->execute(['uid' => $this->userId]);
        return array_map(fn($r) => (string)$r['name'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

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
                ORDER BY favorite_level DESC, m.generation ASC, m.kana ASC, n.is_favorite DESC, n.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid_fav' => $this->userId, 'uid_neta' => $this->userId]);
        $rows = $stmt->fetchAll();

        // tags
        $netaIds = array_map(fn($r) => (int)($r['id'] ?? 0), $rows);
        $tagsMap = [];
        if (!empty($netaIds)) {
            $placeholders = implode(',', array_fill(0, count($netaIds), '?'));
            $sqlTags = "SELECT nt.neta_id, t.name
                        FROM hn_neta_tags nt
                        JOIN hn_tags t ON t.id = nt.tag_id
                        WHERE nt.neta_id IN ({$placeholders})
                        ORDER BY t.name ASC";
            $stmt2 = $this->pdo->prepare($sqlTags);
            $stmt2->execute($netaIds);
            $tagRows = $stmt2->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($tagRows as $tr) {
                $nid = (int)($tr['neta_id'] ?? 0);
                if (!$nid) continue;
                if (!isset($tagsMap[$nid])) $tagsMap[$nid] = [];
                $tagsMap[$nid][] = (string)($tr['name'] ?? '');
            }
        }

        $rows = $this->decryptRows($rows);
        foreach ($rows as &$r) {
            $id = (int)($r['id'] ?? 0);
            $r['tags'] = $tagsMap[$id] ?? [];
            $r['is_favorite'] = (int)($r['is_favorite'] ?? 0);
            $r['neta_type'] = $r['neta_type'] ?? null;
        }

        $grouped = [];
        foreach ($rows as $row) {
            $mid = $row['member_id'];
            if (!isset($grouped[$mid])) {
                $grouped[$mid] = [
                    'member_name' => $row['member_name'],
                    'color1' => $row['color1'] ?: '#7cc7e8',
                    'color2' => $row['color2'] ?: $row['color1'] ?: '#7cc7e8',
                    'favorite_level' => (int)($row['favorite_level'] ?? 0),
                    'items' => []
                ];
            }
            $grouped[$mid]['items'][] = $row;
        }
        return $grouped;
    }
}