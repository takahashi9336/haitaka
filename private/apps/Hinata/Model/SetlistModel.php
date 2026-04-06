<?php

namespace App\Hinata\Model;

use Core\BaseModel;
use Core\Database;

/**
 * セットリスト・参戦記録モデル
 */
class SetlistModel extends BaseModel {
    protected string $table = 'hn_setlists';
    protected array $fields = [
        'id', 'event_id', 'song_id', 'entry_type', 'sort_order', 'encore', 'label', 'block_kind', 'center_member_id', 'memo',
        'created_at', 'updated_at', 'update_user'
    ];
    protected bool $isUserIsolated = false;

    public function getByEventId(int $eventId): array {
        $sql = "SELECT sl.*,
                       s.title as song_title, s.track_type,
                       r.title as release_title, r.release_type,
                       cm.name as center_member_name
                FROM {$this->table} sl
                LEFT JOIN hn_songs s ON sl.song_id = s.id
                LEFT JOIN hn_releases r ON s.release_id = r.id
                LEFT JOIN hn_members cm ON sl.center_member_id = cm.id
                WHERE sl.event_id = :eid
                ORDER BY sl.sort_order ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['eid' => $eventId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return [];
        }

        // 複数センター対応（hn_setlist_centers を正とする）
        $ids = array_map('intval', array_column($rows, 'id'));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $cSql = "SELECT sc.setlist_id, sc.member_id, m.name, m.generation
                 FROM hn_setlist_centers sc
                 JOIN hn_members m ON m.id = sc.member_id
                 WHERE sc.setlist_id IN ({$placeholders})
                 ORDER BY sc.setlist_id ASC, m.generation ASC, m.kana ASC";
        $cStmt = $this->pdo->prepare($cSql);
        $cStmt->execute($ids);
        $centerBySetlist = [];
        foreach ($cStmt->fetchAll(\PDO::FETCH_ASSOC) as $c) {
            $sid = (int)$c['setlist_id'];
            if (!isset($centerBySetlist[$sid])) $centerBySetlist[$sid] = [];
            $centerBySetlist[$sid][] = [
                'member_id' => (int)$c['member_id'],
                'name' => (string)$c['name'],
                'generation' => (int)($c['generation'] ?? 0),
            ];
        }

        foreach ($rows as &$r) {
            $sid = (int)$r['id'];
            $centers = $centerBySetlist[$sid] ?? [];
            $r['center_members'] = $centers;
            $r['center_member_ids'] = array_map(fn($x) => (int)$x['member_id'], $centers);
        }
        unset($r);

        return $rows;
    }

    /**
     * セットリストを一括保存（delete-insert）
     * @param array $items [
     *   { entry_type: song|mc|block, sort_order, song_id?, encore?, label?, block_kind?, center_member_id?, memo? }
     * ]
     */
    public function saveForEvent(int $eventId, array $items): void {
        // 先にセンター中間テーブルを掃除（hn_setlists を消すと setlist_id が分からなくなるため）
        $stmtIds = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE event_id = ?");
        $stmtIds->execute([$eventId]);
        $oldIds = array_map('intval', array_column($stmtIds->fetchAll(\PDO::FETCH_ASSOC), 'id'));
        if (!empty($oldIds)) {
            $ph = implode(',', array_fill(0, count($oldIds), '?'));
            $del = $this->pdo->prepare("DELETE FROM hn_setlist_centers WHERE setlist_id IN ({$ph})");
            $del->execute($oldIds);
        }

        $this->pdo->prepare("DELETE FROM {$this->table} WHERE event_id = ?")->execute([$eventId]);
        if (empty($items)) return;
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (event_id, song_id, entry_type, sort_order, encore, label, block_kind, center_member_id, memo, update_user)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $user = $_SESSION['user']['id_name'] ?? '';
        foreach ($items as $item) {
            $entryType = (string)($item['entry_type'] ?? 'song');
            if (!in_array($entryType, ['song', 'mc', 'block'], true)) {
                $entryType = 'song';
            }

            $songId = $item['song_id'] ?? null;
            if ($songId === '' || $songId === null) {
                $songId = null;
            } else {
                $songId = (int)$songId;
            }

            if ($entryType === 'song' && !$songId) {
                throw new \Exception('song行には song_id が必要です');
            }
            if ($entryType !== 'song') {
                $songId = null;
            }

            $encore = ($entryType === 'song' && !empty($item['encore'])) ? 1 : 0;
            $label = isset($item['label']) && trim((string)$item['label']) !== '' ? trim((string)$item['label']) : null;
            $blockKind = isset($item['block_kind']) && trim((string)$item['block_kind']) !== '' ? trim((string)$item['block_kind']) : null;

            $centerMemberId = $item['center_member_id'] ?? null;
            if ($entryType !== 'song') {
                $centerMemberId = null;
            } else {
                if ($centerMemberId === '' || $centerMemberId === null) {
                    $centerMemberId = null;
                } else {
                    $centerMemberId = (int)$centerMemberId;
                }
            }

            $stmt->execute([
                $eventId,
                $songId,
                $entryType,
                (int)$item['sort_order'],
                $encore,
                $label,
                $blockKind,
                $centerMemberId,
                $item['memo'] ?? null,
                $user,
            ]);

            $setlistId = (int)$this->pdo->lastInsertId();
            if ($entryType === 'song') {
                $centerIds = $item['center_member_ids'] ?? null;
                if (is_array($centerIds)) {
                    $centerIds = array_values(array_unique(array_map('intval', $centerIds)));
                    $centerIds = array_values(array_filter($centerIds, fn($v) => $v > 0));
                } else {
                    $centerIds = [];
                }

                // 互換: center_member_id があり、center_member_ids が無い場合は単体として扱う
                if (empty($centerIds) && $centerMemberId) {
                    $centerIds = [(int)$centerMemberId];
                }

                if (!empty($centerIds)) {
                    $ins = $this->pdo->prepare("INSERT INTO hn_setlist_centers (setlist_id, member_id) VALUES (?, ?)");
                    foreach ($centerIds as $mid) {
                        $ins->execute([$setlistId, (int)$mid]);
                    }
                }
            }
        }
    }

    /**
     * 参戦トグル
     */
    public function toggleAttendance(int $eventId): bool {
        $userId = $_SESSION['user']['id'] ?? 0;
        $pdo = Database::connect();
        $check = $pdo->prepare("SELECT id FROM hn_event_attendance WHERE user_id = ? AND event_id = ?");
        $check->execute([$userId, $eventId]);
        if ($check->fetch()) {
            $pdo->prepare("DELETE FROM hn_event_attendance WHERE user_id = ? AND event_id = ?")->execute([$userId, $eventId]);
            return false;
        }
        $pdo->prepare("INSERT INTO hn_event_attendance (user_id, event_id) VALUES (?, ?)")->execute([$userId, $eventId]);
        return true;
    }

    public function isAttended(int $eventId): bool {
        $userId = $_SESSION['user']['id'] ?? 0;
        $stmt = $this->pdo->prepare("SELECT 1 FROM hn_event_attendance WHERE user_id = ? AND event_id = ?");
        $stmt->execute([$userId, $eventId]);
        return (bool)$stmt->fetch();
    }

    /**
     * ユーザーの参戦イベントID一覧
     */
    public function getAttendedEventIds(): array {
        $userId = $_SESSION['user']['id'] ?? 0;
        $stmt = $this->pdo->prepare("SELECT event_id FROM hn_event_attendance WHERE user_id = ?");
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'event_id');
    }

    /**
     * 楽曲別のライブ披露回数（ユーザーが参戦したライブのみ）
     */
    public function getSongPlayCounts(): array {
        $userId = $_SESSION['user']['id'] ?? 0;
        $sql = "SELECT s.id as song_id, s.title as song_title, COUNT(*) as play_count
                FROM hn_setlists sl
                JOIN hn_songs s ON sl.song_id = s.id
                JOIN hn_event_attendance ea ON ea.event_id = sl.event_id AND ea.user_id = :uid
                WHERE sl.entry_type = 'song'
                GROUP BY s.id, s.title
                ORDER BY play_count DESC, s.title ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * セットリストが登録されているイベントID一覧
     */
    public function getEventsWithSetlist(): array {
        $sql = "SELECT DISTINCT event_id FROM {$this->table}";
        return array_column($this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC), 'event_id');
    }
}
